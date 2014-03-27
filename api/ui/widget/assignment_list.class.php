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
class assignment_list extends \cenozo\ui\widget\base_list
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
    
    $this->add_column( 'participant.uid', 'string', 'UID', true );
    $this->add_column( 'cohort', 'string', 'Cohort', false );
    $this->add_column( 'user.name', 'string', 'User', true );
    $this->add_column( 'defer', 'string', 'Defer', false );
    $this->add_column( 'adjudicate', 'string', 'Adjudicate', false );
    $this->add_column( 'complete', 'string', 'Complete', false );

    $session = lib::create( 'business\session' );
    $this->set_addable( $session->get_role()->name == 'typist' );
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

    $operation_class_name = lib::get_class_name( 'database\operation' );
    $test_class_name = lib::get_class_name( 'database\test' );
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );

    $session = lib::create( 'business\session' );
    $db_role = $session->get_role();

    // allow test_entry transcribe via a transcribe button on assigment rows
    $allow_transcribe_operation = false;
    // allow test_entry adjudicate via a adjudicate button on assignment rows
    $allow_adjudicate_operation = false;

    foreach( $this->get_record_list() as $db_assignment )
    {
      $base_mod = lib::create( 'database\modifier' );
      $base_mod->where( 'assignment_id', '=', $db_assignment->id );

      // the number of tests is cohort and therefore assigment dependent
      $test_count = $test_entry_class_name::count( clone $base_mod );

      $completed_mod = clone $base_mod;
      $completed_mod->where( 'completed', '=', true );
      $complete_count = $test_entry_class_name::count( $completed_mod );

      $db_participant = $db_assignment->get_participant();
      $language = $db_participant->language;

      $deferred_mod = clone $base_mod;
      $deferred_mod->where( 'deferred', '=', true );
      $defer_count = $test_entry_class_name::count( $deferred_mod );

      $adjudicate_mod = clone $base_mod;
      $adjudicate_mod->where( 'adjudicate', '=', true );
      $adjudicate_count = $test_entry_class_name::count( $adjudicate_mod );

      $allow_transcribe = false;
      $allow_adjudicate = false;

      // select the first test_entry for which we either want to transcribe
      // or adjudicate depending on user role
      $test_entry_id = NULL;

      if( $db_role->name == 'typist' )
      {
        $test_entry_mod = clone $base_mod;
        $test_entry_mod->where( 'completed', '=', false );
        $test_entry_mod->order( 'test.rank' );
        $test_entry_mod->limit( 1 );
        $db_test_entry = current( $test_entry_class_name::select( $test_entry_mod ) );
        if( !is_null( $db_test_entry ) ) 
        {  
          $test_entry_id = $db_test_entry->id;
          $allow_transcribe = true;
          $allow_transcribe_operation |= $allow_transcribe;
        }
      }
      else if( $db_role->name == 'administrator' && 
               $complete_count == $test_count && 0 < $adjudicate_count)
      {
        $db_sibling_assignment = $db_assignment->get_sibling_assignment();
        if( $db_sibling_assignment != NULL && $db_sibling_assignment->all_tests_complete() )
        {
          $allow_adjudicate = false;

          // get the first test entry of current db_assignment that requires adjudication
          $test_entry_mod = clone $base_mod;
          $test_entry_mod->where( 'adjudicate', '=', true );
          $test_entry_mod->order( 'test.rank' );
          $test_entry_mod->limit( 1 );

          $db_test_entry = current( $test_entry_class_name::select( $test_entry_mod ) );
          if( !is_null( $db_test_entry ) )
          {
            // see if the sibling test_entry exists
            $sibling_mod = lib::create( 'database\modifier' );
            $sibling_mod->where( 'adjudicate', '=', true );
            $sibling_mod->where( 'assignment_id', '=', $db_sibling_assignment->id );
            $sibling_mod->where( 'test_id', '=', $db_test_entry->test_id );
            $db_sibling_test_entry = current( $test_entry_class_name::select( $sibling_mod ) );
            if( !is_null( $db_sibling_test_entry ) ) 
            {
              $test_entry_id = $db_test_entry->id;
              $allow_adjudicate = true;
              $allow_adjudicate_operation |= $allow_adjudicate;
            }
          }
        }
      }

      $this->add_row( $db_assignment->id,
        array( 'participant.uid' => $db_participant->uid,
               'cohort' => $db_participant->get_cohort()->name,
               'user.name' => $db_assignment->get_user()->name,
               'defer' => 
                 $defer_count > 0 ? $defer_count . '/' . $test_count : 'none',
               'adjudicate' => 
                 $adjudicate_count > 0 ? $adjudicate_count . '/' . $test_count : 'none',
               'complete' =>  
                 $complete_count > 0 ? $complete_count . '/' . $test_count : 'none',
               'allow_transcribe' => $allow_transcribe,
               'allow_adjudicate' => $allow_adjudicate,
               'test_entry_id' => $test_entry_id ) );
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
    // for typist role, restrict to their incomplete assignments
    $session = lib::create( 'business\session' );
    if( $session->get_role()->name == 'typist' )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'user_id', '=', $session->get_user()->id );
      $modifier->where( 'test_entry.completed', '=', false );
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
    // for typist role, restrict to their incomplete assignments
    $session = lib::create( 'business\session' );
    if( $session->get_role()->name == 'typist' )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'user_id', '=', $session->get_user()->id );
      $modifier->where( 'test_entry.completed', '=', false );      
    }

    return parent::determine_record_list( $modifier );
  }
}
