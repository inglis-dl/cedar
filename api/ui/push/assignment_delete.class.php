<?php
/**
 * assignment_delete.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: assignment delete
 *
 * Delete an assignment.
 */
class assignment_delete extends \cenozo\ui\push\base_delete
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'assignment', $args );
  }

  /**
   * Validate the operation.  If validation fails this method will throw a notice exception.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    // an assignment cannot be deleted if it is closed
    $db_assignment = $this->get_record();
    if( !is_null( $db_assignment->end_datetime ) )
      throw lib::create( 'exception\notice',
        'The assignment is closed and cannot be deleted', __METHOD__ );

    $activity_class_name = lib::get_class_name( 'database\activity' );

    // an assignment cannot be deleted if it has seen editing activities within the retention time
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'user_id', '=', $db_assignment->user_id );
    $modifier->where( 'site_id','=', $db_assignment->site_id );
    $modifier->where( 'operation.subject', 'LIKE', 'test_entry_%' );
    $modifier->order( 'datetime', true );
    $modifier->limit( 1 );
    $db_activity = current( $activity_class_name::select( $modifier ) );
    if( false !== $db_activity )
    {
      $date = util::get_datetime_object( $db_activity->datetime );
      $now = util::get_datetime_object();
      $hours = $date->diff( $now )->format( '%h' );
      $setting_manager = lib::create( 'business\setting_manager' );
      $retention_time = $setting_manager->get_setting( 'interface', 'assignment_retention_time' );
      if( $hours < $retention_time )
        throw lib::create( 'exception\notice',
          'The assignment cannot be deleted until the retention time has expired. '.
          'Try again in '. ($retention_time - $hours) . ' hours.' , __METHOD__ );
    }
  }

  /**
   * Purge auxiliary records and reset the adjudicate states of the
   * sibling assignment's test_entry records.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $db_assignment = $this->get_record();
    $assignment_manager = lib::create( 'business\assignment_manager' );
    $assignment_manager->purge_assignment( $db_assignment );
  }
}
