<?php
/**
 * word_list.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\pull;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * Class for word list pull operations.
 *
 */
class word_list extends \cenozo\ui\pull
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Pull arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'word', 'list', $args );
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

    $dictionary_class_name = lib::get_class_name( 'database\dictionary' );
    $language_class_name = lib::get_class_name( 'database\language' );

    $this->data = array();
    $dictionary = array();
    $dictionary['dictionary_id'] = $this->get_argument( 'dictionary_id', NULL );
    $dictionary['variant_dictionary_id'] = $this->get_argument( 'variant_dictionary_id', NULL );
    $dictionary['intrusion_dictionary_id'] = $this->get_argument( 'intrusion_dictionary_id', NULL );
    $dictionary = array_filter( $dictionary );

    $language_id = $this->get_argument( 'language_id', 0 );
    $db_language = NULL;
    if( 0 < $language_id )
    {
      $db_language = lib::create( 'database\language', $language_id );
    }

    $words_only = $this->get_argument( 'words_only', false );

    $modifier = lib::create( 'database\modifier' );

    $first = true;
    $do_where_bracket = ( 1 < count( $dictionary ) ) && ( !is_null( $db_language ) );
    if( $do_where_bracket )
    {
      $modifier->where_bracket( true );
    }
    foreach( $dictionary as $key => $value )
    {
      if( $first )
      {
        $modifier->where( 'dictionary_id', '=', $value );
        $first = false;
      }
      else
      {
        $modifier->where( 'dictionary_id', '=', $value, true, true );
      }
    }
    if( $do_where_bracket )
    {
      $modifier->where_bracket( false );
    }

    if( !is_null( $db_language ) )
    {
      $modifier->where( 'language_id', '=', $db_language->id );
    }
    if( $words_only )
    {
      $this->data = $dictionary_class_name::get_word_list_words( $modifier );
    }
    else
    {
      $this->data = $dictionary_class_name::get_word_list( $modifier );
    }
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
