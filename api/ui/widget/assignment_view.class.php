<?php
/**
 * assignment_view.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget assignment view
 */
class assignment_view extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'assignment', 'view', $args );
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
    $this->add_item( 'site', 'constant', 'Site' );
    $this->add_item( 'user', 'constant', 'User' );

    // create the test_entry sub-list widget
    $this->test_entry_list = lib::create( 'ui\widget\test_entry_list', $this->arguments );
    $this->test_entry_list->set_parent( $this );
    $this->test_entry_list->set_heading( 'Tests' );

    $db_operation = $operation_class_name::get_operation( 'widget', 'assignment', 'reassign' );
    if( lib::create( 'business\session' )->is_allowed( $db_operation ) )
    {
      $db_assignment = $this->get_record();
      if( !is_null( $db_assignment->get_sibling_assignment() ) &&
          2 == count( $db_assignment->get_reassign_user() ) )
      {
        $this->add_action( 'reassign', 'Reassign', $db_operation,
          'Reassign this participant\'s assignments to typists '.
          'with multiple language restrictions' );
      }
    }
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
    $this->set_item( 'user', $db_assignment->get_user()->name, true );

    try
    {
      $this->test_entry_list->process();
      $this->set_variable( 'test_entry_list', $this->test_entry_list->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}
  }

  /**
   * The test_entry list widget.
   * @var test_entry_list
   * @access protected
   */
  protected $test_entry_list = NULL;
}
