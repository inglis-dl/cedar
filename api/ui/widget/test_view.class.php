<?php
/**
 * test_view.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

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

    $this->add_item( 'name', 'constant', 'Name' );
    $this->add_item( 'dictionary_id', 'enum', 'Primary Dictionary' );
    if( !$this->get_record()->strict )
    {
      $this->add_item( 'variant_dictionary_id', 'enum', 'Variant Dictionary' );
      $this->add_item( 'intrusion_dictionary_id', 'enum', 'Intrusion Dictionary' );
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

    $record = $this->get_record();
    $this->set_variable( 'test_id', $record->id );

    // set the view's items
    $this->set_item( 'name', $record->name, true );

    $dictionary_class_name = lib::get_class_name( 'database\dictionary' );

    $dictionary_list = array();
    foreach( $dictionary_class_name::select() as $db_dictionary )
       $dictionary_list[$db_dictionary->id] = $db_dictionary->name;

    $this->set_item( 'dictionary_id', $record->dictionary_id, false, $dictionary_list );
    if( !$record->strict )
    {
      $this->set_item( 'variant_dictionary_id', 
        $record->variant_dictionary_id, false, $dictionary_list );
      $this->set_item( 'intrusion_dictionary_id', 
        $record->intrusion_dictionary_id, false, $dictionary_list );
    }  
  }
}
