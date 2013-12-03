<?php
/**
 * test_delete_ranked_word_set.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: test delete_ranked_word_set
 */
class test_delete_ranked_word_set extends \cenozo\ui\push\base_delete_record
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test', 'ranked_word_set', $args );
  }
}
