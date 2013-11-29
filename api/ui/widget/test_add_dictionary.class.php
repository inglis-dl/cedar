<?php
/**
 * test_add_dictionary.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget test add_dictionary
 */
class test_add_dictionary extends \cenozo\ui\widget\base_add_record
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param string $name The name of the dictionary.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test', 'dictionary', $args );
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

    // define all columns defining this record
    $this->add_item( 'dictionary_id', 'enum', 'Primary Dictionary' );
    $this->add_item( 'variant_dictionary_id', 'enum', 'Variant Dictionary' );
    $this->add_item( 'intrusion_dictionary_id', 'enum', 'Intrusion Dictionary' );
  }

  /** 
   * Defines all items in the view.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();
    
    // create enum arrays
    $dictionary_class_name = lib::get_class_name( 'database\dictionary' );
    foreach( $dictionary_class_name::select() as $db_dictionary )
      $dictionary_list[$db_dictionary->id] = $db_dictionary->name;

    // set the view's items
    $this->set_item( 'dictionary_id', key( $dictionary_list ), true, $dictionary_list );
    $this->set_item( 'variant_dictionary_id', key( $dictionary_list ), true, $dictionary_list );
    $this->set_item( 'intrusion_dictionary_id', key( $dictionary_list ), true, $dictionary_list );
  }
}
