<?php
/**
* test_entry_confirmation_new.class.php
*
* @author Dean Inglis <inglisd@mcmaster.ca>
* @filesource
*/

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
* push: test_entry_confirmation new
*
* Create a new ranked word test entry.
*/
class test_entry_confirmation_new extends \cenozo\ui\push\base_new
{
  /**
* Constructor.
* @author Dean Inglis <inglisd@mcmaster.ca>
* @param array $args Push arguments
* @access public
*/
  public function __construct( $args )
  {
    parent::__construct( 'test_entry_confirmation', $args );
  }
}
