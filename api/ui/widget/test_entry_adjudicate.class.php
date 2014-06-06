<?php
/**
 * test_entry_adjudicate.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget test_entry adjudicate
 */
class test_entry_adjudicate extends \cenozo\ui\widget\base_record
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry', 'adjudicate', $args );
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

    $test_class_name = lib::get_class_name('database\test');

    $db_test_entry = $this->get_record();
    $db_test = $db_test_entry->get_test();
    $db_participant = $db_test_entry->get_assignment()->get_participant();

    // create the test_entry sub widget
    // example: widget class test_entry_ranked_word_adjudicate
    $this->test_entry_widget = lib::create(
      'ui\widget\test_entry_' . $db_test->get_test_type()->name . '_adjudicate',
        $this->arguments );

    $this->test_entry_widget->set_parent( $this );

    $modifier = NULL;
    if( $db_participant->get_cohort()->name == 'tracking' )
    {
      $modifier = lib::create('database\modifier');
      $modifier->where( 'name', 'NOT LIKE', 'FAS%' );
    }

    $test_count = $test_class_name::count( $modifier );

    $heading = sprintf( 'test %d / %d for %s',
      $db_test->rank, $test_count, $db_participant->uid );
    $this->set_heading( $heading );
  }

  /**
   * Sets up the operation with any pre-execution instructions that may be necessary.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );

    $db_test_entry = $this->get_record();
    $db_test = $db_test_entry->get_test();
    $test_type_name = $db_test->get_test_type()->name;

    $db_assignment = $db_test_entry->get_assignment();
    if( is_null( $db_assignment ) )
      throw lib::create( 'exception\runtime',
        'Test entry adjudication requires a valid assignment', __METHOD__ );

    $this->set_variable( 'test_id', $db_test->id );

    // set the dictionary id's needed for text autocomplete
    if( $test_type_name == 'classification' || $test_type_name == 'ranked_word' )
    {
      $db_dictionary = $db_test->get_dictionary();
      if( !is_null( $db_dictionary ) )
        $this->set_variable( 'dictionary_id', $db_dictionary->id );

      $db_variant_dictionary = $db_test->get_variant_dictionary();
      if( !is_null( $db_variant_dictionary ) )
        $this->set_variable( 'variant_dictionary_id', $db_variant_dictionary->id );

      $db_intrusion_dictionary = $db_test->get_intrusion_dictionary();
      if( !is_null( $db_intrusion_dictionary ) )
        $this->set_variable( 'intrusion_dictionary_id', $db_intrusion_dictionary->id );
    }

    $db_participant = $db_assignment->get_participant();
    $this->set_variable( 'participant_id', $db_participant->id );

    $db_language = $db_participant->get_language();
    if( is_null( $db_language ) )
      $db_language = lib::create( 'business\session' )->get_service()->get_language();

    $this->set_variable( 'language_id', $db_language->id );

    if( $db_participant->get_cohort()->name == 'tracking' )
    {
      $setting_manager = lib::create( 'business\setting_manager' );
      $sabretooth_manager = lib::create( 'business\cenozo_manager', SABRETOOTH_URL );
      $sabretooth_manager->set_user( $setting_manager->get_setting( 'sabretooth', 'user' ) );
      $sabretooth_manager->set_password( $setting_manager->get_setting( 'sabretooth', 'password' ) );
      $sabretooth_manager->set_site( $setting_manager->get_setting( 'sabretooth', 'site' ) );
      $sabretooth_manager->set_role( $setting_manager->get_setting( 'sabretooth', 'role' ) );

      $args = array();
      $args['qnaire_rank'] = 1;
      $args['participant_id'] = $db_participant->id;
      $recording_list = $sabretooth_manager->pull( 'recording', 'list', $args );
      $recording_data = array();
      if( !is_null( $recording_list ) &&
          1 == $recording_list->success && 0 < count( $recording_list->data ) )
      {
        foreach( $recording_list->data as $data )
        {
          $url = SABRETOOTH_URL . '/' . $data->url;
          // has to be this servers domain not localhost
          $recording_data[] = str_replace( 'localhost', $_SERVER['SERVER_NAME'], $url );
        }
      }
      $this->set_variable( 'recording_data', $recording_data );
    }

    $this->set_variable( 'test_entry_id_1', $db_test_entry->id );
    $this->set_variable( 'user_1', $db_assignment->get_user()->name );

    $db_sibling_assignment = $db_assignment->get_sibling_assignment();
    if( is_null( $db_sibling_assignment ) )
      throw lib::create( 'exception\runtime',
        'Test entry adjudication requires a valid assignment', __METHOD__ );

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'test_id', '=', $db_test_entry->get_test()->id );
    $modifier->where( 'deferred', '=', false );
    $modifier->where( 'completed', '=', true );
    $modifier->where( 'adjudicate', '=', true );
    $modifier->where( 'assignment_id', '=', $db_sibling_assignment->id );
    $modifier->limit( 1 );
    $db_sibling_test_entry = current( $test_entry_class_name::select( $modifier ) );
    if( false === $db_sibling_test_entry )
      throw lib::create( 'exception\runtime',
        'Test entry adjudication requires a valid sibling test entry', __METHOD__ );

    $this->set_variable( 'test_entry_id_2', $db_sibling_test_entry->id );
    $this->set_variable( 'user_2', $db_sibling_assignment->get_user()->name );

    $this->set_variable( 'rank', $db_test->rank );
    $this->set_variable( 'test_type', $db_test->get_test_type()->name );

    // find the ids of the prev and next test_entrys
    $db_prev_test_entry = $db_test_entry->get_previous( true );
    $db_next_test_entry = $db_test_entry->get_next( true );

    $this->set_variable( 'prev_test_entry_id',
      is_null( $db_prev_test_entry ) ? 0 : $db_prev_test_entry->id );

    $this->set_variable( 'next_test_entry_id',
      is_null( $db_next_test_entry ) ? 0 : $db_next_test_entry->id );

    try
    {
      $this->test_entry_widget->process();
      $this->set_variable( 'test_entry_args', $this->test_entry_widget->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}

    // assignment_manager creates the adjudicate entry
    $db_adjudicate_test_entry = $test_entry_class_name::get_unique_record(
      array( 'test_id', 'participant_id' ),
      array( $db_test_entry->get_test()->id,
             $db_test_entry->get_assignment()->get_participant()->id ) );
    $this->set_variable( 'adjudicate_entry_id', $db_adjudicate_test_entry->id );
  }

  /**
   * The test entry widget.
   * @var test_entry_widget
   * @access protected
   */
  protected $test_entry_widget = NULL;
}
