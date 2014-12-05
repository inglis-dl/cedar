<?php
/**
 * assignment_list.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget assignment list
 */
class assignment_list extends \cenozo\ui\widget\site_restricted_list
{
  /**
   * Constructor
   *
   * Defines all variables required by the assignment list.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'assignment', $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $region_site_class_name = lib::get_class_name( 'database\region_site' );

    $session = lib::create( 'business\session' );
    $db_role = $session->get_role();

    $this->add_column( 'start_datetime', 'datetime', 'Start', true );
    $this->add_column( 'participant.uid', 'string', 'UID', true );
    $this->add_column( 'cohort.name', 'string', 'Cohort', true );
    $this->add_column( 'user.name', 'string', 'User', true );
    $this->add_column( 'deferred', 'boolean', 'Deferred', false );
    $this->add_column( 'adjudicate', 'boolean', 'Adjudicate', false );
    $this->add_column( 'completed', 'boolean', 'Completed', false );

    $is_typist = 'typist' == $db_role->name;
    if( $is_typist )
    {
      $db_service = $session->get_service();
      $db_site = $session->get_site();
      $db_user = $session->get_user();

      // which languages can the user process?
      $user_languages = $db_user->get_language_idlist();

      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'service_id', '=', $db_service->id );
      $modifier->where( 'site_id', '=', $db_site->id );
      $modifier->group( 'language_id' );

      // which languages can the site process?
      $site_languages = array();
      foreach( $region_site_class_name::select( $modifier ) as $db_region_site )
      {
        $id = $db_region_site->get_language()->id;
        if( in_array( $id, $user_languages ) )
        {
          $site_languages[] = $id;
        }
      }

      // sanity check that the user can transcribe in a language at the current site
      if( 0 == count( $site_languages ) )
        throw lib::create( 'exception\notice',
          'There must be one or more region-site languages from '.
          $db_site->name . ' assigned to user: '.
          $db_user->name, __METHOD__ );
    }

    $this->set_addable( $is_typist );
    $this->set_allow_restrict_state( !$is_typist );
    $this->set_removable( 'administrator' == $db_role->name );

    if( $this->allow_restrict_state )
    {
      $restrict_state_id = $this->get_argument( 'restrict_state_id', '' );
      $restrict_language = $this->get_argument( 'restrict_language', 'any' );
      $restrict_on_state = $restrict_state_id != array_search( 'No restriction', $this->state_list );
      $restrict_on_language = $restrict_language != 'any';
      if( $restrict_on_state )
      {
        if( $restrict_on_language )
        {
          $this->set_heading( sprintf( '%s %s, restricted to %s %s assignments',
            $this->get_subject(),
            $this->get_name(),
            $this->get_restrict_state_name( $restrict_state_id ),
            $restrict_language == 'fr' ? 'French' : 'English' ) );
         }
         else
         {
          $this->set_heading( sprintf( '%s %s, restricted to %s assignments',
            $this->get_subject(),
            $this->get_name(),
            $this->get_restrict_state_name( $restrict_state_id ) ) );
         }
      }
      else if( $restrict_on_language )
      {
        $this->set_heading( sprintf( '%s %s, restricted to %s assignments',
          $this->get_subject(),
          $this->get_name(),
          $restrict_language == 'fr' ? 'French' : 'English' ) );
      }
    }
  }

  /**
   * Set the rows array needed by the template.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $assignment_class_name = lib::get_class_name( 'database\assignment' );
    $language_class_name = lib::get_class_name( 'database\language' );
    $operation_class_name = lib::get_class_name( 'database\operation' );
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $test_class_name = lib::get_class_name( 'database\test' );
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );

    $session = lib::create( 'business\session' );
    $db_role = $session->get_role();

    // allow test_entry transcribe via a transcribe button on assigment rows
    $allow_transcribe_operation = false;
    // allow test_entry adjudicate via a adjudicate button on assignment rows
    $allow_adjudicate_operation = false;

    if( $this->allow_restrict_state )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'active', '=', true );
      $languages = array( 'any' => 'any' );
      foreach( $language_class_name::select( $modifier ) as $db_language )
        $languages[$db_language->id] = $db_language->name;
      $this->set_variable( 'languages', $languages );

      $restrict_language_id = $this->get_argument( 'restrict_language_id', 'any' );
      $this->set_variable( 'restrict_language_id', $restrict_language_id );
    }

    foreach( $this->get_record_list() as $db_assignment )
    {
      $base_mod = lib::create( 'database\modifier' );
      $base_mod->where( 'assignment_id', '=', $db_assignment->id );

      $db_participant = $db_assignment->get_participant();

      $allow_transcribe = false;
      $allow_adjudicate = false;

      $deferred   = $db_assignment->has_deferrals();
      $adjudicate = $db_assignment->has_adjudicates();
      $completed  = $assignment_class_name::all_tests_complete( $db_assignment->id );

      // select the first test_entry for which we either want to transcribe
      // or adjudicate depending on user role
      $test_entry_id = NULL;

      if( 'typist' == $db_role->name )
      {
        $test_entry_mod = clone $base_mod;
        // get the first test that could be pending
        if( $deferred )
        {
          $test_entry_mod->where( 'IFNULL( deferred, "NULL" )', '=', 'pending' );
        }
        // otherwise, get the first incomplete test_entry
        else
        {
          $test_entry_mod->where( 'completed', '=', false );
        }
        $test_entry_mod->order( 'test.rank' );
        $test_entry_mod->limit( 1 );
        $db_test_entry = current( $test_entry_class_name::select( $test_entry_mod ) );
        if( false !== $db_test_entry )
        {
          $test_entry_id = $db_test_entry->id;
          $allow_transcribe = true;
          $allow_transcribe_operation = $allow_transcribe;
        }
      }
      else if( 'typist' != $db_role->name && $completed && $adjudicate && !$deferred )
      {
        $db_sibling_assignment = $db_assignment->get_sibling_assignment();
        if( !is_null( $db_sibling_assignment ) &&
            $assignment_class_name::all_tests_complete( $db_sibling_assignment->id ) &&
            $db_sibling_assignment->has_adjudicates() &&
            !$db_sibling_assignment->has_deferrals() )
        {
          // get the first test entry of current db_assignment that requires adjudication
          $test_entry_mod = clone $base_mod;
          $test_entry_mod->where( 'adjudicate', '=', true );
          $test_entry_mod->where( 'completed', '=', true );
          $test_entry_mod->where( 'IFNULL( deferred, "NULL" )', 'NOT IN',
            $test_entry_class_name::$deferred_states );
          $test_entry_mod->order( 'test.rank' );
          $test_entry_mod->limit( 1 );

          $db_test_entry = current( $test_entry_class_name::select( $test_entry_mod ) );
          if( false !== $db_test_entry )
          {
            // see if the sibling test_entry exists
            $sibling_mod = lib::create( 'database\modifier' );
            $sibling_mod->where( 'adjudicate', '=', true );
            $sibling_mod->where( 'completed', '=', true );
            $sibling_mod->where( 'IFNULL( deferred, "NULL" )', 'NOT IN',
              $test_entry_class_name::$deferred_states );

            if( !is_null( $db_test_entry->get_sibling_test_entry( $sibling_mod ) ) &&
                !$allow_adjudicate )
            {
              $test_entry_id = $db_test_entry->id;
              $allow_adjudicate = true;
              $allow_adjudicate_operation = $allow_adjudicate;
            }
          }
        }
      }

      $row = array(
        'start_datetime' => $db_assignment->start_datetime,
        'participant.uid' => $db_participant->uid,
        'cohort.name' => $db_participant->get_cohort()->name,
        'user.name' => $db_assignment->get_user()->name,
        'deferred' => $deferred,
        'adjudicate' =>  $adjudicate,
        'completed' =>  $completed,
        'allow_transcribe' => $allow_transcribe ? 1 : 0,
        'allow_adjudicate' => $allow_adjudicate ? 1 : 0,
        'test_entry_id' => is_null( $test_entry_id ) ? '' : $test_entry_id );

      $this->add_row( $db_assignment->id, $row );
    }

    if( $this->allow_restrict_state )
    {
      $this->set_variable( 'state_list', $this->state_list );
      $this->set_variable( 'restrict_state_id', $this->get_argument( 'restrict_state_id', '' ) );
    }

    // define whether or not test_entry transcribing or adjudicating is allowed
    $db_operation = $operation_class_name::get_operation( 'widget', 'test_entry', 'transcribe' );
    $this->set_variable( 'allow_transcribe',
      ( lib::create( 'business\session' )->is_allowed( $db_operation ) &&
        $allow_transcribe_operation ) );

    $db_operation = $operation_class_name::get_operation( 'widget', 'test_entry', 'adjudicate' );
    $this->set_variable( 'allow_adjudicate',
      ( lib::create( 'business\session' )->is_allowed( $db_operation ) &&
        $allow_adjudicate_operation ) );
  }

  /**
   * Overrides the parent class method to restrict by user_id and test_entry
   * completed status, if necessary
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_record_count( $modifier = NULL )
  {
    $database_class_name = lib::get_class_name( 'database\database' );
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );

    // for typist role, restrict to their incomplete assignments
    $session = lib::create( 'business\session' );
    $db_role = $session->get_role();
    if( 'typist' == $db_role->name )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'user_id', '=', $session->get_user()->id );
      $modifier->where_bracket( true );
      $modifier->where( 'test_entry.completed', '=', false );
      $modifier->or_where( 'IFNULL(test_entry.deferred, "NULL")', '=', 'pending' );
      $modifier->where_bracket( false );
    }

    if( $this->allow_restrict_state )
    {
      $restrict_state_id = $this->get_argument( 'restrict_state_id', '' );
      if( isset( $restrict_state_id ) &&
          $restrict_state_id != array_search( 'No restriction', $this->state_list ) )
      {
        if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
        // Closed
        if( $restrict_state_id == array_search( 'Closed', $this->state_list ) )
        {
          $modifier->where( 'end_datetime', '!=', NULL );
        }
        else
        {
          // Open
          $modifier->where( 'end_datetime', '=', NULL );
          if( $restrict_state_id == array_search( 'Deferred', $this->state_list ) )
          {
            $modifier->where( 'test_entry.deferred', 'IN',
              $test_entry_class_name::$deferred_states );
          }
          else if( $restrict_state_id == array_search( 'Adjudicate', $this->state_list ) )
          {
            $modifier->where( 'IFNULL( test_entry.deferred, "NULL" )', 'NOT IN',
              $test_entry_class_name::$deferred_states );
            $modifier->where( 'test_entry.adjudicate', '=', true );
          }
        }
      }

      $restrict_language_id = $this->get_argument( 'restrict_language_id', 'any' );
      // restrict by language
      if( 'any' != $restrict_language_id )
      {
        if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
        $column = sprintf(
          'IFNULL( participant.language_id, %s )',
          $database_class_name::format_string( $session->get_service()->language_id ) );
        $modifier->where( $column, '=', $restrict_language_id );
      }
    }
    return parent::determine_record_count( $modifier );
  }

  /**
   * Overrides the parent class method to restrict by user_id and test_entry
   * completed status, if necessary
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_record_list( $modifier = NULL )
  {
    $database_class_name = lib::get_class_name( 'database\database' );
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );

    // for typist role, restrict to their incomplete assignments
    $session = lib::create( 'business\session' );
    $db_role = $session->get_role();
    if( $db_role->name == 'typist' )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'user_id', '=', $session->get_user()->id );
      $modifier->where_bracket( true );
      $modifier->where( 'test_entry.completed', '=', false );
      $modifier->or_where( 'IFNULL(test_entry.deferred, "NULL")', '=', 'pending' );
      $modifier->where_bracket( false );
    }

    if( $this->allow_restrict_state )
    {
      $restrict_state_id = $this->get_argument( 'restrict_state_id', '' );
      if( isset( $restrict_state_id ) &&
          $restrict_state_id != array_search( 'No restriction', $this->state_list ) )
      {
        if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
        // Closed
        if( $restrict_state_id == array_search( 'Closed', $this->state_list ) )
        {
          $modifier->where( 'end_datetime', '!=', NULL );
        }
        else
        {
          // Open
          $modifier->where( 'end_datetime', '=', NULL );
          if( $restrict_state_id == array_search( 'Deferred', $this->state_list ) )
          {
            $modifier->where( 'test_entry.deferred', 'IN',
              $test_entry_class_name::$deferred_states );
          }
          else if( $restrict_state_id == array_search( 'Adjudicate', $this->state_list ) )
          {
            $modifier->where( 'IFNULL( test_entry.deferred, "NULL" )', 'NOT IN',
              $test_entry_class_name::$deferred_states );
            $modifier->where( 'test_entry.adjudicate', '=', true );
          }
        }
      }

      $restrict_language_id = $this->get_argument( 'restrict_language_id', 'any' );
      // restrict by language
      if( 'any' != $restrict_language_id )
      {
        if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
        $column = sprintf( 'IFNULL( participant.language_id, %s )',
                           $database_class_name::format_string(
                             $session->get_service()->language_id ) );
        $modifier->where( $column, '=', $restrict_language_id );
      }
    }

    return parent::determine_record_list( $modifier );
  }

  /**
   * Get whether to include a drop down to restrict the list by state
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return boolean
   * @access public
   */
  public function get_allow_restrict_state()
  {
    return $this->allow_restrict_state;
  }

  /**
   * Set whether to include a drop down to restrict the list by state
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param boolean $enable
   * @access public
   */
  public function set_allow_restrict_state( $enable )
  {
    $this->allow_restrict_state = $enable;
  }

  /**
   * Whether to include a drop down to restrict the list by state
   * @var boolean
   * @access protected
   */
  protected $allow_restrict_state = true;

  /**
   * The associative array of restrictable states
   * @var array
   * @access protected
   */
  protected $state_list = array(
    1 => 'Closed', 2 => 'No restriction', 3 => 'Deferred', 4 => 'Adjudicate' );

  /**
   * Get a restrict state name from its id
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param boolean $enable
   * @access public
   */
  private function get_restrict_state_name( $id )
  {
    return array_key_exists( $id, $this->state_list ) ? $this->state_list[$id] : 'Open';
  }
}
