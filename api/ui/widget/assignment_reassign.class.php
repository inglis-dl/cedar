<?php
/**
 * assignment_reassign.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget assignment view
 */
class assignment_reassign extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'assignment', 'reassign', $args );
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

    $operation_class_name = lib::get_class_name( 'database\operation' );

    // add items to the view
    $this->add_item( 'uid', 'constant', 'UID' );
    $this->add_item( 'cohort', 'constant', 'Cohort' );
    $this->add_item( 'user1', 'constant', 'User 1' );
    $this->add_item( 'user2', 'constant', 'User 2' );

    $db_operation = $operation_class_name::get_operation( 'push', 'assignment', 'reassign' );
    if( lib::create( 'business\session' )->is_allowed( $db_operation ) )
    {
      $this->add_action( 'reassign', 'Reassign', $db_operation,
        'Reassign this participant\'s assignments to typists '.
        'with no language restrictions' );
    }

    $this->set_heading( 'Reassign Assignment' );
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

    $db_assignment = $this->get_record();

    // set the view's items
    $db_participant = $db_assignment->get_participant();
    $this->set_item( 'uid', $db_participant->uid, true );
    $this->set_item( 'cohort', $db_participant->get_cohort()->name, true );

    $user_ids = $db_assignment->get_reassign_user();
    for( $i = 1; $i <= 2; $i++ )
    {
      $db_user = lib::create( 'database\user', $user_ids[ $i-1 ] );
      $this->set_item( 'user'.$i, $db_user->name, true );
    }
  }
}
