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
   * @param boolean defines whether to get the next entry when adjudicating
   * @return record (NULL if unsuccessful)
   * @access public
   */
  public function get_previous( $adjudicate = false )
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
      $rank = $this->get_test()->rank - 1;

      if( $adjudicate )
      {
        $found = false;
        do
        {
          $db_prev_test = $test_class_name::get_unique_record( 'rank', $rank-- );
          if( !is_null( $db_prev_test ) )
          {
            $db_test_entry = static::get_unique_record(
              array( 'test_id', 'assignment_id' ),
              array( $db_prev_test->id, $this->assignment_id ) );
            if( !is_null( $db_test_entry ) && 1 == $db_test_entry->adjudicate )
            {
              $db_prev_test_entry = $db_test_entry;
              $found = true;
            }
          }
        } while( !$found && 0 < $rank );
      }
      else
      {
        $db_prev_test = $test_class_name::get_unique_record( 'rank', $rank );
        if( !is_null( $db_prev_test ) ) 
          $db_prev_test_entry = static::get_unique_record( 
            array( 'test_id', 'assignment_id' ),
            array( $db_prev_test->id, $this->assignment_id ) );
      }    

    }
    return $db_prev_test_entry;
  }

  /** 
   * Get the next record according to test rank.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param boolean defines whether to get the next entry when adjudicating
   * @return record (NULL if unsuccessful)
   * @access public
   */
  public function get_next( $adjudicate = false )
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
      $rank =  $this->get_test()->rank + 1;

      if( $adjudicate )
      {
        $max_rank = $test_class_name::count();
        $found = false;
        do
        {
          $db_next_test = $test_class_name::get_unique_record( 'rank', $rank++ );
          if( !is_null( $db_next_test ) )
          {
            $db_test_entry = static::get_unique_record(
              array( 'test_id', 'assignment_id' ),
              array( $db_next_test->id, $this->assignment_id ) );
            if( !is_null( $db_test_entry ) &&  1 == $db_test_entry->adjudicate )
            {
              $db_next_test_entry = $db_test_entry;
              $found = true;
            }
          }
        } while( !$found && $rank <= $max_rank );
      }
      else
      {
        $db_next_test = $test_class_name::get_unique_record( 'rank', $rank );
        if( !is_null( $db_next_test ) ) 
          $db_next_test_entry = static::get_unique_record( 
            array( 'test_id', 'assignment_id' ),
            array( $db_next_test->id, $this->assignment_id ) );
      }    
    }
    return $db_next_test_entry;
  }

  /** 
   * Get the test entry from the sibling assignment for adjudication.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return record (NULL if unsuccessful)
   * @access public
   */
  public function get_adjudicate_record()
  {
    $db_assignment = $this->get_assignment();
    // this entry could belong to an adjudicate entry submission
    if( empty( $db_assignment ) ) 
      return NULL;
    $db_assignment_sibling = $db_assignment->get_sibling_record();
    if( $db_assignment_sibling == NULL )
      return NULL;
    
    // get the matching test entry to compare with
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );    
    $entry_mod = lib::create( 'database\modifier' );
    $entry_mod->where( 'assignment_id', '=', $db_assignment_sibling->id );
    $entry_mod->where( 'test_id', '=', $this->test_id );
    $entry_mod->where( 'completed', '=', 1 );
    $entry_mod->where( 'deferred', '=', 0 );
    $db_test_entry = $test_entry_class_name::select( $entry_mod );
    if( empty( $db_test_entry ) )    
      return NULL;
 
    return $db_test_entry[0];
  }

  /** 
   * Determine and set the adjudicate status.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   * @throws exception\notice
   */
  public function update_adjudicate_status()
  {
    $this->adjudicate = 0;
    $db_test_entry = $this->get_adjudicate_record();
    if( $db_test_entry == NULL ) return;

    if( 1 == $this->completed && 0 == $this->deferred )
    {
      // get all the sub entries for each test entry and compare
      $entry_name = 'test_entry_' . $this->get_test()->get_test_type()->name;
      $get_list_method = 'get_' . $entry_name . '_list';
      $entry_list = $this->$get_list_method();
      $match_entry_list = $db_test_entry->$get_list_method();
   
      $entry_class_name = lib::get_class_name( 'database\\' . $entry_name );
      $this->adjudicate = $entry_class_name::adjudicate_compare( $entry_list , $match_entry_list );
    }
     
    if( $db_test_entry->adjudicate != $this->adjudicate )
    {
      $db_test_entry->adjudicate = $this->adjudicate;
      $db_test_entry->save();
    }
  }

  /** 
   * Update the complete and adjudicate status fields.
   * Test entry sub table entries determine completion status to
   * pass to this method in their edit operation.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param boolean the completed status
   * @access public
   */
  public function update_status_fields( $completed = 0 )
  {
    $this->completed = $completed;
    $this->update_adjudicate_status();
    $this->save();

    $db_assignment = $this->get_assignment();
    if( !empty( $db_assignment ) )
    {
      if( $db_assignment->is_complete() )
      {
        $db_assignment->end_datetime = util::get_datetime_object()->format( "Y-m-d H:i:s" );
      }
      else
      {
        $db_assignment->end_datetime = NULL;
      }
      $db_assignment->save();
    }  
  }
}
