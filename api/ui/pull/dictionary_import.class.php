<?php
/**
 * dictionary_import.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace curry\ui\pull;
use cenozo\lib, cenozo\log, curry\util;

/**
 * pull: dictionary import
 *
 * Import new words into a dictionary.
 */
class dictionary_import extends \cenozo\ui\pull
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
    
    $file_data = $this->get_argument('file_data');

    // now process the data
    $word_array = array();
    $multiple_word_count = 0;
    $invalid_word_count = 0;
    $duplicate_word_count = 0; 
    $unique_word_count = 0;

    log::debug( $file_data );
    die();
    
    foreach( preg_split( '/[\n\r]+/', $file_data ) as $line )
    {   
      $values = str_getcsv( $line );

      if( 1 == count( $values ) ) 
      {   
        $word = strtolower( $values[0] );
        if( count( str_word_count( $word, 1 ) ) > 1 ) 
        {
           $multiple_word_count++;
           continue;
        }
        if( preg_match( '#[0-9]#', $word ) ) 
        {
          $invalid_word_count++;
          continue;
        }   
        $word_array[] = $word;
      }   
    }

    $word_array = array_unique( array_filter( $word_array ), SORT_STRING );

    $this->data = array();
    $this->data[ 'multiple_word_count' ] = $multiple_word_count;
    $this->data[ 'invalid_word_count' ] = $invalid_word_count;
    $this->data[ 'duplicate_word_count' ] = $duplicate_word_count;
    $this->data[ 'unique_word_count' ] = $unique_word_count;
    $this->data[ 'unique_word_entries' ] = '';

    if( count( $word_array ) > 0 ) 
    {
      $id = $this->get_argument( 'id' );
      $db_dictionary = lib::create( 'database\dictionary', $id ); 
      if( $db_dictionary->get_word_count() > 0 )
      {
        $words_en = array();
        $words_fr = array();

        // loop through all the words
        $word_mod_en = lib::create( 'database\modifier' );
        $word_mod_en->where( 'word.dictionary_id', '=', $id );
        $word_mod_en->where( 'word.language', '=', 'en' );
        foreach( $word_class_name::select( $word_mod_en ) as $db_word )
        {   
          $words_en[] = $db_word->word;
        } 

        $word_mod_fr = lib::create( 'database\modifier' );
        $word_mod_fr->where( 'word.dictionary_id', '=', $id );
        $word_mod_fr->where( 'word.language', '=', 'fr' );
        foreach( $word_class_name::select( $word_mod_fr ) as $db_word )
        {   
          $words_fr[] = $db_word->word;
        } 


        $this->data['existing en']=$words_en;
        $this->data['existing fr']=$words_fr;

        $word_array_unique = array_diff( $word_array, $word_list );
        $unique_word_count = count( $word_array_unique );
        $duplicate_word_count = count( $word_array ) - $unique_word_count;
        $this->data[ 'duplicate_word_count' ] = $duplicate_word_count;
        $this->data[ 'unique_word_count' ] = $unique_word_count;
        $this->data[ 'unique_word_entries' ] = $word_array_unique;
      }
    }
    log::debug( $this->data );
    die();
  }
  
  /** 
   * Data returned in JSON format.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_data_type()
  { 
    return "json";
  }
}
