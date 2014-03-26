<?php
/**
 * test_entry_add.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget test_entry add
 */
class test_entry_add extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'test_entry', 'add', $args );
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
    $this->add_item( 'test_id', 'enum', 'Test' );
    $this->add_item( 'assignment_id', 'hidden' );
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
    
    $test_class_name = lib::get_class_name( 'database\test' );

    // this widget must have a parent, and it's subject must be an assignment
    if( is_null( $this->parent ) || 'assignment' != $this->parent->get_subject() )
      throw lib::create( 'exception\runtime',
        'Test entry widget must have a parent with assignment as the subject.', __METHOD__ );
    
    $test_list = array();
    foreach( $test_class_name::select() as $db_test )
      $test_list[$db_test->id] = $db_test->name;

    // set the view's items
    $this->set_item( 'test_id', key( $test_list ), true, $test_list );
    $this->set_item( 'assignment_id', $this->parent->get_record()->id );
  }
}
