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
   * @return database\test_entry (NULL if unsuccessful)
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
   * @return database\test_entry (NULL if unsuccessful)
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
            if( !is_null( $db_test_entry ) &&  true == $db_test_entry->adjudicate )
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
   * Determine the completed status of this test entry.
   * NOTE: completeness test must be implemented for each test type. 
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   * @throws exception\runtime
   * @return bool completed status
   */
  public function is_completed()
  {
    $completed = false;

    // what type of test is this ?
    $db_test = $this->get_test();
    $test_type_name = $db_test->get_test_type()->name;
    $database_class_name = lib::get_class_name( 'database\database' );
    $entry_class_name = lib::get_class_name( 'database\test_entry_' . $test_type_name );

    // the test is different depending on type:
    // - confirmation: confirmation column is not null
    // - classification: one word_id or word_candidate column not null
    // - alpha_numeric: one word_id column not null
    // - ranked_word: all primary dictionary words have valid selection responses with 
    // variant responses having a not null word_candidate

    $base_mod = lib::create( 'database\modifier' );
    $base_mod->where( 'test_entry_id', '=', $this->id );
    if( $test_type_name == 'confirmation' )
    {
      $base_mod->where( 'confirmation', '!=', NULL );
      $completed = 0 < $entry_class_name::count( $base_mod );
    }
    else if( $test_type_name == 'classification' )
    {
      $base_mod->where( 'word_id', '!=', NULL );
      $base_mod->where( 'word_candidate', '!=', NULL, true, true );
      $completed = 0 < $entry_class_name::count( $base_mod );
    }
    else if( $test_type_name == 'alpha_numeric' )
    {
      $base_mod->where( 'word_id', '!=', NULL );
      $completed = 0 < $entry_class_name::count( $base_mod );
    }
    else if( $test_type_name == 'ranked_word' )
    {
      // custom query for ranked_word test type
      $sql = sprintf( 
        'SELECT '.
        '( '.
          '( SELECT MAX(rank) FROM ranked_word_set ) - '.
          '( '.
            'SELECT COUNT(*) FROM test_entry_ranked_word '.
            'WHERE test_entry_id = %s '.
            'AND '.
            '( '.
              'selection IS NOT NULL OR '.
              'selection="variant" AND word_candidate IS NOT NULL '.
            ') '.
          ') '. 
        ')',
        $database_class_name::format_string( $this->id ) );
      
      $completed = 0 == static::db()->get_one( $sql );
    }
    else
      throw lib::create( 'exception\runtime',
        'Unrecognized test type: ' . $test_type_name, __METHOD__ );

    return $completed;
  }

  /** 
   * Compare this test_entry with another.
   * The other test_entry should be from the sibling assignment.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   * @param database\test_entry $db_test_entry
   * @return bool completed status
   */
  public function compare( $db_test_entry )
  {
    // get the daughter table entries as lists
    $entry_name = 'test_entry_' . $this->get_test()->get_test_type()->name;
    $entry_class_name = lib::get_class_name( 'database\\' . $entry_name );
    $get_list_method = 'get_' . $entry_name . '_list';

    $lhs_list = $this->$get_list_method();
    $rhs_list = $db_test_entry->$get_list_method();
   
    return $entry_class_name::compare( $lhs_list, $rhs_list );
  }
}
