<?php
/**
 * dictionary_import.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace curry\ui\push;
use cenozo\lib, cenozo\log, curry\util;

/**
 * push: dictionary import words
 *
 * Import words into a dictionary.
 */
class dictionary_import extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'dictionary', 'import', $args );
    //call a log
    log::debug( $this->arguments );
    die();     
  }


  /** 
   * Prepare the operation.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    if( 0 == $_SERVER['CONTENT_LENGTH'] )
      throw lib::create( 'exception\notice',
        'Tried to import participant data without a valid CSV file.',
        __METHOD__ );
    
    $filename = $_SERVER['HTTP_X_FILENAME'];
    $file_data = utf8_encode( file_get_contents( 'php://input' ) );

    // now process the data
    $row = 1;
    $this->word_array = array();
    foreach( preg_split( '/[\n\r]+/', $file_data ) as $line )
    {
      $values = str_getcsv( $line );

      if(  1 == count( $values ) )
      {
        $word = strtolower( $values[0] );
        if( count( str_word_count( $word, 1 ) ) > 1 ) 
        {   
          throw lib::create( 'exception\notice',
            'Multiple word entries are not allowed.', __METHOD__ );
        }
        if( preg_match( '#[0-9]#', $word ) )
        {   
          throw lib::create( 'exception\notice',
            'Not a valid word: numbers are not allowed.', __METHOD__ );
        }
        $this->word_array[] = $word;
      }
      $row++;
    }
    if( !empty( $this->word_array ) )
    {
      $this->word_array = array_unique( $this->word_array, SORT_STRING );
    }
  }

  /**
   * Validate the operation.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();
    

   // array_subtract
    // get all the words in the target dictionary
    // 
    $dictionary_class_name = lib::get_class_name( 'database\dictionary' );
 

  }

  protected $word_array;

}
