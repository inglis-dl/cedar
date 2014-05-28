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
        $found = false;
        do
        {
          $db_prev_test = $test_class_name::get_unique_record( 'rank', $rank-- );
          if( !is_null( $db_prev_test ) )
          {
            $db_test_entry = static::get_unique_record(
              array( 'test_id', 'assignment_id' ),
              array( $db_prev_test->id, $this->assignment_id ) );
            if( $db_test_entry->audio_status != 'unusable' && 
                $db_test_entry->audio_status != 'unavailable' &&
                $db_test_entry->participant_statue != 'refused' )
            {
              $db_prev_test_entry = $db_test_entry;
              $found = true;
            }
          }
        } while( !$found && 0 < $rank );      
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
        $found = false;
        do
        {
          $db_next_test = $test_class_name::get_unique_record( 'rank', $rank++ );
          if( !is_null( $db_prev_test ) )
          {
            $db_test_entry = static::get_unique_record(
              array( 'test_id', 'assignment_id' ),
              array( $db_next_test->id, $this->assignment_id ) );
            if( $db_test_entry->audio_status != 'unusable' && 
                $db_test_entry->audio_status != 'unavailable' &&
                $db_test_entry->participant_statue != 'refused' )
            {
              $db_next_test_entry = $db_test_entry;
              $found = true;
            }
          }
        } while( !$found && $rank <= $max_rank );
      }
    }
    return $db_next_test_entry;
  }

  /**
   * Determine the completed status of this test entry.
   * NOTE: completeness test must be implemented for each test type.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   * @return bool completed status
   */
  public function is_completed()
  {
    $completed = false;

    // check if the participant refused the test or if
    // the audio was sufficiently faulty
    if( 'refused' == $this->participant_status ||
        'unavailable' == $this->audio_status || 'unusable' == $this->audio_status )
    {
      $completed = true;
    }
    else
    {
      // what type of test is this ?
      $db_test = $this->get_test();
      $test_type_name = $db_test->get_test_type()->name;
      $database_class_name = lib::get_class_name( 'database\database' );
      $entry_class_name = lib::get_class_name( 'database\test_entry_' . $test_type_name );

      // the test is different depending on type:
      // - confirmation: confirmation column is not null
      // - classification: one word_id column not null
      // - alpha_numeric: one word_id column not null
      // - ranked_word: all primary dictionary words have valid selection responses with
      // variant and intrusion responses having a not null word_id

      $base_mod = lib::create( 'database\modifier' );
      $base_mod->where( 'test_entry_id', '=', $this->id );
      if( $test_type_name == 'confirmation' )
      {
        $base_mod->where( 'confirmation', '!=', NULL );
        $completed = 0 < $entry_class_name::count( $base_mod );
      }
      else if( $test_type_name == 'classification' || $test_type_name == 'alpha_numeric' )
      {
        $base_mod->where( 'word_id', '!=', NULL );
        $completed = 0 < $entry_class_name::count( $base_mod );
      }
      else if( $test_type_name == 'ranked_word' )
      {
        // custom query for ranked_word test type
        $id_string = $database_class_name::format_string( $this->id );
        $sql = sprintf(
          'SELECT '.
          '( '.
            '( SELECT MAX(rank) FROM ranked_word_set ) - '.
            '( '.
              'SELECT COUNT(*) FROM test_entry_ranked_word '.
              'WHERE test_entry_id = %s '.
              'AND selection IS NOT NULL '.
            ') '.
          ')',
          $id_string );

        $completed = 0 == static::db()->get_one( $sql );

        // check that intrusions are filled in for non-adjucate entries
        if( $completed && is_null( $this->participant_id ) )
        {
          $sql = sprintf(
            'SELECT COUNT(*) FROM test_entry_ranked_word '.
            'WHERE test_entry_id = %s '.
            'AND selection IS NULL '.
            'AND word_id IS NULL '.
            'AND ranked_word_set_id IS NULL',
            $id_string );

          $completed = 0 == static::db()->get_one( $sql );
        }
      }
    }

    return $completed;
  }

  /**
   * Compare this test_entry with another.
   * The other test_entry should be from the sibling assignment.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   * @param database\test_entry $db_test_entry
   * @return bool true if identical
   */
  public function compare( $db_test_entry )
  {
    $comparison_result =
      $this->audio_status == $db_test_entry->audio_status &&
      $this->participant_status == $db_test_entry->participant_status;

    if( $comparison_result )
    {
      // get the daughter table entries as lists
      $entry_name = 'test_entry_' . $this->get_test()->get_test_type()->name;
      $entry_class_name = lib::get_class_name( 'database\\' . $entry_name );
      $get_list_function = 'get_' . $entry_name . '_list';

      $lhs_list = $this->$get_list_function();
      $rhs_list = $db_test_entry->$get_list_function();

      $comparison_result = $entry_class_name::compare( $lhs_list, $rhs_list );
    }

    return $comparison_result;
  }

  /**
   * Get the sibling of this test_entry
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return db_test_entry (NULL if no sibling)
   * @access public
   */
  public function get_sibling_test_entry()
  {
    $db_test_entry = false;
    if( !is_null( $this->assignment_id ) )
    {
      // find a sibling test_entry based on assignment id and test id uniqueness
      $db_sibling_assignment = $this->get_assignment()->get_sibling_assignment();
      if( !is_null( $db_sibling_assignment ) )
      {
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'assignment_id', '=', $db_sibling_assignment->id );
        $modifier->where( 'test_id', '=', $this->test_id );
        $db_test_entry = current( static::select( $modifier ) );
      }
    }
    return false === $db_test_entry ? NULL : $db_test_entry;
  }
}
