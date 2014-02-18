<?php
/**
 * word_classify.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\pull;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * Class for word classify pull operations.
 *
 */
class word_classify extends \cenozo\ui\pull
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Pull arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'word', 'classify', $args );
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
    $this->data = 'candidate';
    
    $db_test = lib::create( 'database\test', $this->get_argument( 'test_id' ) );
    $word_candidate = $this->get_argument( 'word_candidate' );
    
    $db_dictionary = $db_test->get_dictionary();

    $language = $this->get_argument( 'language', 'any' );


    $base_mod = lib::create( 'database\modifier' );
    if( 'any' != $language ) $base_mod->where( 'language', '=', $language );
    $base_mod->where( 'word', '=', $word_candidate );
    $base_mod->limit( 1 );
    
    $modifier = clone $base_mod;
    $modifier->where( 'dictionary_id', '=', $db_test->get_dictionary()->id );   

    $db_word = $word_class_name::select( $modifier );
    if( !empty( $db_word ) )
    {
      $this->data = 'primary';
    }
    else
    {
      if( $db_test->strict != 0 )
      {
        $modifier = clone $base_mod;
        $modifier->where( 'dictionary_id', '=', $db_test->get_intrusion_dictionary()->id );
        $db_word = $word_class_name::select( $modifier );
        if( !empty( $db_word ) )
        {
          $this->data = 'intrusion';
        }
        else
        {
          $modifier = clone $base_mod;
          $modifier->where( 'dictionary_id', '=', $db_test->get_variant_dictionary()->id );
          $db_word = $word_class_name::select( $modifier );
          if( !empty( $db_word ) )
          {
            $this->data = 'intrusion';
          }
        }
      }      
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
