<?php
/**
 * assignment_manager.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\business;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * Manages assignments.
 */
class assignment_manager extends \cenozo\singleton
{
  /**
   * Constructor.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function __construct()
  {
  }

  /**
   * Reset a test_entry.  All existing test_entry daughter records are deleted
   * and new ones are created. Only test_entrys belonging to assignments that
   * have never been finished can be reset.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @param  database\test_entry $db_test_entry
   * @access public
   */
  public static function reset_test_entry( $db_test_entry )
  {
    $db_assignment = $db_test_entry->get_assignment();

    if( !is_null( $db_assignment->end_datetime ) )
      throw lib::create( 'exception\notice',
        'The assignment for participant UID ' . $db_assignment->get_participant()->uid .
        'is closed and cannot have any tests reset.', __METHOD__ );

    $db_test_entry->initialize();

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'adjudicate', '!=', NULL );
    $db_sibling_test_entry = $db_test_entry->get_sibling_test_entry( $modifier );
    if( !is_null( $db_sibling_test_entry ) )
    {
      $db_sibling_test_entry->adjudicate = NULL;
      $db_sibling_test_entry->save();
    }
  }

  /**
   * Reassign an assigment and its sibling assignment to a pair
   * of users having no language restrictions
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param  database\assignment $db_assignment
   * @access public
   */
  public static function reassign( $db_assignment )
  {
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );
    $util_class_name = lib::get_class_name( 'util' );

    $db_sibling_assignment = $db_assignment->get_sibling_assignment();
    if( is_null( $db_sibling_assignment ) )
      throw lib::create( 'exception\notice',
        'A sibling assignment is required',  __METHOD__ );

    $user_ids = $db_assignment->get_reassign_user();
    if( 2 != count( $user_ids ) )
      throw lib::create( 'exception\notice',
        'Two users with no language restrictions are required for reassigning',  __METHOD__ );

    // we have two user ids which now need to be identified
    // with the current two assignments
    $reset_1 = reset( $user_ids );
    $user_id_1 = key( $user_ids );
    unset( $user_ids[ $user_id_1 ] );
    $reset_2 = reset( $user_ids );
    $user_id_2 = key( $user_ids );

    $assignment_reset = array();
    if( $reset_1[0] )
      $assignment_reset[ $reset_1[1] ] = $user_id_1;

    if( $reset_2[0] )
      $assignment_reset[ $reset_2[1] ] = $user_id_2;

    // remove any adjudications associated with this participant
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'participant_id', '=', $db_assignment->participant_id );
    foreach( $test_entry_class_name::select( $modifier ) as $db_adjudicate_entry )
    {
      $mod = lib::create( 'database\modifier' );
      $mod->where( 'test_entry_id', '=', $db_adjudicate_entry->id );
      $sql = sprintf( 'DELETE FROM test_entry_%s %s',
        $db_adjudicate_entry->get_test()->get_test_type()->name,
        $mod->get_sql() );
      $test_entry_class_name::db()->execute( $sql );
      $db_adjudicate_entry->delete();
    }

    $db_assignment->end_datetime = NULL;
    $db_sibling_assignment->end_datetime = NULL;
    $now_date_obj = $util_class_name::get_datetime_object();
    if( array_key_exists( $db_assignment->id, $assignment_reset ) )
    {
      $db_assignment->user_id = $assignment_reset[ $db_assignment->id ];
      $db_assignment->start_datetime = $now_date_obj->format( 'Y-m-d H:i:s' );
    }

    if( array_key_exists( $db_sibling_assignment->id, $assignment_reset ) )
    {
      $db_sibling_assignment->user_id = $assignment_reset[ $db_sibling_assignment->id ];
      $db_sibling_assignment->start_datetime = $now_date_obj->format( 'Y-m-d H:i:s' );
    }
    $db_assignment->save();
    $db_sibling_assignment->save();

    // delete the test_entry records as required
    if( 0 < count( $assignment_reset ) )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'assignment_id', 'IN', array_keys( $assignment_reset ) );
      foreach( $test_entry_class_name::select( $modifier ) as $db_test_entry )
      {
        $mod = lib::create( 'database\modifier' );
        $mod->where( 'test_entry_id', '=', $db_test_entry->id );
        $sql = sprintf( 'DELETE FROM test_entry_note %s',
          $mod->get_sql() );
        $test_entry_class_name::db()->execute( $sql );
        $sql = sprintf( 'DELETE FROM test_entry_%s %s',
          $db_test_entry->get_test()->get_test_type()->name,
          $mod->get_sql() );
        $test_entry_class_name::db()->execute( $sql );
        $db_test_entry->delete();
      }
    }

    // initialize each assignment as required
    if( array_key_exists( $db_assignment->id, $assignment_reset ) )
      $db_assignment->initialize();
    if( array_key_exists( $db_sibling_assignment->id, $assignment_reset ) )
      $db_sibling_assignment->initialize();
  }

  /**
   * Purge an assigment of its test_entry records including any adjudicates
   * associated with the participant.  If a sibling assignment exists, reset
   * its test_entry records' adjudicate states to default (NULL).
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param  database\assignment $db_assignment
   * @access public
   */
  public static function purge_assignment( $db_assignment )
  {
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );

    // remove any adjudications associated with this participant
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'participant_id', '=', $db_assignment->participant_id );
    foreach( $test_entry_class_name::select( $modifier ) as $db_adjudicate_entry )
    {
      $mod = lib::create( 'database\modifier' );
      $mod->where( 'test_entry_id', '=', $db_adjudicate_entry->id );
      $sql = sprintf( 'DELETE FROM test_entry_%s %s',
        $db_adjudicate_entry->get_test()->get_test_type()->name,
        $mod->get_sql() );
      $test_entry_class_name::db()->execute( $sql );
      $db_adjudicate_entry->delete();
    }

    $db_sibling_assignment = $db_assignment->get_sibling_assignment();
    if( !is_null( $db_sibling_assignment ) )
    {
      $db_sibling_assignment->end_datetime = NULL;
      $db_sibling_assignment->save();
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'assignment_id', '=', $db_sibling_assignment->id );
      // reset adjudicate status to null for all test_entry records
      $sql = sprintf(
        'UPDATE test_entry '.
        'SET adjudicate = NULL %s',
        $modifier->get_sql() );
      $test_entry_class_name::db()->execute( $sql );
    }

    // delete test_entry and daughter entry records
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'assignment_id', '=', $db_assignment->id );
    foreach( $test_entry_class_name::select( $modifier ) as $db_test_entry )
    {
      $mod = lib::create( 'database\modifier' );
      $mod->where( 'test_entry_id', '=', $db_test_entry->id );
      $sql = sprintf( 'DELETE FROM test_entry_note %s',
        $mod->get_sql() );
      $test_entry_class_name::db()->execute( $sql );
      $sql = sprintf( 'DELETE FROM test_entry_%s %s',
        $db_test_entry->get_test()->get_test_type()->name,
        $mod->get_sql() );
      $test_entry_class_name::db()->execute( $sql );
      $db_test_entry->delete();
    }
  }

  /**
   * Update an assigment and its sibling assignment end_datetime
   * based on their test_entry complete, deferred and adjudicate status's.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param  database\assignment $db_assignment
   * @access public
   */
  public static function complete_assignment( $db_assignment )
  {
    $assignment_class_name = lib::get_class_name( 'database\assignment' );
    $database_class_name = lib::get_class_name( 'database\database' );
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );

    $db_sibling_assignment = $db_assignment->get_sibling_assignment();

    if( $assignment_class_name::all_tests_complete( $db_assignment->id ) )
    {
      if( !is_null( $db_sibling_assignment ) &&
          $assignment_class_name::all_tests_complete( $db_sibling_assignment->id ) )
      {
        // go through all the tests and look for differences
        // get all the assignment's tests
        $session = lib::create( 'business\session' );
        $session->acquire_semaphore();

        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'assignment_id', '=', $db_assignment->id );
        $modifier->where( 'completed', '=', true );
        $modifier->where( 'IFNULL( deferred, "NULL" )', 'NOT IN',
          $test_entry_class_name::$deferred_states );

        $completed = true;
        foreach( $test_entry_class_name::select( $modifier ) as $db_test_entry )
        {
          $db_sibling_test_entry = $db_test_entry->get_sibling_test_entry();
          if( !$db_test_entry->compare( $db_sibling_test_entry ) )
          {
            if( ( is_null( $db_test_entry->adjudicate ) ||
                  is_null( $db_sibling_test_entry->adjudicate ) ) ||
                 ( true == $db_test_entry->adjudicate ||
                   true == $db_sibling_test_entry->adjudicate ) )
            {
              $db_test_entry->adjudicate = true;
              $db_test_entry->save();
              $db_sibling_test_entry->adjudicate = true;
              $db_sibling_test_entry->save();
              $completed = false;
            }
          }
          else
          {
            // if they are identical check if there is an adjudicate entry and delete it
            $db_test = $db_test_entry->get_test();
            $db_adjudicate_test_entry = $test_entry_class_name::get_unique_record(
              array( 'test_id', 'participant_id' ),
              array( $db_test->id, $db_assignment->get_participant()->id ) );
            if( !is_null( $db_adjudicate_test_entry ) )
            {
              // delete test_entry daughter record(s)
              $sql = sprintf(
                'DELETE FROM test_entry_%s '.
                'WHERE test_entry_id = %s',
                $db_test->get_test_type()->name,
                $database_class_name::format_string( $db_adjudicate_test_entry->id ) );

              $test_entry_class_name::db()->execute( $sql );
              $db_adjudicate_test_entry->delete();
            }

            if( !is_null( $db_test_entry->adjudicate ) )
            {
              $db_test_entry->adjudicate = NULL;
              $db_test_entry->save();
            }
            if( !is_null( $db_sibling_test_entry->adjudicate ) )
            {
              $db_sibling_test_entry->adjudicate = NULL;
              $db_sibling_test_entry->save();
            }
          }
        }
        $session->release_semaphore();

        if( $completed )
        {
          // both assignments are now complete: set their end datetimes
          $end_datetime = util::get_datetime_object()->format( "Y-m-d H:i:s" );
          $db_assignment->end_datetime = $end_datetime;
          $db_sibling_assignment->end_datetime = $end_datetime;
          $db_assignment->save();
          $db_sibling_assignment->save();
        }
        else
        {
          if( !is_null( $db_assignment->end_datetime ) ||
              !is_null( $db_sibling_assignment->end_datetime ) )
          {
            $db_assignment->end_datetime = NULL;
            $db_sibling_assignment->end_datetime = NULL;
            $db_assignment->save();
            $db_sibling_assignment->save();
          }
        }
      }
    }
    else
    {
      if( !is_null( $db_assignment->end_datetime ) )
      {
        $db_assignment->end_datetime = NULL;
        $db_assignment->save();
      }
      if( !is_null( $db_sibling_assignment ) && !is_null( $db_sibling_assignment->end_datetime ) )
      {
        $db_sibling_assignment->end_datetime = NULL;
        $db_sibling_assignment->save();
      }
    }
  }

  /**
   * Update test_entry, its assignment, its sibling assignment and its sibling
   * assignment's test_entry based on its complete status.  This method is
   * typically called whenever a daughter table entry is edited.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param  database\test_entry $db_test_entry
   * @access public
   */
  public static function complete_test_entry( $db_test_entry )
  {
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );

    $db_test_entry->completed = $db_test_entry->is_completed();
    if( in_array( $db_test_entry->deferred, $test_entry_class_name::$deferred_states ) )
    {
      if( $db_test_entry->completed && 'pending' ==  $db_test_entry->deferred )
        $db_test_entry->deferred = 'resolved';
      else if( !$db_test_entry->completed )
        $db_test_entry->deferred = NULL;
    }

    $db_test_entry->save();

    // no further processing is required for an adjudicate entry
    if( is_null( $db_test_entry->participant_id ) )
    {
      $db_assignment = $db_test_entry->get_assignment();

      // check if the assignment can be completed
      if( $db_test_entry->completed &&
          !in_array( $db_test_entry->deferred, $test_entry_class_name::$deferred_states ) )
      {
        // the assignment will determine the adjudicate status
        static::complete_assignment( $db_assignment );
      }
      else
      {
        if( !is_null( $db_assignment->end_datetime ) )
        {
          $db_assignment->end_datetime = NULL;
          $db_assignment->save();
          log::warning( 'checked assignment null end_datetime in complete_test_entry' );
        }
      }
    }
  }

  /**
   * Get the data for adjudicating a test_entry. This method creates a new test_entry
   * independent of any assignment, but linked to the progenitor test_entry's by
   * their assignment's common participant id.  The test_entry is initialized and
   * populated with data common to both progenitors, reserving uninitialized daughter entries
   * for the adjudication.  The progenitor data generated by this method is presented at the
   * UI layer for adjudication by an administrator or supervisor.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param  database\test_entry $db_test_entry
   * @throws exception\runtime
   * @return array() Data required for adjudication at the UI layer
   * @access public
   */
  public static function get_adjudicate_data( $db_test_entry )
  {
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );
    $db_test = $db_test_entry->get_test();
    $test_type_name = $db_test->get_test_type()->name;
    $entry_class_name = lib::get_class_name( 'database\test_entry_' . $test_type_name );

    if( is_null( $db_test_entry->adjudicate ) ||
       !$db_test_entry->completed ||
        in_array( $db_test_entry->deferred, $test_entry_class_name::$deferred_states ) )
      throw lib::create( 'exception\runtime', 'Invalid test entry', __METHOD__ );

    // get the sibling entry
    $db_assignment = $db_test_entry->get_assignment();
    $db_sibling_test_entry = $db_test_entry->get_sibling_test_entry();

    if( is_null( $db_sibling_test_entry ) ||
        is_null( $db_sibling_test_entry->adjudicate ) ||
        !$db_sibling_test_entry->completed ||
        in_array( $db_test_entry->deferred, $test_entry_class_name::$deferred_states ) )
      throw lib::create( 'exception\runtime', 'Invalid sibling test entry', __METHOD__ );

    $db_test_entry->trim();
    $db_sibling_test_entry->trim();

    $get_list_function = 'get_test_entry_' . $test_type_name . '_list';

    // if we havent created the adjudicate entry, do so now
    $db_adjudicate_test_entry = $test_entry_class_name::get_unique_record(
      array( 'test_id', 'participant_id' ),
      array( $db_test->id, $db_assignment->get_participant()->id ) );

    if( is_null( $db_adjudicate_test_entry ) )
    {
      // create a new test entry to hold the data
      $db_adjudicate_test_entry = lib::create( 'database\test_entry' );
      $db_adjudicate_test_entry->participant_id = $db_assignment->get_participant()->id;
      $db_adjudicate_test_entry->test_id = $db_test->id;
      $db_adjudicate_test_entry->save();
      $db_adjudicate_test_entry->initialize( false );
    }

    $audio_status_list = $test_entry_class_name::get_enum_values( 'audio_status' );
    $audio_status_list = array_combine( $audio_status_list, $audio_status_list );
    $audio_status_list = array_reverse( $audio_status_list, true );
    $audio_status_list['NULL'] = '';
    $audio_status_list = array_reverse( $audio_status_list, true );

    $participant_status_list = $test_entry_class_name::get_enum_values( 'participant_status' );
    $participant_status_list = array_combine( $participant_status_list, $participant_status_list );
    $participant_status_list = array_reverse( $participant_status_list, true );
    $participant_status_list['NULL'] = '';
    $participant_status_list = array_reverse( $participant_status_list, true );

    // only classification tests (FAS and AFT) require prompt status
    if( 'classification' != $test_type_name )
    {
      unset( $participant_status_list['suspected prompt'],
             $participant_status_list['prompted'] );
    }

    $status_data = array();
    $status_data[] = array(
      'status_label' => 'Audio Status',
      'status_type' => 'audio',
      'id_1' => $db_test_entry->id,
      'id_2' => $db_sibling_test_entry->id,
      'id_3' => $db_adjudicate_test_entry->id,
      'status_1' =>
        array_search( $db_test_entry->audio_status, $audio_status_list ),
      'status_2' =>
        array_search( $db_sibling_test_entry->audio_status, $audio_status_list ),
      'status_3' =>
        array_search( $db_adjudicate_test_entry->audio_status, $audio_status_list ),
      'status_list' => $audio_status_list,
      'adjudicate' => $db_test_entry->audio_status != $db_sibling_test_entry->audio_status );

    $status_data[] = array(
      'status_label' => 'Participant Status',
      'status_type' => 'participant',
      'id_1' => $db_test_entry->id,
      'id_2' => $db_sibling_test_entry->id,
      'id_3' => $db_adjudicate_test_entry->id,
      'status_1' =>
        array_search( $db_test_entry->participant_status, $participant_status_list ),
      'status_2' =>
        array_search( $db_sibling_test_entry->participant_status, $participant_status_list ),
      'status_3' =>
        array_search( $db_adjudicate_test_entry->participant_status, $participant_status_list ),
      'status_list' => $participant_status_list,
      'adjudicate' => $db_test_entry->participant_status != $db_sibling_test_entry->participant_status );

    $entry_data = array();

    if( 'confirmation' == $test_type_name )
    {
      $obj_list = array(
        current( $db_test_entry->$get_list_function() ),
        current( $db_sibling_test_entry->$get_list_function() ),
        current( $db_adjudicate_test_entry->$get_list_function() ) );

      $adjudicate = current( $obj_list )->confirmation != next( $obj_list )->confirmation;
      if( !$adjudicate )
      {
        $confirmation = current( $obj_list )->confirmation;
        end( $obj_list )->confirmation = $confirmation;
        end( $obj_list )->save();
      }

      reset( $obj_list );
      for( $i = 1; $i <= 3; $i++ )
      {
        $obj = current( $obj_list );
        $entry_data[ 'id_' . $i ] = $obj->id;
        $entry_data[ 'confirmation_' . $i ] =
          is_null( $obj->confirmation ) ? '' : $obj->confirmation;
        next( $obj_list );
      }

      $entry_data[ 'adjudicate' ] = $adjudicate;
    }
    else
    {
      $classification = array_combine(
        array( $db_test->dictionary_id,
               $db_test->intrusion_dictionary_id,
               $db_test->variant_dictionary_id ),
        array( 'primary', 'intrusion', 'variant' ) );

      if( 'alpha_numeric' == $test_type_name || 'classification' == $test_type_name )
      {
        $modifier = lib::create( 'database\modifier' );
        $modifier->order( 'rank' );
        $a = $db_test_entry->$get_list_function( clone $modifier );
        $b = $db_sibling_test_entry->$get_list_function( clone $modifier );
        $c = $db_adjudicate_test_entry->$get_list_function( clone $modifier );

        $max_count = max( count( $a ), count( $b ) );
        if( count( $c ) > $max_count )
        {
          $db_adjudicate_test_entry->truncate( count( $c ) - $max_count );
        }
        else if( count( $c ) < $max_count )
        {
          $db_max_rank_entry = $max_count == count( $a ) ? end( $a ) : end( $b );
          reset( $a );
          reset( $b );

          //create additional entries if necessary
          $c_obj = end( $c );
          for( $rank = $c_obj->rank + 1; $rank <= $db_max_rank_entry->rank; $rank++ )
          {
            $db_entry = lib::create( 'database\test_entry_' . $test_type_name );
            $db_entry->test_entry_id = $db_adjudicate_test_entry->id;
            $db_entry->rank = $rank;
            $db_entry->save();
          }
        }
        $c = $db_adjudicate_test_entry->$get_list_function( clone $modifier );
        $rank = 1;
        while( !is_null( key( $a ) ) || !is_null( key( $b ) ) || !is_null( key( $c ) ) )
        {
          $a_obj = current( $a );
          $b_obj = current( $b );
          $c_obj = current( $c );

          $id_1 = '';
          $id_2 = '';
          $id_3 = $c_obj->id;
          $word_1 = '';
          $word_id_1 = '';
          $word_2 = '';
          $word_id_2 = '';
          $word_3 = '';
          $word_id_3 = '';
          $adjudicate = false;

          // unequal number of list elements case
          if( false === $a_obj )
          {
            $adjudicate = true;
            $id_2 = $b_obj->id;
            if( !is_null( $b_obj->word_id ) )
            {
              $db_word = lib::create( 'database\word', $b_obj->word_id );
              $word_2 = $db_word->word;
              $word_id_2 = $db_word->id;
            }
          }
          // unequal number of list elements case
          else if( false === $b_obj )
          {
            $adjudicate = true;
            $id_1 = $a_obj->id;
            if( !is_null( $a_obj->word_id ) )
            {
              $db_word = lib::create( 'database\word', $a_obj->word_id );
              $word_1 = $db_word->word;
              $word_id_1 = $db_word->id;
            }
          }
          else
          {
            $id_1 = $a_obj->id;
            $id_2 = $b_obj->id;
            $adjudicate = $a_obj->word_id != $b_obj->word_id;

            //copy the progenitor to the adjudicate
            if( !$adjudicate )
            {
              $c_obj->word_id = $a_obj->word_id;
              $c_obj->save();
            }

            if( !is_null( $a_obj->word_id ) )
            {
              $db_word = lib::create( 'database\word', $a_obj->word_id );
              $word_id_1 = $db_word->id;
              $word_1 = $db_word->word;
            }

            if( !is_null( $b_obj->word_id ) )
            {
              $db_word = lib::create( 'database\word', $b_obj->word_id );
              $word_id_2 = $db_word->id;
              $word_2 = $db_word->word;
            }
          }

          if( !is_null( $c_obj->word_id ) )
          {
            $db_word = lib::create( 'database\word', $c_obj->word_id );
            $word_id_3 = $db_word->id;
            $word_3 = $db_word->word;
          }

          $row = array(
                   'id_1' => $id_1,
                   'id_2' => $id_2,
                   'id_3' => $id_3,
                   'rank' => $rank,
                   'word_id_1' => $word_id_1,
                   'word_1' => $word_1,
                   'word_id_2' => $word_id_2,
                   'word_2' => $word_2,
                   'adjudicate' => $adjudicate,
                   'word_id_3' => $word_id_3,
                   'word_3' => $word_3 );

          // get word classfications
          if( 'classification' == $test_type_name )
          {
            for( $i = 1; $i <= 3; $i++ )
            {
              $classification_i = '';
              $word_id_i = "word_id_$i";
              if( '' !== $$word_id_i )
              {
                $db_word = lib::create( 'database\word', $$word_id_i );
                $dictionary_id = $db_word->dictionary_id;
                $classification_i = array_key_exists( $dictionary_id, $classification ) ?
                $classification[ $dictionary_id ] : '';
              }
              $row[ 'classification_' . $i ] = $classification_i;
            }
          }

          $entry_data[] = $row;
          $rank = $rank + 1;
          next( $a );
          next( $b );
          next( $c );
        }
      }
      else if( 'ranked_word' == $test_type_name )
      {
        $db_language = $db_test_entry->get_assignment()->get_participant()->get_language();
        if( is_null( $db_language ) )
          $db_language = lib::create( 'business\session' )->get_service()->get_language();

        $modifier = lib::create( 'database\modifier' );
        $modifier->order( 'id' );
        $a = $db_test_entry->$get_list_function( clone $modifier );
        $b = $db_sibling_test_entry->$get_list_function( clone $modifier );
        $c = $db_adjudicate_test_entry->$get_list_function( clone $modifier );

        $max_count = max( count( $a ), count( $b ) );
        if( count( $c ) > $max_count )
        {
          $db_adjudicate_test_entry->truncate( count( $c ) - $max_count );
        }
        else if( count( $c ) < $max_count )
        {
          //create additional entries if necessary
          $num = $max_count - count( $c );
          for( $i = 0 ; $i < $num; $i++ )
          {
            $db_entry = lib::create( 'database\test_entry_ranked_word' );
            $db_entry->test_entry_id = $db_adjudicate_test_entry->id;
            $db_entry->save();
          }
        }
        $c = $db_adjudicate_test_entry->$get_list_function( clone $modifier );
        while( !is_null( key( $a ) ) || !is_null( key ( $b ) ) || !is_null( key( $c ) ) )
        {
          $a_obj = current( $a );
          $b_obj = current( $b );
          $c_obj = current( $c );

          $id_1 = '';
          $id_2 = '';
          $id_3 = $c_obj->id;
          $word_id_1 = '';
          $word_id_2 = '';
          $word_id_3 = '';
          $word_1 = '';
          $word_2 = '';
          $word_3 = '';
          $classification_1 = '';
          $classification_2 = '';
          $classification_3 = '';
          $selection_1 = '';
          $selection_2 = '';
          $selection_3 = '';
          $ranked_word_set_id = '';
          $ranked_word_set_word = '';
          $adjudicate = false;

          // unequal number of list elements case
          if( false === $a_obj )
          {
            $adjudicate = true;
            $id_2 = $b_obj->id;
            $selection_2 = is_null( $b_obj->selection ) ? '' : $b_obj->selection;
            $ranked_word_set_id = is_null( $b_obj->ranked_word_set_id ) ? '' :
              $b_obj->ranked_word_set_id;
            if( !is_null( $b_obj->word_id ) )
            {
              $db_word = lib::create( 'database\word', $b_obj->word_id );
              $word_2 = $db_word->word;
              $word_id_2 = $db_word->id;
              $dictionary_id = $db_word->dictionary_id;
              $classification_2 = array_key_exists( $dictionary_id, $classification ) ?
                $classification[ $dictionary_id ] : '';
            }
          }
          // unequal number of list elements case
          else if( false === $b_obj )
          {
            $adjudicate = true;
            $id_1 = $a_obj->id;
            $selection_1 = is_null( $a_obj->selection ) ? '' : $a_obj->selection;
            $ranked_word_set_id = is_null( $a_obj->ranked_word_set_id ) ? '' :
              $a_obj->ranked_word_set_id;
            if( !is_null( $a_obj->word_id ) )
            {
              $db_word = lib::create( 'database\word', $a_obj->word_id );
              $word_1 = $db_word->word;
              $word_id_1 = $db_word->id;
              $dictionary_id = $db_word->dictionary_id;
              $classification_1 = array_key_exists( $dictionary_id, $classification ) ?
                $classification[ $dictionary_id ] : '';
            }
          }
          else
          {
            $id_1 = $a_obj->id;
            $id_2 = $b_obj->id;
            $selection_1 = $a_obj->selection;
            $selection_2 = $b_obj->selection;

            if( !is_null( $a_obj->ranked_word_set_id ) && !is_null( $b_obj->ranked_word_set_id ) )
            {
              if( $a_obj->ranked_word_set_id != $b_obj->ranked_word_set_id )
                throw lib::create( 'exception\runtime',
                  'Invalid test entry ranked word pair', __METHOD__ );

              $ranked_word_set_word = $a_obj->get_ranked_word_set()->get_word( $db_language )->word;
              $ranked_word_set_id = $a_obj->get_ranked_word_set()->id;
            }

            $adjudicate = ( $a_obj->word_id != $b_obj->word_id ||
                            $a_obj->selection != $b_obj->selection );

            if( 'variant' == $a_obj->selection && 'variant' == $b_obj->selection &&
                ( is_null( $a_obj->word_id ) || is_null( $b_obj->word_id ) ) ) $adjudicate = true;

            // copy the progenitor to the adjudicate
            if( !$adjudicate )
            {
              $c_obj->word_id= $a_obj->word_id;
              $c_obj->selection = $a_obj->selection;
              $c_obj->ranked_word_set_id = $ranked_word_set_id;
              $c_obj->save();
            }

            if( !is_null( $a_obj->word_id ) )
            {
              $db_word = lib::create( 'database\word', $a_obj->word_id );
              $word_id_1 = $db_word->id;
              $word_1 = $db_word->word;
              $dictionary_id = $db_word->dictionary_id;
              $classification_1 = array_key_exists( $dictionary_id, $classification ) ?
                $classification[ $dictionary_id ] : '';
            }

            if( !is_null( $b_obj->word_id ) )
            {
              $db_word = lib::create( 'database\word', $b_obj->word_id );
              $word_id_2 = $db_word->id;
              $word_2 = $db_word->word;
              $dictionary_id = $db_word->dictionary_id;
              $classification_2 = array_key_exists( $dictionary_id, $classification ) ?
                $classification[ $dictionary_id ] : '';
            }
          }

          if( !is_null( $c_obj->word_id ) )
          {
            $db_word = lib::create( 'database\word', $c_obj->word_id );
            $word_id_3 = $db_word->id;
            $word_3 = $db_word->word;
            $dictionary_id = $db_word->dictionary_id;
            $classification_3 = array_key_exists( $dictionary_id, $classification ) ?
              $classification[ $dictionary_id ] : '';
          }
          if( !is_null( $c_obj->selection ) )  $selection_3 = $c_obj->selection;

          $entry_data[] = array(
                   'id_1' => $id_1,
                   'id_2' => $id_2,
                   'id_3' => $id_3,
                   'ranked_word_set_id' => $ranked_word_set_id,
                   'ranked_word_set_word' => $ranked_word_set_word,
                   'selection_1' => $selection_1,
                   'selection_2' => $selection_2,
                   'selection_3' => $selection_3,
                   'word_id_1' => $word_id_1,
                   'word_1' => $word_1,
                   'classification_1' => $classification_1,
                   'word_id_2' => $word_id_2,
                   'word_2' => $word_2,
                   'classification_2' => $classification_2,
                   'word_id_3' => $word_id_3,
                   'word_3' => $word_3,
                   'classification_3' => $classification_3,
                   'adjudicate' => $adjudicate );

          next( $a );
          next( $b );
          next( $c );
        }
      }
    }

    return array( 'entry_data' => $entry_data, 'status_data' => $status_data );
  }
}
