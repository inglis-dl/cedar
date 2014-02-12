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

    $record = $this->get_record();

    $db_assignment = $record->get_assignment();

    if( is_null( $db_assignment ) )
      throw lib::create( 'exception\notice',
        'The test entry must have a link to the assignment table.', __METHOD__ );

    $db_test = $record->get_test();
    $db_participant = $db_assignment->get_participant();
    $this->adjudicate_entry = $record->get_adjudicate_entry();

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

    $db_assignment = $record->get_assignment();
    if( empty( $db_assignment ) || is_null( $db_assignment ) )
      throw lib::create( 'exception\runtime',
        'Test entry adjudication requires a valid assignment', __METHOD__ );

    $this->set_variable( 'test_id', $record->test_id );
    $this->set_variable( 'participant_id', $db_assignment->get_participant()->id );
    $db_dictionary = $db_test->get_dictionary();
    $dictionary_id = ''; 
    if( !empty( $db_dictionary ) ) 
      $dictionary_id = $db_dictionary->id;
    $this->set_variable( 'dictionary_id', $dictionary_id );

    $language = 'any';
    $db_participant = $record->get_assignment()->get_participant();
    if( empty( $db_participant ) || is_null( $db_participant ) )
      throw lib::create( 'exception\runtime',
        'The participant id must be set', __METHOD__ );

    $language = is_null( $db_participant->language ) ? 'any' : $db_participant->language;
    $this->set_variable( 'language', $language );

    $db_cohort = $db_participant->get_cohort();
    if( $db_cohort->name == 'tracking' )
    {   
      $sabretooth_manager = lib::create( 'business\cenozo_manager', SABRETOOTH_URL );
      $sabretooth_manager->use_machine_credentials( true );
      $args = array();
      $args['qnaire_rank'] = 1;
      $args['participant_id'] = $db_participant->id;
      $recording_list = $sabretooth_manager->pull( 'recording', 'list', $args );
      $recording_data = array();
      if( $recording_list->success == 1 && count( $recording_list->data ) > 0 ) 
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

    $this->set_variable( 'id_1', $record->id );
    //$this->set_variable( 'audio_fault_1', $record->audio_fault );
    $this->set_variable( 'adjudicate_1', $record->adjudicate );
    $this->set_variable( 'deferred_1', $record->deferred );
    $this->set_variable( 'completed_1', $record->completed );
    $this->set_variable( 'user_1', $db_assignment->get_user()->name );

    $this->set_variable( 'id_2', $this->adjudicate_entry->id );
    //$this->set_variable( 'audio_fault_2', $this->adjudicate_entry->audio_fault );
    $this->set_variable( 'adjudicate_2', $this->adjudicate_entry->adjudicate );
    $this->set_variable( 'deferred_2', $this->adjudicate_entry->deferred );
    $this->set_variable( 'completed_2', $this->adjudicate_entry->completed );

    $db_adjudicate_assignment = $this->adjudicate_entry->get_assignment();
    if( empty( $db_adjudicate_assignment ) || is_null( $db_adjudicate_assignment ) )
      throw lib::create( 'exception\runtime',
        'Test entry adjudication requires a valid assignment', __METHOD__ );

    $this->set_variable( 'user_2', $db_adjudicate_assignment->get_user()->name );

    $this->set_variable( 'audio_fault', $record->audio_fault || $this->adjudicate_entry->audio_fault );
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
   * Get the adjudicate test entry sibling.
   * @var test_entry_widget
   * @access protected
   */
  public function get_adjudicate_entry() 
  {
    return $this->adjudicate_entry;
  }
  
  /**
   * The test entry widget.
   * @var test_entry_widget
   * @access protected
   */
  protected $test_entry_widget = NULL;

  /**
   * The adjudicate test entry sibling.
   * @var test_entry_widget
   * @access protected
   */
  protected $adjudicate_entry = NULL;
}
