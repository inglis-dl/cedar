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
    $this->add_item( 'test_entry', 'string', 'Word' );
    $this->add_item( 'language', 'string', 'Language' );
    $this->add_item( 'dictionary', 'string', 'Dictionary' );
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

    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );
    $languages = $test_entry_class_name::get_enum_values( 'language' );
    $languages = array_combine( $languages, $languages );
    $db_test_entry = $this->get_record();

    // set the view's items
    $this->set_item( 'test_entry', $db_test_entry->test_entry, true );
    $this->set_item( 'language', $db_test_entry->language, false, $languages );
    $this->set_item( 'dictionary', $db_test_entry->get_dictionary()->name, true );
  }
}
