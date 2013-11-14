<?php
/**
 * dictionary_new.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace curry\ui\push;
use cenozo\lib, cenozo\log, curry\util;

/**
 * push: dictionary new
 *
 * Create a new dictionary.
 */
class dictionary_new extends \cenozo\ui\push\base_new
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'dictionary', $args );
  }

  /**
   * Validate the operation.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    // make sure the name column isn't blank
    $columns = $this->get_argument( 'columns' );
    if( !array_key_exists( 'name', $columns ) || 0 == strlen( $columns['name'] ) )
      throw lib::create( 'exception\notice',
        'The dictionary\'s name cannot be left blank.', __METHOD__ );
/*
    if( 0 == $_SERVER['CONTENT_LENGTH'] )
      throw lib::create( 'exception\notice',
        'Tried to import participant data without a valid CSV file.',
        __METHOD__ );
*/
  }

  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
/*
  protected function execute()
  {
    parent::execute();

    // store the data
    $filename = $_SERVER['HTTP_X_FILENAME'];
    if( $filename == '' ) return;
    $data = utf8_encode( file_get_contents( 'php://input' ) );
    if( !is_array( $data ) ) return;

    // now process the data
    $row = 0;

    $record = $this->get_record();

    foreach( preg_split( '/[\n\r]+/', $data ) as $line )
    {
      $values = str_getcsv( $line );
      $row++; 

      // skip header line(s)
      if( 'dictionary_name' == $values[0] || 
          'description' == $values[0] || 
          'words' == $values[0] ) continue;

      if( count( $values ) > 0 )
      {
        $word = strtolower( $values[0] );
        if( count( str_word_count( $word, 1 ) ) > 1 ) continue;
      } 
   
      try
      {
        $record->save();
      }
      catch( \cenozo\exception\database $e )
      {
        throw lib::create( 'exception\notice',
          sprintf( 'There was a problem importing row %d.', $row ), __METHOD__, $e );
      }
    }
  }
*/
}
