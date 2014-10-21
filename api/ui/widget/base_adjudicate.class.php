<?php
/**
 * base_adjudicate.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * Base class for all test_entry transcribe widgets
 * @abstract
 */
abstract class base_adjudicate extends \cenozo\ui\widget
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param string $subject The test entry type being transcribed.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'adjudicate', $args );
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
    parent::prepare();

    if( is_null( $this->parent ) )
      throw lib::create( 'exception\runtime', 'This class must have a parent', __METHOD__ );

    $db_test_entry = $this->parent->get_record();
    $heading = $db_test_entry->get_test()->name . ' test adjudicate form';

    if( 'requested' == $db_test_entry->deferred ||
        'pending' == $db_test_entry->deferred )
      $heading = $heading . ' NOTE: this test is currently deferred';

    $this->set_heading( $heading );
  }

  /**
   * Sets up the operation with any pre-execution instructions that may be necessary.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\runtime
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $db_test_entry = $this->parent->get_record();

    $assignment_manager = lib::create( 'business\assignment_manager' );
    $adjudicate_data = $assignment_manager::get_adjudicate_data( $db_test_entry );
    $this->set_variable( 'entry_data', $adjudicate_data['entry_data'] );
    $this->set_variable( 'status_data', $adjudicate_data['status_data'] );
  }
}
