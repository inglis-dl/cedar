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
class test_entry extends \cenozo\database\record
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
       log::warning( 'tried to get previous test_entry on test_entry with no id' );
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
       log::warning( 'tried to get next test_entry on test_entry with no id' );
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
   * Determine and set the adjudicate status.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   * @throws exception\notice
   */
  public function adjudicate()
  {
    if( !$this->completed ) return;
    // are there 2 test entries for this participant 
    
    $db_assignment = $this->get_assignment();
    $assign_mod = lib::create( 'database\modifier' );
    $assign_mod->where( 'participant_id', '=', $db_assignment->participant_id );
    $assign_mod->where( 'user_id', '!=', $db_assignment->user_id );
    $assignment_class_name = lib::get_class_name( 'database\assignment' );
    $db_assignment_match = $assignment_class_name::select( $assign_mod );
    if( empty( $db_assignment_match ) ) 
    {
      log::debug( 'no matching assignment found' );
      return;
    }
    $db_assignment_match = $db_assignment_match[0];
    // TODO check that the user_id in the assignment table should be unique
    
    // get the matching test entry to compare with
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );
    
    $entry_mod = lib::create( 'database\modifier' );
    $entry_mod->where( 'assignment_id', '=', $db_assignment_match->id );
    $entry_mod->where( 'test_id', '=', $this->test_id );
    $entry_mod->where( 'completed', '=', 1 );
    $db_test_entry_match = $test_entry_class_name::select( $entry_mod );
    if( empty( $db_test_entry_match ) )
    {
      log::debug( 'no matching assignment found' );
      return;
    }
    $db_test_entry_match = $db_test_entry_match[0];
    
    // get all the sub entries for each entry
    $entry_type_name = $this->get_test()->get_test_type()->name;
    $entry_name = 'test_entry_' . $entry_type_name;
    $get_list_method = 'get_' . $entry_name . '_list';
    $entry_list = $this->$get_list_method();
    $match_entry_list = $db_test_entry_match->$get_list_method();
 
    if( count( $match_entry_list ) != count( $entry_list ) )
      throw lib::create( 'exception\runtime',
        'Test entries must have the same number of sub entries.', __METHOD__ );

    $entry_class_name = lib::get_class_name( 'database\\' . $entry_name );
    $adjudicate = $entry_class_name::adjudicate_compare( $entry_list , $match_entry_list );
    if( $adjudicate == 0 )
    {
       
     log::debug( 'no diffs found' );
    }
    else
    {
      log::debug(' diffs found' );
    }
    if(  $this->adjudicate != $adjudicate )
    {
      $this->adjudicate = $adjudicate;
      $this->save();
    }  
     
    if( $db_test_entry_match->adjudicate != $adjudicate )
    {
      $db_test_entry_match->adjudicate = $adjudicate;
      $db_test_entry_match->save();
    } 
  }
}
