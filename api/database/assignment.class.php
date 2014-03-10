<?php
/**
 * assignment.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\database;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * assignment: record
 */
class assignment extends \cenozo\database\record
{
  /** 
   * Returns the role dependent complete status of the assignment.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param string the role for which completeness is to be determined
   * @return boolean
   * @access public
   */
  public function is_complete( $role = 'typist' )
  {
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'assignment_id', '=', $this->id );

    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );

    if( $role == 'typist' )
    {
      $modifier->where( 'deferred', '=', 1 );
      $modifier->where( 'completed', '=', 0, true, true );
      $num_completed = $test_entry_class_name::count( $modifier );
      return 0 == $num_completed;
    }
    else
    {
      $modifier->where( 'adjudicate', '=', 1 );
      $num_adjudicate  = $test_entry_class_name::count( $modifier );
      return 0 == $num_adjudicate && !empty( $this->end_datetime );
    }
  }
}
