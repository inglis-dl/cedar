<?php
/**
 * dictionary_import.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget dictionary import
 *
 * Import words into a dictionary.
 */
class dictionary_import extends \cenozo\ui\widget\base_record
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'dictionary', 'import', $args );
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

    $dictionary_import_class_name = lib::get_class_name( 'database\dictionary_import' );

    $md5 = $this->get_argument( 'md5', false );
    $this->set_variable( 'md5', $md5 );
    if( $md5 )
    {
      // get the import file matching the md5 hash
      $db_dictionary_import = $dictionary_import_class_name::get_unique_record( 'md5', $md5 );
      if( is_null( $db_dictionary_import ) )
        throw lib::create( 'exception\argument', 'md5', $md5, __METHOD__ );
      $this->set_variable( 'dictionary_import_id', $db_dictionary_import->id );
    }
  }
}
