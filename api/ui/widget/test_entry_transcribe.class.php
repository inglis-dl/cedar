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
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $record = $this->get_record();
    $test_class_name = lib::get_class_name('database\test');
    $test_count = $test_class_name::count();

    $db_test = $record->get_test();
    $rank = $db_test->rank;
    $this->set_heading( $db_test->name . ' transcription for ' .  
      $record->get_assignment()->get_participant()->uid . 
      ' ( test ' . $rank . ' / ' . $test_count . ' tests )'  );
    $this->set_variable( 'audio_fault', $record->audio_fault );
    $this->set_variable( 'deferred', $record->deferred );
    $this->set_variable( 'rank', $rank );

    $db_test_type = $db_test->get_test_type();

    // find the ids of the prev and next test_entrys
    $db_prev_test_entry = $record->get_previous();
    $db_next_test_entry = $record->get_next();

    $this->set_variable( 'prev_test_entry_id', 
      is_null($db_prev_test_entry) ? 0 : $db_prev_test_entry->id );

    $this->set_variable( 'next_test_entry_id', 
      is_null($db_next_test_entry) ? 0 : $db_next_test_entry->id );
  }
}
