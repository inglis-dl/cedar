<?php
/**
 * test_entry_submit.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: test_entry submit
 *
 * Submit a test entry from an adjudication.
 */
class test_entry_submit extends \cenozo\ui\push\base_record
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
  protected function validate()
  {
    parent::validate();

   if( !$db_test_entry->completed )
      throw lib::create( 'exception\notice',
        'Tried to submit an incomplete adjudication.', __METHOD__ );
  }

  /** 
   * Finishes the operation with any post-execution instructions that may be necessary.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\runtime
   * @access protected
   */
  protected function finish() 
  {
    parent::finish();

    // an adjudicate test entry has been submitted
    // get one of the assignments of the original test entry based
    // on participant id
    $assignment_class_name = lib::get_class_name( 'database\assignment' ); 

    $db_test_entry = $this->get_record();

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'participant_id', '=', $db_test_entry->participant_id );
    $modifier->limit( 1 );
    $db_assignment = current( $assignment_class_name::select( $modifier ) );
    
    $assignment_manager = lib::create( 'business\assignment_manager' );
    $assignment_manager::complete_assignment( $db_assignment );

    $db_test_entry->adjudicate = false;
    $db_sibling_test_entry = $db_test_entry->get_sibling_test_entry();
    $db_sibling_test_entry->adjudicate = false;
    $db_test_entry->save();
    $db_sibling_test_entry->save();     
  }
}
