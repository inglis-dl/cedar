<?php
/**
 * dictionary_delete.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace curry\ui\push;
use cenozo\lib, cenozo\log, curry\util;

/**
 * push: dictionary delete
 */
class dictionary_delete extends \cenozo\ui\push\base_delete
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'dictionary', $args );
  }
}
