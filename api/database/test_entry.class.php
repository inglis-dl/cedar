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
      $found = false;
      if( $adjudicate )
      {
        do
        {
          $db_prev_test = $test_class_name::get_unique_record( 'rank', $rank-- );
          if( !is_null( $db_prev_test ) )
          {
            $db_test_entry = static::get_unique_record(
              array( 'test_id', 'assignment_id' ),
              array( $db_prev_test->id, $this->assignment_id ) );
            if( !is_null( $db_test_entry ) && $db_test_entry->adjudicate )
            {
              $db_prev_test_entry = $db_test_entry;
              $found = true;
            }
          }
        } while( !$found && 0 < $rank );
      }
      else
      {
        do
        {
          $db_prev_test = $test_class_name::get_unique_record( 'rank', $rank-- );
          if( !is_null( $db_prev_test ) )
          {
            $db_test_entry = static::get_unique_record(
              array( 'test_id', 'assignment_id' ),
              array( $db_prev_test->id, $this->assignment_id ) );
            if( !is_null( $db_test_entry ) &&
                !in_array( $db_test_entry->audio_status, self::$audio_complete_states ) &&
                'refused' != $db_test_entry->participant_status &&
                'submitted' != $db_test_entry->completed )
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
      $rank = $this->get_test()->rank + 1;
      $max_rank = $test_class_name::count();
      $found = false;
      if( $adjudicate )
      {
        do
        {
          $db_next_test = $test_class_name::get_unique_record( 'rank', $rank++ );
          if( !is_null( $db_next_test ) )
          {
            $db_test_entry = static::get_unique_record(
              array( 'test_id', 'assignment_id' ),
              array( $db_next_test->id, $this->assignment_id ) );
            if( !is_null( $db_test_entry ) && $db_test_entry->adjudicate )
            {
              $db_next_test_entry = $db_test_entry;
              $found = true;
            }
          }
        } while( !$found && $rank <= $max_rank );
      }
      else
      {
        do
        {
          $db_next_test = $test_class_name::get_unique_record( 'rank', $rank++ );
          if( !is_null( $db_next_test ) )
          {
            $db_test_entry = static::get_unique_record(
              array( 'test_id', 'assignment_id' ),
              array( $db_next_test->id, $this->assignment_id ) );
            if( !is_null( $db_test_entry ) &&
                !in_array( $db_test_entry->audio_status, self::$audio_complete_states ) &&
                'refused' != $db_test_entry->participant_status &&
                'submitted' != $db_test_entry->completed )
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
        in_array( $this->audio_status, self::$audio_complete_states ) )
    {
      $completed = true;
    }
    else
    {
      // what type of test is this ?
      $db_test = $this->get_test();
      $test_type_name = $db_test->get_test_type()->name;
      $entry_class_name = lib::get_class_name( 'database\test_entry_' . $test_type_name );

      // the test is different depending on type:
      // - confirmation: confirmation column is not null
      // - classification: one word_id column not null
      // - alpha_numeric: one word_id column not null
      // - ranked_word: all primary dictionary words have valid selection responses with
      // variant and intrusion responses having a not null word_id

      $base_mod = lib::create( 'database\modifier' );
      $base_mod->where( 'test_entry_id', '=', $this->id );
      if( 'confirmation' == $test_type_name )
      {
        $modifier = clone $base_mod;
        $modifier->where( 'confirmation', '!=', NULL );
        $completed = 0 < $entry_class_name::count( $modifier );
      }
      else if( 'classification' == $test_type_name || 'alpha_numeric' == $test_type_name )
      {
        $modifier = clone $base_mod;
        $modifier->where( 'word_id', '!=', NULL );
        $completed = 0 < $entry_class_name::count( $modifier );
      }
      else if( 'ranked_word' == $test_type_name )
      {
        // custom query for ranked_word test type
        $modifier = clone $base_mod;
        $modifier->where( 'ranked_word_set_id', '!=', NULL );
        $modifier->where( 'selection', '!=', NULL );
        $sql = sprintf(
          'SELECT '.
          '( '.
            '( SELECT MAX(rank) FROM ranked_word_set ) - '.
            '( '.
              'SELECT COUNT(*) FROM test_entry_ranked_word %s '.
              'AND IF( selection = "variant", IF( word_id IS NULL, 0, 1 ), 1 ) '.
            ') '.
          ')',
          $modifier->get_sql() );

        $completed = 0 === intval( static::db()->get_one( $sql ) );

        // check that intrusions are filled in for non-adjudicate entries
        if( $completed && is_null( $this->participant_id ) )
        {
          $modifier = clone $base_mod;
          $modifier->where( 'ranked_word_set_id', '=', NULL );
          $modifier->where( 'selection', '=', NULL );
          $modifier->where( 'word_id', '=', NULL );

          $completed = 0 === $entry_class_name::count( $modifier );
        }
      }
    }

    if( $this->is_adjudicate() && !$completed )
    {
       // compare the progenitors
       // if they match, assess the audio and participant status's
       // both of which are allowed to be empty
       $modifier = lib::create( 'database\modifier' );
       $modifier->where( 'assignment.participant_id', '=', $this->participant_id );
       $modifier->where( 'test_id', '=', $this->test_id );
       $modifier->where( 'participant_id', '=', NULL );
       $progenitor = $this->get_progenitor_test_entry();
       $sibling = $progenitor->get_sibling_test_entry();
       if( $progenitor->compare( $sibling, false ) )
       {
         if( ( $progenitor->audio_status == $this->audio_status ||
               $sibling->audio_status == $this->audio_status ) &&
             ( $progenitor->participant_status == $this->participant_status ||
               $sibling->participant_status == $this->participant_status ) )
           $completed = true;
       }
    }

    return $completed;
  }

  /**
   * Is this test entry and adjudicate entry?
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   * @return bool
   */
  public function is_adjudicate()
  {
    return ( !is_null( $this->participant_id ) && is_null( $this->assignment_id ) );
  }

  /**
   * Compare this test_entry with another.
   * The other test_entry should be from the sibling assignment.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   * @param database\test_entry $db_test_entry
   * @param bool Whether to compare participant and audio status's
   * @return bool true if identical
   */
  public function compare( $db_test_entry, $status_compare = true )
  {
    $comparison_result =  $status_compare ?
      ( $this->audio_status == $db_test_entry->audio_status &&
        $this->participant_status == $db_test_entry->participant_status ) : true;

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
   * @param database\modifier $modifier Modifications to the selection.
   * @access public
   * @param database\modifier Modifier to refine the selection
   * @return database\test_entry (NULL if no sibling)
   */
  public function get_sibling_test_entry( $modifier = NULL )
  {
    $db_test_entry = false;
    if( !is_null( $this->assignment_id ) )
    {
      // find a sibling test_entry based on assignment id and test id uniqueness
      $db_sibling_assignment = $this->get_assignment()->get_sibling_assignment();
      if( !is_null( $db_sibling_assignment ) )
      {
        if( is_null( $modifier ) )
        {
          $modifier = lib::create( 'database\modifier' );
        }
        $modifier->where( 'assignment_id', '=', $db_sibling_assignment->id );
        $modifier->where( 'test_id', '=', $this->test_id );
        $modifier->limit( 1 );
        $db_test_entry = current( static::select( $modifier ) );
      }
    }
    return false === $db_test_entry ? NULL : $db_test_entry;
  }

  /**
   * Get the adjudication of this test_entry
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   * @return database\test_entry (NULL if no adjudicate)
   */
  public function get_adjudicate_test_entry()
  {
    $db_test_entry = NULL;
    if( !is_null( $this->assignment_id ) )
    {
      $db_assignment = $this->get_assignment();
      if( !is_null( $db_assignment ) )
      {
        $db_test_entry = static::get_unique_record(
          array( 'test_id', 'participant_id' ),
          array( $this->test_id, $db_assignment->participant_id ) );
      }
    }
    return $db_test_entry;
  }

  /**
   * Get a progenitor of an adjudication test_entry
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   * @return database\test_entry (NULL if not an adjudicate or no progenitor)
   */
  public function get_progenitor_test_entry()
  {
    $db_test_entry = NULL;
    if( $this->is_adjudicate() )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'assignment.participant_id', '=', $this->participant_id );
      $modifier->where( 'test_id', '=', $this->test_id );
      $modifier->limit( 1 );
      $db_test_entry = current( static::select( $modifier ) );
    }
    return false === $db_test_entry ? NULL : $db_test_entry;
  }

  /**
   * Initialize this test_entry by deleting and recreating all daughter table entries.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param boolean reset_default Reset column values to their default state at creation.
   * @access public
   */
  public function initialize( $reset_default = true )
  {
    if( $reset_default )
    {
      $this->completed = 'incomplete';
      $this->deferred = NULL;
      $this->adjudicate = NULL;
      $this->audio_status = NULL;
      $this->participant_status = NULL;
      $this->save();
    }

    $db_test = $this->get_test();
    $test_type_name = $db_test->get_test_type()->name;
    $entry_class_name = 'test_entry_' . $test_type_name;
    $get_count_method = 'get_' . $entry_class_name . '_count';

    if( 0 < $this->$get_count_method() )
    {
      $sql = sprintf(
        'DELETE FROM %s '.
        'WHERE test_entry_id = %s',
        $entry_class_name,
        static::db()->format_string( $this->id ) );
      static::db()->execute( $sql );
    }

    if( 'ranked_word' == $test_type_name )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->order( 'rank' );
      foreach( $db_test->get_ranked_word_set_list( $modifier ) as $db_ranked_word_set )
      {
        $db_test_entry_ranked_word = lib::create( 'database\\'. $entry_class_name );
        $db_test_entry_ranked_word->test_entry_id = $this->id;
        $db_test_entry_ranked_word->ranked_word_set_id = $db_ranked_word_set->id;
        $db_test_entry_ranked_word->save();
      }
    }
    else if( 'confirmation' == $test_type_name )
    {
      $db_test_entry_confirmation = lib::create( 'database\\'. $entry_class_name );
      $db_test_entry_confirmation->test_entry_id = $this->id;
      $db_test_entry_confirmation->save();
    }
    else if( 'classification' == $test_type_name )
    {
      $setting_manager = lib::create( 'business\setting_manager' );
      $max_rank = $setting_manager->get_setting( 'interface', 'classification_max_rank' );
      for( $rank = 1; $rank <= $max_rank; $rank++ )
      {
        $db_test_entry_classification = lib::create( 'database\\'. $entry_class_name );
        $db_test_entry_classification->test_entry_id = $this->id;
        $db_test_entry_classification->rank = $rank;
        $db_test_entry_classification->save();
      }
    }
    else if( 'alpha_numeric' == $test_type_name )
    {
      $setting_manager = lib::create( 'business\setting_manager' );
      $max_rank = $setting_manager->get_setting( 'interface', 'alpha_numeric_max_rank' );
      for( $rank = 1; $rank <= $max_rank; $rank++ )
      {
        $db_test_entry_alpha_numeric = lib::create( 'database\\'. $entry_class_name );
        $db_test_entry_alpha_numeric->test_entry_id = $this->id;
        $db_test_entry_alpha_numeric->rank = $rank;
        $db_test_entry_alpha_numeric->save();
      }
    }
  }

  /**
   * Trim off empty alpha_numeric, classification and ranked_word test
   * entries.  Trim removes entries starting from the highest id downward
   * until the first non-empty record is reached.  Trim will remove, for example,
   * trailing empty intrusion records in ranked_word tests.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return integer number of records deleted
   * @access public
   */
  public function trim()
  {
    $db_test = $this->get_test();
    $test_type_name = $db_test->get_test_type()->name;
    if( 'confirmation' == $test_type_name ) return 0;

    $entry_class_name = lib::get_class_name( 'database\test_entry_' . $test_type_name );

    $sql = NULL;
    if( 'classification' == $test_type_name || 'alpha_numeric' == $test_type_name )
    {
      $sql = sprintf(
        'SELECT id '.
        'FROM test_entry_' . $test_type_name . ' '.
        'WHERE word_id IS NULL '.
        'AND test_entry_id = %s '.
        'ORDER BY rank DESC',
        static::db()->format_string( $this->id ) );
    }
    else
    {
      $sql = sprintf(
        'SELECT id '.
        'FROM test_entry_' . $test_type_name . ' '.
        'WHERE word_id IS NULL '.
        'AND ranked_word_set_id IS NULL '.
        'AND test_entry_id = %s '.
        'ORDER BY id DESC',
        static::db()->format_string( $this->id ) );
    }
    $rows = $entry_class_name::db()->get_all( $sql );
    $count = 0;
    if( count( $rows ) > 0 )
    {
      foreach( $rows as $index => $row )
      {
        $db_entry = lib::create( 'database\test_entry_' . $test_type_name, $row['id'] );
        if( !is_null( $db_entry->word_id ) ) break;
        $db_entry->delete();
        $count++;
      }
    }
    return $count;
  }

  /**
   * Truncate alpha_numeric, classification and ranked_word test
   * entries.  Truncate removes entries starting from the highest id downward
   * until $size deletions are obtained.  For ranked_word entries, only
   * intrusions can be deleted.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return integer number of records deleted
   * @access public
   */
  public function truncate( $size = 0 )
  {
    if( 0 == $size ) return 0;

    $db_test = $this->get_test();
    $test_type_name = $db_test->get_test_type()->name;
    if( 'confirmation' == $test_type_name ) return 0;

    $entry_class_name = lib::get_class_name( 'database\test_entry_' . $test_type_name );

    $sql = sprintf(
      'SELECT id '.
      'FROM test_entry_' . $test_type_name . ' '.
      'WHERE test_entry_id = %s '.
      'ORDER BY id DESC '.
      'LIMIT %d',
      static::db()->format_string( $this->id ), $size );

    $rows = $entry_class_name::db()->get_all( $sql );
    $count = 0;
    if( count( $rows ) > 0 )
    {
      foreach( $rows as $index => $row )
      {
        $db_entry = lib::create( 'database\test_entry_' . $test_type_name, $row['id'] );
        if( 'ranked_word' == $test_type_name &&
            !is_null( $db_entry->ranked_word_set_id ) ) break;
        $db_entry->delete();
        $count++;
      }
    }
    return $count;
  }

  /**
   * Get the id of the participant's preferred language or that of the
   * service to use for transcribing audio recordings.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return $db_language default language to transcribe in
   * @access public
   */
  public function get_default_participant_language()
  {
    $db_participant = is_null( $this->assignment_id ) ?
      $this->get_participant() : $this->get_assignment()->get_participant();
    return is_null( $db_participant->language_id ) ?
      lib::create( 'business\session' )->get_service()->get_language() :
      $db_participant->get_language();
  }

  /**
   * Audio states that allow completion without daughter entry items.
   * @var audio_complete_states
   * @access private
   */
  public static $audio_complete_states = array( 'unavailable', 'unusable' );

  /**
   * Deferral states that indicate a deferred status.
   * @var audio_complete_states
   * @access private
   */
  public static $deferred_states = array( 'requested', 'pending' );
}
