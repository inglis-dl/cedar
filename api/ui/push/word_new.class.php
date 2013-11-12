<?php
/**
 * word_new.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace curry\ui\push;
use cenozo\lib, cenozo\log, curry\util;

/**
 * push: word new
 *
 * Create a new word.
 */
class word_new extends \cenozo\ui\push\base_new
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'word', $args );
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

    $columns = $this->get_argument( 'columns' );

    // if there is a word, validate it
    if( array_key_exists( 'word', $columns ) ) 
    {
      $word = strtolower( $columns['word'] );
      if( count( str_word_count( $word, 1 ) ) > 1 )
      {
        throw lib::create( 'exception\notice',
          'Multiple word are not allowed.', __METHOD__ );
      }
      if( preg_match( '#[0-9]#', $word ) )
      {  
        throw lib::create( 'exception\notice',
          'Not a valid word: numbers are not allowed.', __METHOD__ );
      }
    }
  }
}
