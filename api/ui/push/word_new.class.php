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
}
