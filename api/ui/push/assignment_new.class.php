<?php
/**
 * assignment_new.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: assignment new
 *
 * Create a new assignment.
 */
class assignment_new extends \cenozo\ui\push\base_new
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
   * Processes arguments, preparing them for the operation.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    $assignment_class_name = lib::get_class_name( 'database\assignment' );
    $event_type_class_name = lib::get_class_name( 'database\event_type' );
    $participant_class_name = lib::get_class_name( 'database\participant' );

    $columns = $this->get_argument( 'columns', array() );
    if( empty( $columns ) )
    {
      $this->arguments['columns'] = $columns;
    }

    if( ( !array_key_exists( 'user_id', $columns ) || 0 == strlen( $columns['user_id'] ) ) ||
        ( !array_key_exists( 'participant_id', $columns ) ||
          0 == strlen( $columns['participant_id'] ) ) )
    {
      $session = lib::create( 'business\session' );
      $db_user = $session->get_user();

      // block with a semaphore
      $session->acquire_semaphore();

      $db_participant = $assignment_class_name::get_next_available_participant( $db_user );

      $session->release_semaphore();

      // throw a notice if no participant was found
      if( is_null( $db_participant ) )
        throw lib::create( 'exception\notice',
          'There are currently no participants available for processing.', __METHOD__ );

      $columns['user_id'] = $db_user->id;
      $columns['participant_id'] = $db_participant->id;
      $columns['cohort_name'] = $db_participant->get_cohort()->name;
      $this->arguments['columns'] = $columns;
    }

    parent::prepare();
  }

  /**
   * Finishes the operation with any post-execution instructions that may be necessary.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\runtime
   * @access protected
   */
  protected function finish()
  {
    parent::finish();

    $assignment_manager = lib::create( 'business\assignment_manager' );
    $assignment_manager::initialize_assignment( $this->get_record() );
  }
}
