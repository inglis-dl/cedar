<?php
/**
 * test_entry_reset.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: test_entry reset
 *
 * Edit a test entry.
 */
class test_entry_reset extends \cenozo\ui\push\base_record
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry', 'reset', $args );
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

    $db_test_entry = $this->get_record();
    $assignment_manager = lib::create( 'business\assignment_manager' );
    $assignment_manager::initialize_assignment( 
      $db_test_entry->get_assignment(),
      $db_test_entry->get_test()->id );
  }  
}
