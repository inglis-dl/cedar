<?php
/**
 * dictionary_delete_word.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace curry\ui\push;
use cenozo\lib, cenozo\log, curry\util;

/**
 * push: dictionary delete_word
 */
class dictionary_delete_word extends \cenozo\ui\push\base_delete_record
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'dictionary', 'word', $args );
  }
}
