<?php
/**
 * test_entry_note_delete.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: test_entry_note delete
 *
 * Delete a test_entry_note.
 */
class test_entry_note_delete extends \cenozo\ui\push\base_delete
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry_note', $args );
  }
}
