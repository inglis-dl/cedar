<?php
/**
 * test_entry.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\database;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * test_entry: record
 */
class test_entry extends \cenozo\database\has_note
{
  /** 
   * Get the previous record according to test rank.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   */
  public function get_previous()
  {
    $db_prev_test_entry = NULL;
    if( is_null( $this->id ) )
    {
       throw lib::create( 'exception\runtime',
         'Tried to get previous test_entry on test_entry with no id', __METHOD__ );
    }
    else
    {
      $test_class_name = lib::get_class_name( 'database\test' );
      $db_prev_test = $test_class_name::get_unique_record( 'rank', $this->get_test()->rank - 1 );
      if( !is_null( $db_prev_test ) ) 
        $db_prev_test_entry = static::get_unique_record( 
          array( 'test_id', 'assignment_id' ),
          array( $db_prev_test->id, $this->assignment_id ) );  
    }
    return $db_prev_test_entry;
  }

  /** 
   * Get the next record according to test rank.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   */
  public function get_next()
  {
    $db_next_test_entry = NULL;
    if( is_null( $this->id ) )
    {
     throw lib::create( 'exception\runtime',
       'Tried to get next test_entry on test_entry with no id', __METHOD__ );
    }
    else
    {
      $test_class_name = lib::get_class_name( 'database\test' );
      $db_next_test = $test_class_name::get_unique_record( 'rank', $this->get_test()->rank + 1 );
      if( !is_null( $db_next_test ) ) 
        $db_next_test_entry = static::get_unique_record( 
          array( 'test_id', 'assignment_id' ),
          array( $db_next_test->id, $this->assignment_id ) );
    }
    return $db_next_test_entry;
  }


  /** 
   * Get the entry record from the sibling assignment for adjudication.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   */
  public function get_adjudicate_entry()
  {
    if( is_null( $this->completed ) || $this->completed == 0 || $this->deferred == 1 )
      return NULL;
    
    // find a matching assignment based on participant id and user id uniqueness
    $db_assignment = $this->get_assignment();
    $assign_mod = lib::create( 'database\modifier' );
    $assign_mod->where( 'participant_id', '=', $db_assignment->participant_id );
    $assign_mod->where( 'user_id', '!=', $db_assignment->user_id );
    $assignment_class_name = lib::get_class_name( 'database\assignment' );
    $db_assignment_match = $assignment_class_name::select( $assign_mod );
    if( empty( $db_assignment_match ) || is_null( $db_assignment_match ) )   
      return NULL;
    
    $db_assignment_match = $db_assignment_match[0];
    // TODO check that the user_id in the assignment table should be unique
    
    // get the matching test entry to compare with
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );    
    $entry_mod = lib::create( 'database\modifier' );
    $entry_mod->where( 'assignment_id', '=', $db_assignment_match->id );
    $entry_mod->where( 'test_id', '=', $this->test_id );
    $entry_mod->where( 'completed', '=', 1 );
    $db_test_entry_match = $test_entry_class_name::select( $entry_mod );
    if( empty( $db_test_entry_match ) || is_null( $db_test_entry_match ) )    
      return NULL;
 
    return $db_test_entry_match[0];
  }

  /** 
   * Determine and set the adjudicate status.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   * @throws exception\notice
   */
  public function adjudicate()
  {
    $db_test_entry_match = $this->get_adjudicate_entry();
    if( $db_test_entry_match == NULL ) return false;

    // get all the sub entries for each entry
    $entry_type_name = $this->get_test()->get_test_type()->name;
    $entry_name = 'test_entry_' . $entry_type_name;
    $get_list_method = 'get_' . $entry_name . '_list';
    $entry_list = $this->$get_list_method();
    $match_entry_list = $db_test_entry_match->$get_list_method();
 
    $entry_class_name = lib::get_class_name( 'database\\' . $entry_name );
    $adjudicate = $entry_class_name::adjudicate_compare( $entry_list , $match_entry_list );
    
    $this->adjudicate = $adjudicate;
     
    if( $db_test_entry_match->adjudicate != $adjudicate )
    {
      $db_test_entry_match->adjudicate = $adjudicate;
      $db_test_entry_match->save();
    }

    return true;
  }

  /** 
   * Update the complete and adjudicate status fields.
   * Test entry sub table entries determine completion status to
   * pass to this method in their edit operation.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   */
  public function update_status_fields( $completed )
  {
    $this->completed = $completed;
    $this->adjudicate();
    $this->save();
  }
}
