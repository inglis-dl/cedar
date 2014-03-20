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

    // create the test_entry sub widget
    // example: widget class test_entry_ranked_word_transcribe
    $this->test_entry_widget = lib::create( 
      'ui\widget\test_entry_' . $db_test->get_test_type()->name . '_transcribe', $this->arguments );
    $this->test_entry_widget->set_parent( $this );
    if( !$this->editable )
      $this->test_entry_widget->set_validate_access( false );

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

    /$session = lib::create( 'business\session' );

    // determine whether the typist is on a break
    $away_time_mod = lib::create( 'database\modifier' );
    $away_time_mod->where( 'end_datetime', '=', NULL );
    $this->set_variable( 'on_break',
    0 < $session->get_user()->get_away_time_count( $away_time_mod ) );

    $record = $this->get_record();
    $db_test = $record->get_test();
    $test_type_name = $db_test->get_test_type()->name;

    $this->set_variable( 'audio_fault', $record->audio_fault );
    $this->set_variable( 'deferred', $record->deferred );
    $this->set_variable( 'completed', $record->completed );
    $this->set_variable( 'rank', $db_test->rank );
    $this->set_variable( 'test_type', $test_type_name );
    $this->set_variable( 'test_id', $db_test->id );

    $dictionary_id = 0;
    $variant_dictionary_id = 0;
    $intrusion_dictionary_id = 0;

    $db_dictionary = $db_test->get_dictionary();
    if( !is_null( $db_dictionary ) )
      $dictionary_id = $db_dictionary->id;

    if( !preg_match( '/FAS/', $db_test->name ) )
    {
      $db_variant_dictionary = $db_test->get_variant_dictionary();
      if( !is_null( $db_variant_dictionary ) )
        $variant_dictionary_id = $db_variant_dictionary->id;
    }
    
    $db_intrusion_dictionary = $db_test->get_intrusion_dictionary();
    if( !is_null( $db_intrusion_dictionary ) )
      $intrusion_dictionary_id = $db_intrusion_dictionary->id;

    $this->set_variable( 'dictionary_id', $dictionary_id );
    $this->set_variable( 'variant_dictionary_id', $variant_dictionary_id );
    $this->set_variable( 'intrusion_dictionary_id', $intrusion_dictionary_id );

    $language = 'any';
    $db_participant = $record->get_assignment()->get_participant();
    if( is_null( $db_participant ) )
      throw lib::create( 'exception\runtime', 
        'The participant id must be set', __METHOD__ );
    
    $language = is_null( $db_participant->language ) ? 'any' : $db_participant->language;
    $this->set_variable( 'language', $language );    
    
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
      if( !is_null( $recording_list) && 
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
 
    // find the ids of the prev and next test_entrys
    $db_prev_test_entry = $record->get_previous();
    $db_next_test_entry = $record->get_next();

    $this->set_variable( 'prev_test_entry_id', 
      is_null($db_prev_test_entry) ? 0 : $db_prev_test_entry->id );

    $this->set_variable( 'next_test_entry_id', 
      is_null($db_next_test_entry) ? 0 : $db_next_test_entry->id );

    $this->set_variable( 'editable', $this->editable ? 1 : 0 );
    $this->set_variable( 'actionable', $this->actionable ? 1 : 0 );
    
    try 
    {   
      $this->test_entry_widget->process();
      $this->set_variable( 'test_entry_args', $this->test_entry_widget->get_variables() );
    }   
    catch( \cenozo\exception\permission $e ) {}
  }

  /** 
   * Determines whether the record can be edited.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   */
  public function get_editable()
  {
    return $this->editable;
  }

  /** 
   * Determines whether the action buttons are enabled.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   */
  public function get_actionable()
  {
    return $this->actionable;
  }

  /** 
   * Set whether the record can be edited.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   */
  public function set_editable( $enable )
  {
    $this->editable = $enable;
  }

  /** 
   * Set whether the action buttons are enabled.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   */
  public function set_actionable( $enable )
  {
    $this->actionable = $enable;
  }

  /**
   * Determines whether the html input elements are enabled.
   * @var boolean
   * @access private
   */
  private $editable = true;

  /**
   * Determines whether the action buttons should be available.
   * @var boolean
   * @access private
   */
   private $actionable = true;

  /**
   * The test entry widget.
   * @var test_entry_widget
   * @access protected
   */
  protected $test_entry_widget = NULL;
}
