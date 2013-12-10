<?php
/**
 * test_entry_delete.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: test_entry delete
 */
class test_entry_delete extends \cenozo\ui\push\base_delete
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry', $args );
  }
}
