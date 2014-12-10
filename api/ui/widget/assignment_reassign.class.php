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

    $this->set_editable( true );

    // add items to the view
    $this->add_item( 'uid', 'constant', 'UID' );
    $this->add_item( 'cohort', 'constant', 'Cohort' );
    $this->add_item( 'site', 'constant', 'Site' );
    $this->add_item( 'current_user', 'constant', 'Current User' );
    $this->add_item( 'user_id', 'enum', 'New User' );

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
    $this->set_item( 'site', $db_assignment->get_site()->name, true );
    $this->set_item( 'current_user', $db_assignment->get_user()->name, true );

    // NOTE: this widget is parented by the assignment_view widget.
    $user_list = $db_assignment->get_reassign_user();
    $user_list['NULL'] = '';
    $this->set_item( 'user_id', '', true, $user_list, true );
  }
}
