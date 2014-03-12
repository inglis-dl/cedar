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
   * Get sibling record to this assignment.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return record (NULL if no sibling)
   * @access public
   */
  public function get_sibling_record()
  {
    // find a ing assignment based on participant id and user id uniqueness
    $assign_mod = lib::create( 'database\modifier' );
    $assign_mod->where( 'participant_id', '=', $this->participant_id );
    $assign_mod->where( 'user_id', '!=', $this->user_id );
    $assignment_class_name = lib::get_class_name( 'database\assignment' );
    $db_assignment = $assignment_class_name::select( $assign_mod );
    if( empty( $db_assignment ) )
      return NULL;
          
    return $db_assignment[0];
  }

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
