<?php
/**
 * dictionary_import_new.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: dictionary import words
 *
 * Import words into a dictionary.
 */
class dictionary_import_new extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'dictionary_import', 'new', $args );
  }

  /** 
   * Validate the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    if( 0 == $_SERVER['CONTENT_LENGTH'] )
      throw lib::create( 'exception\notice',
        'Tried to import dictionary data without a valid CSV file.',
        __METHOD__ );
  }

  /**
   * This method executes the operation's purpose.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $dictionary_import_class_name = lib::get_class_name( 'database\dictionary_import' );

    $data = file_get_contents( 'php://input' );
    $md5 = md5( utf8_encode( $data ) );  

    $db_dictionary_import = $dictionary_import_class_name::get_unique_record( 'md5', $md5 );
    if( !is_null( $db_dictionary_import ) )
    {
      if( $db_dictionary_import->processed )
       throw lib::create( 'exception\notice',
         'This file has already been imported.', __METHOD__ );
    }
    else
    {
      $db_dictionary_import = lib::create( 'database\dictionary_import' );
    }

    $db_dictionary_import->processed = false;
    $db_dictionary_import->data = $data;
    $db_dictionary_import->md5 = $md5;
    $db_dictionary_import->save();
  }
}
