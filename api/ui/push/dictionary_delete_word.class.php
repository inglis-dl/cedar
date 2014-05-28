<?php
/**
 * dictionary_delete_word.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: dictionary delete_word
 *
 * Delete a word from a dictionary.
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
