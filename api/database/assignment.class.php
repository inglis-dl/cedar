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
    $base_mod = lib::create( 'database\modifier' );
    $base_mod->where( 'assignment_id', '=', $this->id );
    $modifier = clone $base_mod;
    $modifier->where( 'deferred', '=', 0 );
    $modifier->where( 'completed', '=', 1 );
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );
    $num_test = $test_entry_class_name::count( $base_mod );
    
    if( $role == 'typist' )
    {
      $num_complete = $test_entry_class_name::count( $modifier );
      return $num_test == $num_complete;
    }
    else
    {
      $modifier->where( 'adjudicate', '=', 0 );
      $num_complete  = $test_entry_class_name::count( $modifier );
      return $num_test == $num_complete && !empty( $this->end_datetime );
    }
  }
}
