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
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    // add items to the view
    $this->add_item( 'uid', 'constant', 'UId' );
    $this->add_item( 'cohort', 'constant', 'Cohort' );
    $this->add_item( 'language', 'constant', 'Language' );
    $this->add_item( 'test_id', 'constant', 'Test' );
    $this->add_item( 'audio_fault', 'boolean', 'Audio Fault' );
    $this->add_item( 'deferred', 'boolean', 'Deferred' );
    $this->add_item( 'note', 'constant', 'Deferral Note' );
    $this->add_item( 'completed', 'boolean', 'Completed' );
    $this->add_item( 'adjudicate', 'constant', 'Adjudicate' );
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

    $record = $this->get_record();
    $db_assignment = $record->get_assignment();
    $db_test = $record->get_test();
    $db_participant = $db_assignment->get_participant();

    // set the view's items
    $this->set_item( 'uid', $db_participant->uid );
    $this->set_item( 'cohort', $db_participant->get_cohort()->name );
    $this->set_item( 'language', 
      is_null( $db_participant->language ) ? 'en' : $db_participant->language );
    $this->set_item( 'test_id', $db_test->name );
    $this->set_item( 'audio_fault', $record->audio_fault );
    $this->set_item( 'deferred', $record->deferred  );
    $this->set_item( 'note', $record->note  );
    $this->set_item( 'completed', $record->completed );
    $this->set_item( 'adjudicate', $record->adjudicate ? 'Yes' : 'No' );
  }
}
