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
    
    $dictionary_id = $this->get_argument( 'dictionary_id' );
    $language = $this->get_argument( 'language', 'any' );
    $words_only = $this->get_argument( 'words_only', '0' );

    $dictionary_class_name = lib::get_class_name( 'database\dictionary' );
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'dictionary_id', '=', $dictionary_id );
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
