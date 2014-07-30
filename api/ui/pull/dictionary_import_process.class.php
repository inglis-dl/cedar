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

    $dictionary_import_class_name = lib::get_class_name( 'database\dictionary_import' );
    $language_class_name = lib::get_class_name( 'database\language' );
    $word_class_name = lib::get_class_name( 'database\word' );

    $md5 = $this->get_argument( 'md5' );
    $dictionary_id = $this->get_argument( 'dictionary_id' );

    $db_dictionary_import = $dictionary_import_class_name::get_unique_record( 'md5', $md5 );

    if( !is_null( $db_dictionary_import ) && $db_dictionary_import->processed )
      throw lib::create( 'exception\notice',
        'This file has already been imported.', __METHOD__ );

    $file_data = $db_dictionary_import->data;

    if( is_null( $file_data ) )
      throw lib::create( 'exception\notice',
        'There is no file data in the import record.', __METHOD__ );

    $db_dictionary_import->dictionary_id = $dictionary_id;

    // now process the data
    $duplicate_word_count  = 0;
    $unique_word_count     = 0;
    $dictionary_word_count = 0;
    $error_count           = 0;

    $language_mod = lib::create( 'database\modifier' );
    $language_mod->where( 'active', '=', true );
    $language_codes = array();
    foreach( $language_class_name::select( $language_mod ) as $db_language )
    {
      $language_codes[$db_language->id] = $db_language->code;
    }
    if( 0 == count( $language_codes ) )
    {
      $db_language = lib::create( 'business\session' )->get_service()->get_language();
      $language_codes[$db_language->id] = $db_language->code;
    }

    $word_array = array();
    $this->data = array();
    $this->data['id'] = $db_dictionary_import->id;
    $this->data['error_count'] = $error_count;
    $this->data['error_entries'] = array();

    $row = 0;
    $duplicate_input_count = 0;
    foreach( preg_split( '/[\n\r]+/', $file_data ) as $line )
    {
      $row++;

      $row_entry = array_filter( str_getcsv( $line ) );
      $row_entry_count = count( $row_entry );

      if( 2 == $row_entry_count )
      {
        $word = explode( ' ', strtolower( trim( $row_entry[0] ) ) );
        $error = false;

        foreach( $word as $value )
        {
          if( !$word_class_name::is_valid_word( $value ) )
          {
            $this->data['error_entries'][] =
              'Error: invalid word entry "' . $value . '" on line '
              . $row . ': "' . implode( '", "', $row_entry ) . '"';
            $error_count++;
            $error = true;
          }
        }
        if( is_array( $word ) ) $word = implode( ' ', $word );

        $language = strtolower( trim( $row_entry[1] ) );
        if( !in_array( $language, array_values( $language_codes ) ) )
        {
          $this->data['error_entries'][] =
            'Error: invalid language code "' . $language . '" on line '
            . $row . ': "' . implode( '", "', $row_entry ) . '"';
          $error_count++;
          $error = true;
        }

        if( !$error )
        {
          $found = false;
          foreach( $word_array as $key => $value )
          {
            if( $value[0] == $word && $value[1] == $language )
            {
              $duplicate_input_count++;
              $this->data['error_entries'][] =
                'Error: dupicate input entry (ignored) "' . $word . '" on line '
                . $row . ': "' . implode( '", "', $row_entry ) . '"';
              $error_count++;
              $found = true;
            }
          }

          if( !$found )
            $word_array[] = array( $word, $language );
        }
      }
      else
      {
        if( 0 != $row_entry_count )
        {
          $this->data['error_entries'][] =
            'Error: invalid number of elements ( ' . $row_entry_count . ' ) on line '
            . $row . ': "' . implode( '", "', $row_entry ) . '"';
          $error_count++;
        }
      }
    }
    $this->data['error_count'] = $error_count;
    $this->data['duplicate_input_count'] = $duplicate_input_count;

    $unique_word_count = count( $word_array );

    $this->data[ 'dictionary_word_count' ] = 0;
    $word_array_final = $word_array;

    if( 0 < $unique_word_count )
    {
      $db_dictionary = lib::create( 'database\dictionary', $dictionary_id );
      $dictionary_word_count = $db_dictionary->get_word_count();
      $this->data[ 'dictionary_word_count' ] = $dictionary_word_count;
      if( 0 < $dictionary_word_count )
      {
        $unique_word_count = 0;
        $word_array_final = array();
        foreach( $language_codes as $language_id => $code )
        {
          $candidate_words = array();
          foreach( $word_array as $key => $value )
          {
            if( $value[1] == $code )
              $candidate_words[] = $value[0];
          }
          $candidate_word_count = count( $candidate_words );
          if( 0 < $candidate_word_count )
          {
            $dictionary_words = array();
            $modifier = lib::create( 'database\modifier' );
            $modifier->where( 'dictionary_id', '=', $dictionary_id );
            $modifier->where( 'language_id', '=', $language_id );
            foreach( $word_class_name::select( $modifier ) as $db_word )
            {
              $dictionary_words[] = $db_word->word;
            }
            $unique_words = array();
            if( 0 < count( $dictionary_words ) )
            {
              $unique_words = array_diff( $candidate_words, $dictionary_words );
            }
            else
            {
              $unique_words = $candidate_words;
            }
            $unique_count = count( $unique_words );
            if( 0 < $unique_count )
            {
              foreach( $unique_words as $word )
                $word_array_final[] = array( $word, $code );

              $unique_word_count += $unique_count;
              $duplicate_word_count += $candidate_word_count - $unique_count;
            }
          }
        }
      }
    }

    $this->data[ 'duplicate_word_count' ] = $duplicate_word_count;
    $this->data[ 'unique_word_count' ] = $unique_word_count;
    if( 0 < $unique_word_count )
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
