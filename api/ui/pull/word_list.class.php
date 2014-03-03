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
    
    $this->data = array();
    $dictionary = array();
    $dictionary['dictionary_id'] = $this->get_argument( 'dictionary_id', NULL );
    $dictionary['variant_dictionary_id'] = $this->get_argument( 'variant_dictionary_id', NULL );
    $dictionary['intrusion_dictionary_id'] = $this->get_argument( 'intrusion_dictionary_id', NULL );
    
    $dictionary = array_filter( $dictionary );

    $language = $this->get_argument( 'language', 'any' );
    $words_only = $this->get_argument( 'words_only', false );

    $dictionary_class_name = lib::get_class_name( 'database\dictionary' );
    $modifier = lib::create( 'database\modifier' );

    $first = true;
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

    if( 'any' != $language ) $modifier->where( 'language', '=', $language );
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
