<?php
/**
 * dictionary_import_process.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\pull;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * pull: dictionary import
 *
 * Import new words into a dictionary.
 */
class dictionary_import_process extends \cenozo\ui\pull
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'dictionary_import', 'process', $args );
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
    
    $md5 = $this->get_argument( 'md5' );
    $dictionary_id = $this->get_argument( 'dictionary_id' );

    $dictionary_import_class_name = lib::get_class_name( 'database\dictionary_import' );
    $db_dictionary_import = $dictionary_import_class_name::get_unique_record( 'md5', $md5 );    
    if( !is_null( $db_dictionary_import ) )
    {   
      if( $db_dictionary_import->processed )
       throw lib::create( 'exception\notice',
         'This file has already been imported.', __METHOD__ );
    }
    $file_data = $db_dictionary_import->data;
    if( is_null( $file_data ) )
    {
      throw lib::create( 'exception\notice',
       'There is no file data in the import record.', __METHOD__ );
    }
    $db_dictionary_import->dictionary_id = $dictionary_id;

    // now process the data
    $word_array = array();
    $duplicate_word_count = 0;
    $unique_word_count = 0;
    $dictionary_word_count = 0;
    $error_count = 0;

    $word_class_name = lib::get_class_name( 'database\word' );
    $languages = $word_class_name::get_enum_values( 'language' );
    
    $this->data = array();
    $this->data[ 'id' ] = $db_dictionary_import->id;
    $this->data['error_count'] = $error_count;
    $this->data['error_entries'] = array();

    $row = 0;
    foreach( preg_split( '/[\n\r]+/', $file_data ) as $line )
    {
      $row++;
      
      $row_entry = array_filter( str_getcsv( $line ) );
      $row_entry_count = count( $row_entry );

      if( 2 == $row_entry_count ) 
      {   
        $word = strtolower( $row_entry[0] );
        $error = false;

        if( preg_match( '#[0-9]#', $word ) ) 
        {
          $this->data['error_entries'][] = 
            'Error: invalid word entry "' . $word . '" on line ' 
            . $row . ': "' . implode( '", "', $row_entry ) . '"';
          $error_count++;
          $error = true;
        }
        $language = strtolower( $row_entry[1] );
        if( !in_array( $language, $languages ) )
        {
          $this->data['error_entries'][] = 
            'Error: invalid language code "' . $language . '" on line ' 
            . $row . ': "' . implode( '", "', $row_entry ) . '"';
          $error_count++;  
          $error = true;
        }

        if( !$error ) $word_array[] = array( $word, $language );
      }
      else
      {
        if( $row_entry_count != 0 )
        {
          $this->data['error_entries'][] =
            'Error: invalid number of elements ( ' . $row_entry_count . ' ) on line ' 
            . $row . ': "' . implode( '", "', $row_entry ) . '"';
          $error_count++;  
        }
      }  
    }
    $this->data['error_count'] = $error_count;

    $unique = array_unique( $word_array, SORT_REGULAR );
    $word_array = array();
    foreach( $unique as $key => $value )
    {
      $word_array[$value[0]] = $value[1];
    }

    $unique_word_count = count( $word_array );

    $this->data[ 'dictionary_word_count' ] = 0;
    $word_array_final = $word_array;

    if( $unique_word_count > 0 ) 
    {      
      $db_dictionary = lib::create( 'database\dictionary', $dictionary_id );
      $dictionary_word_count = $db_dictionary->get_word_count();
      $this->data[ 'dictionary_word_count' ] = $dictionary_word_count;
      if( $dictionary_word_count > 0 )
      {
        $word_class_name = lib::get_class_name( 'database\word' );
        $languages = $word_class_name::get_enum_values( 'language' );
        $unique_word_count = 0;
        $word_array_final = array();
        foreach( $languages as $language )
        {
          $candidate_words = array_keys( $word_array, $language );
          $candidate_word_count = count( $candidate_words ); 
          if( $candidate_word_count > 0 )
          {
            $dictionary_words = array();
            $modifier = lib::create( 'database\modifier' );
            $modifier->where( 'word.dictionary_id', '=', $id );
            $modifier->where( 'word.language', '=', $language );
            foreach( $word_class_name::select( $modifier ) as $db_word )
            {   
              $dictionary_words[] = $db_word->word;
            }

            if( count( $dictionary_words ) > 0 )
            {
              $unique_words = array_diff( $candidate_words, $dictionary_words );
              $unique_count = count( $unique_words );
              if( $unique_count > 0 )
              {
                $language_values = array_fill( 0, $unique_count, $language );
                $word_array_final[] = array_combine( $unique_words, $language_values );
                $unique_word_count += $unique_count;
                $duplicate_word_count += $candidate_word_count - $unique_count;
              }
            }  
          }          
        }
      }
    }

    $this->data[ 'duplicate_word_count' ] = $duplicate_word_count;
    $this->data[ 'unique_word_count' ] = $unique_word_count;
    if( $unique_word_count > 0 )
    {
      $db_dictionary_import->serialization = util::json_encode( $word_array_final );
    }

    $db_dictionary_import->save();
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
