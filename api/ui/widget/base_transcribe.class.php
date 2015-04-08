<?php
/**
 * base_transcribe.class.php
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
abstract class base_transcribe extends \cenozo\ui\widget
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
    parent::__construct( $subject, 'transcribe', $args );
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
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );

    if( is_null( $this->parent ) )
      throw lib::create( 'exception\runtime', 'This class must have a parent', __METHOD__ );

    $db_test_entry = $this->parent->get_record();
    $heading = $db_test_entry->get_test()->name . ' test entry form';

    if( in_array( $db_test_entry->deferred, $test_entry_class_name::$deferred_states ) )
      $heading = $heading . ' NOTE: this test is currently deferred';

    $this->set_heading( $heading );
  }
}
