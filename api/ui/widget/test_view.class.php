<?php
/**
 * test_view.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace curry\ui\widget;
use cenozo\lib, cenozo\log, curry\util;

/**
 * widget test view
 */
class test_view extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'test', 'view', $args );
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

    // create an associative array with everything we want to display about the test
    $this->add_item( 'name', 'string', 'Name' );

    // create the dictionary sub-list widget
    $this->dictionary_list = lib::create( 'ui\widget\dictionary_list', $this->arguments );
    $this->dictionary_list->set_parent( $this );
    $this->dictionary_list->set_heading( 'Dictionaries' );
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

    // set the view's items
    $db_test = $this->get_record();
    $this->set_item( 'name', $db_test->name, true );

    $this->set_variable( 'test_id', $db_test->id );

    try 
    {   
      $this->dictionary_list->process();
      $this->set_variable( 'dictionary_list', $this->dictionary_list->get_variables() );
    }   
    catch( \cenozo\exception\permission $e ) {}
  }

  /** 
   * The test list widget.
   * @var dictionary_list
   * @access protected
   */
  protected $dictionary_list = NULL;
}
