<?php
/**
 * word_edit.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: word edit
 *
 * Edit a word.
 */
class word_edit extends \cenozo\ui\push\base_edit
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

    $word_class_name = lib::get_class_name( 'database\word' );

    $columns = $this->get_argument( 'columns' );

    // if there is a word, validate it
    if( array_key_exists( 'word', $columns ) )
    {
      $word = explode( ' ', strtolower( trim( $columns['word'] ) ) );

      if( empty( $word ) )
        throw lib::create( 'exception\notice',
          'Empty word entries are not allowed.', __METHOD__ );
      
      foreach( $word as $value )
      {
        if( !$word_class_name::is_valid_word( $value ) )
          throw lib::create( 'exception\notice',
            '"'. $value . '" is not a valid word.', __METHOD__ );
      }
    }
  }
}
