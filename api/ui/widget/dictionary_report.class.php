<?php
/**
 * dictionary_report.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log;

/**
 * widget dictionary report
 */
class dictionary_report extends \cenozo\ui\widget\base_report
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
    parent::__construct( 'dictionary', $args );
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

    $this->add_parameter( 'dictionary_id', 'enum', 'Dictionary' );
    $this->set_variable( 'description',
      'This report provides a list of the words contained in a dictionary.' );
  }

  /**
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $dictionary_class_name = lib::get_class_name( 'database\dictionary' );

    // create the enum lists
    $dictionary_list = $dictionary_class_name::select();
    $dictionaries = array();
    foreach( $dictionary_list as $db_dictionary )
      $dictionaries[$db_dictionary->id] = $db_dictionary->name;

    $this->set_parameter( 'dictionary_id', key( $dictionaries ), true, $dictionaries );
  }
}
