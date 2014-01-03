<?php
/**
 * test_entry_transcribe.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget test_entry transcribe
 *
 * Transcribe recordings into a test_entry.
 */
class test_entry_transcribe extends \cenozo\ui\widget\base_record
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry', 'transcribe', $args );
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

    $record = $this->get_record();
    $db_test = $record->get_test();
    $db_participant = $record->get_assignment()->get_participant();

    $db_test_type = $db_test->get_test_type();

    // create the test_entry_widget sub widget
    $this->test_entry_widget = lib::create( 
      'ui\widget\test_entry_' . $db_test_type->name , $this->arguments );
    $this->test_entry_widget->set_parent( $this );

    $modifier = NULL;
    if( $db_participant->get_cohort()->name == 'tracking' )
    {
      $modifier = lib::create('database\modifier');
      $modifier->where( 'name', 'not like', 'FAS%' );
    }     
    
    $test_class_name = lib::get_class_name('database\test');
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

    $record = $this->get_record();
    $db_test = $record->get_test();

    $this->set_variable( 'audio_fault', $record->audio_fault );
    $this->set_variable( 'deferred', $record->deferred );
    $this->set_variable( 'rank', $db_test->rank );
    $this->set_variable( 'test_type', $db_test->get_test_type()->name );

    // find the ids of the prev and next test_entrys
    $db_prev_test_entry = $record->get_previous();
    $db_next_test_entry = $record->get_next();

    $this->set_variable( 'prev_test_entry_id', 
      is_null($db_prev_test_entry) ? 0 : $db_prev_test_entry->id );

    $this->set_variable( 'next_test_entry_id', 
      is_null($db_next_test_entry) ? 0 : $db_next_test_entry->id );

    try 
    {   
      $this->test_entry_widget->process();
      $this->set_variable( 'test_entry_args', $this->test_entry_widget->get_variables() );
    }   
    catch( \cenozo\exception\permission $e ) {}
  }

  /**
   * The test entry widget.
   * @var test_entry_widget
   * @access protected
   */
  protected $test_entry_widget = NULL;
}
