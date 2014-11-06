<?php
/**
 * test_entry_view.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget test_entry view
 */
class test_entry_view extends \cenozo\ui\widget\base_view
{
  /**
   * Constructor
   *
   * Defines all variables which need to be set for the associated template.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry', 'view', $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\runtime
   * @access protected
   */
  protected function prepare()
  {
    $this->set_removable( false );

    parent::prepare();

    $db_test_entry = $this->get_record();

    $is_deferred = in_array( $db_test_entry->deferred, array( 'requested', 'pending' ) );

    // add items to the view
    $this->add_item( 'participant.uid', 'constant', 'UID' );
    $this->add_item( 'cohort.name', 'constant', 'Cohort' );
    $this->add_item( 'language.name', 'constant', 'Language' );
    $this->add_item( 'user.name', 'constant', 'Typist' );
    $this->add_item( 'test.name', 'constant', 'Test' );
    $this->add_item( 'test.name', 'constant', 'Test' );
    $this->add_item( 'audio_status',
      $is_deferred ? 'constant' : 'enum', 'Audio Status' );
    $this->add_item( 'participant_status',
      $is_deferred ? 'constant' : 'enum', 'Participant Status' );
    $this->add_item( 'deferred',
      $is_deferred ? 'enum' : 'constant', 'Deferred' );
    $this->add_item( 'completed', 'constant', 'Completed' );
    $this->add_item( 'adjudicate', 'constant', 'Adjudicate' );

    // create the test_entry_transcribe sub widget
    if( 'typist' ==  lib::create( 'business\session' )->get_role()->name )
      throw lib::create( 'exception\runtime',
        'Only administrators and supervisors can view transcriptions within a test_entry_view',
        __METHOD__ );

    $this->test_entry_transcribe = lib::create( 'ui\widget\test_entry_transcribe',
      array( 'test_entry_transcribe' => array( 'id' => $this->get_argument( 'id' ) ) ) );
    $this->test_entry_transcribe->set_parent( $this );
    $this->test_entry_transcribe->set_validate_access( false );
    $this->test_entry_transcribe->set_editable( false );
    $this->test_entry_transcribe->set_actionable( false );
  }

  /**
   * Finish setting the variables in a widget.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );

    $db_test_entry = $this->get_record();
    $db_assignment = $db_test_entry->get_assignment();
    $db_test = $db_test_entry->get_test();
    $db_participant = $db_assignment->get_participant();

    $db_language = $db_test_entry->get_default_participant_language();

    // set the view's items
    $this->set_item( 'participant.uid', $db_participant->uid );
    $this->set_item( 'cohort.name', $db_participant->get_cohort()->name );
    $this->set_item( 'language.name', $db_language->name );
    $this->set_item( 'user.name', $db_assignment->get_user()->name );
    $this->set_item( 'test.name', $db_test->name );

    $is_deferred = in_array( $db_test_entry->deferred, array( 'requested', 'pending' ) );

    if( $is_deferred )
    {
      $this->set_item( 'audio_status', $db_test_entry->audio_status );
    }
    else
    {
      $audio_status_list = $test_entry_class_name::get_enum_values( 'audio_status' );
      $audio_status_list = array_combine( $audio_status_list, $audio_status_list );
      $audio_status_list = array_reverse( $audio_status_list, true );
      $audio_status_list['NULL'] = '';
      $audio_status_list = array_reverse( $audio_status_list, true );
      $this->set_item( 'audio_status',
        $db_test_entry->audio_status, true, $audio_status_list );
    }

    if( $is_deferred )
    {
      $this->set_item( 'participant_status', $db_test_entry->participant_status );
    }
    else
    {
      $participant_status_list = $test_entry_class_name::get_enum_values( 'participant_status' );
      $participant_status_list = array_combine( $participant_status_list, $participant_status_list );
      $participant_status_list = array_reverse( $participant_status_list, true );
      $participant_status_list['NULL'] = '';
      $participant_status_list = array_reverse( $participant_status_list, true );

      // only classification tests (FAS and AFT) require prompt status
      if( 'classification' != $db_test->get_test_type()->name )
      {
        unset( $participant_status_list['suspected prompt'],
               $participant_status_list['prompted'] );
      }

      $this->set_item( 'participant_status',
        $db_test_entry->participant_status, true, $participant_status_list );
    }

    if( $is_deferred )
    {
      $deferred_list = $test_entry_class_name::get_enum_values( 'deferred' );
      $deferred_list = array_combine( $deferred_list, $deferred_list );
      unset( $deferred_list['resolved'] );
      $this->set_item( 'deferred',
        $db_test_entry->deferred, true, $deferred_list );
    }
    else
      $this->set_item( 'deferred',
        is_null( $db_test_entry->deferred ) ? 'No' : ucwords( $db_test_entry->deferred ) );

    $this->set_item( 'completed', $db_test_entry->completed ? 'Yes' : 'No' );
    $this->set_item( 'adjudicate',
      is_null( $db_test_entry->adjudicate ) || !$db_test_entry->adjudicate ? 'No' : 'Yes' );

    try
    {
      $this->test_entry_transcribe->process();
      $this->set_variable( 'test_entry_transcribe', $this->test_entry_transcribe->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}
  }

  /**
   * The test_entry_transcribe widget.
   * @var test_entry_transcribe
   * @access protected
   */
  protected $test_entry_transcribe = NULL;
}
