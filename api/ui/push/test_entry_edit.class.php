<?php
/**
 * test_entry_edit.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: test_entry edit
 *
 * Edit a test entry.
 */
class test_entry_edit extends \cenozo\ui\push\base_edit
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

  /**
   * This method executes the operation's purpose.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  public function execute()
  {
    parent::execute();

    $assignment_manager = lib::create( 'business\assignment_manager' );
    $db_test_entry = $this->get_record();

    $assignment_manager::complete_test_entry( $db_test_entry );
  }
}
