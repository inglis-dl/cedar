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
   * Validate the operation.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  public function validate()
  {
    parent::validate();

    $columns = $this->get_argument( 'columns' );
    if( array_key_exists( 'participant_status', $columns ) &&
       'refused' == $columns['participant_status'] )
    {
      $db_test_entry = $this->get_record();
      if( 'unavailable' == $db_test_entry->audio_status ||
          'unusable'    == $db_test_entry->audio_status )
      {
        throw lib::create( 'exception\notice',
          'The audio status is inconsistent with the participant status.', __METHOD__ );
      }         
    }
  }
}
