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
class word_list extends \cenozo\ui\pull\base_list
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Pull arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'word', $args );
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
    
    $columns = $this->get_argument( 'columns' );

    if( array_key_exists( 'dictionary_id', $columns ) &&
        array_key_exists( 'language', $columns ) )
    {

      $dictionary_class_name = lib::get_class_name( 'database\dictionary' );
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'dictionary_id', '=', $columns['dictionary_id'] );
      $modifier->where( 'language', '=', $columns['language'] );
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
