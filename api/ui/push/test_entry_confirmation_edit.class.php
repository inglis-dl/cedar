<?php
/**
 * test_entry_confirmation_edit.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: test_entry_confirmation edit
 *
 * Edit a confirmation test entry.
 */
class test_entry_confirmation_edit extends \cenozo\ui\push\base_edit
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

  /** 
   * This method executes the operation's purpose.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    // \cenozo\database\database::$debug=true;
    parent::execute();

    $db_test_entry_confirmation = $this->get_record();
    $db_test_entry = $db_test_entry_confirmation->get_test_entry();
    $completed = is_null( $db_test_entry_confirmation ) ? 0 : 1;
    $db_test_entry->update_status_fields( $completed );
    // \cenozo\database\database::$debug=false;
  }  
}
