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
   * Initialize an assignment.  All existing test_entry records are deleted
   * and new test_entry records are created.
   * Only assigments that have never been adjudicated or finished can be initialized.
   * This method is typically called during creation of a db_assignment or
   * to reset a db_test_entry.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @param  database\assignment $db_assignment
   * @param  database\test_entry $db_test_entry
   * @access public
   */
  public static function initialize_assignment( $db_assignment, $db_test_entry = NULL )
  {
    if( !is_null( $db_assignment->end_datetime ) )
      throw lib::create( 'exception\notice',
        'The assignment for participant UID ' . $db_assignment->get_participant()->uid .
        'is closed and cannot be initialized', __METHOD__ );

    $test_class_name = lib::get_class_name( 'database\test' );
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );
    $test_entry_note_class_name = lib::get_class_name( 'database\test_entry_note' );

    $db_participant = $db_assignment->get_participant();

    // delete test_entry daughter record(s)
    if( !is_null( $db_test_entry ) )
    {
      $sql = sprintf( 'DELETE FROM test_entry_'.
        $db_test_entry->get_test()->get_test_type()->name .
        ' WHERE test_entry_id = %d', $db_test_entry->id );
      $test_entry_class_name::db()->execute( $sql );

      // initialize new daughter entries
      static::initialize_test_entry( $db_test_entry );

      // get sibling assignment, reset test_entry adjudicate value from 1 to NULL
      $db_sibling_assignment = $db_assignment->get_sibling_assignment();
      if( !is_null( $db_sibling_assignment ) )
      {
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'test_id', '=', $db_test_entry->get_test()->id );
        $modifier->where( 'adjudicate', '=', true );
        $modifier->where( 'assignment_id', '=', $db_sibling_assignment->id );
        $db_sibling_test_entry = current( $test_entry_class_name::select( $modifier ) );
        if( false !== $db_sibling_test_entry )
        {
          $db_sibling_test_entry->adjudicate = NULL;
          $db_sibling_test_entry->save();
        }
      }
    }
    else
    {
      $test_mod = NULL;
      if( $db_participant->get_cohort()->name == 'tracking' )
      {
        $test_mod = lib::create( 'database\modifier' );
        $test_mod->where( 'name', 'NOT LIKE', 'FAS%' );
      }

      // create test_entry record(s)
      foreach( $test_class_name::select( $test_mod ) as $db_test )
      {
        $db_test_entry = lib::create( 'database\test_entry' );
        $db_test_entry->test_id = $db_test->id;
        $db_test_entry->assignment_id = $db_assignment->id;
        $db_test_entry->save();
        // create daughter entry record(s)
        static::initialize_test_entry( $db_test_entry );
      }
    }
  }

  /**
   * Initialize a test_entry.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param  database\test_entry $db_test_entry
   * @access public
   */
  public static function initialize_test_entry( $db_test_entry )
  {
    $word_class_name = lib::get_class_name( 'database\word' );

    $db_test = $db_test_entry->get_test();
    $test_type_name = $db_test->get_test_type()->name;
    $entry_class_name = 'test_entry_' . $test_type_name;

    $db_assignment = $db_test_entry->get_assignment();
    // if null db_assignment then db_test_entry is an ajudication
    if( is_null( $db_assignment ) )
      $db_participant = $db_test_entry->get_participant();
    else
      $db_participant = $db_assignment->get_participant();

    $db_language = $db_participant->get_language();
    if( is_null( $db_language ) )
      $db_language = lib::create( 'business\session' )->get_service()->get_language();

    if( $test_type_name == 'ranked_word' )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->order( 'rank' );
      foreach( $db_test->get_ranked_word_set_list( $modifier ) as $db_ranked_word_set )
      {
        $db_test_entry_ranked_word = lib::create( 'database\\'. $entry_class_name );
        $db_test_entry_ranked_word->ranked_word_set_id = $db_ranked_word_set->id;
        $db_test_entry_ranked_word->test_entry_id = $db_test_entry->id;
        $db_test_entry_ranked_word->save();
      }
    }
    else if( $test_type_name == 'confirmation' )
    {
      $db_test_entry_confirmation = lib::create( 'database\\'. $entry_class_name );
      $db_test_entry_confirmation->test_entry_id = $db_test_entry->id;
      $db_test_entry_confirmation->save();
    }
    else if( $test_type_name == 'classification' )
    {
      $setting_manager = lib::create( 'business\setting_manager' );
      $max_rank = $setting_manager->get_setting( 'interface', 'classification_max_rank' );
      for( $rank = 1; $rank <= $max_rank; $rank++ )
      {
        $db_test_entry_classification = lib::create( 'database\\'. $entry_class_name );
        $db_test_entry_classification->test_entry_id = $db_test_entry->id;
        $db_test_entry_classification->rank = $rank;
        $db_test_entry_classification->save();
      }
    }
    else if( $test_type_name == 'alpha_numeric' )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'language_id', '=', $db_language->id );
      $word_count = $db_test->get_dictionary()->get_word_count( $modifier );
      for( $rank = 1; $rank <= $word_count; $rank++ )
      {
        $db_test_entry_alpha_numeric = lib::create( 'database\\'. $entry_class_name );
        $db_test_entry_alpha_numeric->test_entry_id = $db_test_entry->id;
        $db_test_entry_alpha_numeric->rank = $rank;
        $db_test_entry_alpha_numeric->save();
      }
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
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );

    $db_sibling_assignment = $db_assignment->get_sibling_assignment();

    if( $db_assignment->all_tests_complete() )
    {
      if( !is_null( $db_sibling_assignment ) && $db_sibling_assignment->all_tests_complete() )
      {
        // go through all the tests and look for differences
        // get all the assignment's tests
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'assignment_id', '=', $db_assignment->id );
        $modifier->where( 'completed', '=', true );
        $modifier->where( 'deferred', '=', false );
        $complete = true;
        foreach( $test_entry_class_name::select( $modifier ) as $db_test_entry )
        {
          $db_sibling_test_entry = $db_test_entry->get_sibling_test_entry();
          if( !$db_test_entry->compare( $db_sibling_test_entry ) )
          {
            $db_test_entry->adjudicate = true;
            $db_test_entry->save();
            $db_sibling_test_entry->adjudicate = true;
            $db_sibling_test_entry->save();
            $complete = false;
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
              $sql = sprintf( 'DELETE FROM test_entry_'.
                $db_test->get_test_type()->name .
                ' WHERE test_entry_id = %d', $db_test_entry->id );
              $test_entry_class_name::db()->execute( $sql );
              $db_adjudicate_test_entry->delete();
            }
          }
        }

        if( $complete )
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
   * Update test_entry, its assigment, its sibling assignment and its sibling
   * assignment's test_entry based on its complete status.  This method is
   * typically called whenever a daughter table entry is edited.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param  database\test_entry $db_test_entry
   * @access public
   */
  public static function complete_test_entry( $db_test_entry )
  {
    $db_test_entry->completed = $db_test_entry->is_completed();
    $db_test_entry->save();

    // no further processing is required for an adjudicate entry
    if( is_null( $db_test_entry->participant_id ) )
    {
      // check if the assignment can be completed
      if( $db_test_entry->completed && !$db_test_entry->deferred )
      {
        $db_assignment = $db_test_entry->get_assignment();

        // the assignment will determine the adjudicate status
        static::complete_assignment( $db_assignment );
      }
    }
  }

  /**
   * Get the data for adjudicating a test_entry. This method creates a new test_entry
   * independent of any assignment, but linked to the progenitor test_entry's by
   * their assignment's common participant id.  The test_entry is initialized and
   * populated with data common to both progenitors, reserving uninitialized daughter entries
   * for the adjudication.  The progenitor data generated by this method is presented at the
   * UI layer for adjudication by an administrator.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param  database\test_entry $db_test_entry
   * @throws exception\runtime
   * @return array() Data required for adjudication at the UI layer
   * @access public
   */
  public static function get_adjudicate_data( $db_test_entry )
  {
    $ranked_word_set_has_language_class_name =
      lib::get_class_name( 'database\ranked_word_set_has_language' );
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );
    $db_test = $db_test_entry->get_test();
    $test_type_name = $db_test->get_test_type()->name;
    $entry_class_name = lib::get_class_name( 'database\test_entry_' . $test_type_name );

    if( $db_test_entry->adjudicate != true ||
       !$db_test_entry->completed ||
        $db_test_entry->deferred )
      throw lib::create( 'exception\runtime', 'Invalid test entry', __METHOD__ );

    $adjudicate_data = NULL;

    // get the sibling entry
    $db_assignment = $db_test_entry->get_assignment();
    $db_sibling_assignment = $db_assignment->get_sibling_assignment();
    if( !is_null( $db_sibling_assignment ) )
    {
      $db_sibling_test_entry = $test_entry_class_name::get_unique_record(
        array( 'test_id', 'assignment_id' ),
        array( $db_test->id, $db_sibling_assignment->id ) );

      if( is_null( $db_sibling_test_entry ) || $db_sibling_test_entry->adjudicate != true ||
          !$db_sibling_test_entry->completed || $db_sibling_test_entry->deferred )
        throw lib::create( 'exception\runtime', 'Invalid sibling test entry', __METHOD__ );

      if( $db_sibling_test_entry->adjudicate == true )
      {
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
          static::initialize_test_entry( $db_adjudicate_test_entry );
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
        if( $test_type_name != 'classification' )
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

        if( $test_type_name == 'confirmation' )
        {
          $a = current( $db_test_entry->$get_list_function() );
          $b = current( $db_sibling_test_entry->$get_list_function() );
          $c = current(  $db_adjudicate_test_entry->$get_list_function() );

          $entry_data[ 'id_1' ] = $a->id;
          $entry_data[ 'id_2' ] = $b->id;
          $entry_data[ 'id_3' ] = $c->id;
          $entry_data[ 'confirmation_1' ] = $a->confirmation;
          $entry_data[ 'confirmation_2' ] = $b->confirmation;
          $entry_data[ 'adjudicate' ] = $a->confirmation != $b->confirmation;
        }
        else
        {
          $classification = array_combine(
            array( $db_test->dictionary_id,
                   $db_test->intrusion_dictionary_id,
                   $db_test->variant_dictionary_id ),
            array( 'primary', 'intrusion', 'variant' ) );

          if( $test_type_name == 'alpha_numeric' || $test_type_name == 'classification' )
          {
            $rank_modifier = lib::create( 'database\modifier' );
            $rank_modifier->order( 'rank' );
            $a = $db_test_entry->$get_list_function( clone $rank_modifier );
            $b = $db_sibling_test_entry->$get_list_function( clone $rank_modifier );
            $c = $db_adjudicate_test_entry->$get_list_function( clone $rank_modifier );

            // get the max ranked entry that has something entered
            $max_rank_modifier = lib::create( 'database\modifier' );
            $max_rank_modifier->where( 'test_entry_id', 'IN',
              array( $db_test_entry->id, $db_sibling_test_entry->id ) );
            $max_rank_modifier->where( 'word_id', '!=', NULL );
            $max_rank_modifier->order_desc( 'rank' );
            $max_rank_modifier->limit( 1 );
            $db_max_rank_entry = current( $entry_class_name::select( $max_rank_modifier ) );

            // this record should never be empty if we got this far in the process
            if( false === $db_max_rank_entry )
             throw lib::create( 'exception\runtime',
               'Invalid max ranked test entry', __METHOD__ );

            //create additional entries if necessary
            $c_obj = end( $c );
            for( $rank = $c_obj->rank + 1; $rank <= $db_max_rank_entry->rank; $rank++ )
            {
              $db_entry = lib::create( 'database\test_entry_' . $test_type_name );
              $db_entry->test_entry_id = $db_adjudicate_test_entry->id;
              $db_entry->rank = $rank;
              $db_entry->save();
            }
            reset( $c );

            $rank = 1;
            $c = $db_adjudicate_test_entry->$get_list_function( clone $rank_modifier );
            while( ( !is_null( key( $a ) ) || !is_null( key( $b ) ) || !is_null( key( $c ) ) ) &&
                   $rank <= $db_max_rank_entry->rank )
            {
              $a_obj = current( $a );
              $b_obj = current( $b );
              $c_obj = current( $c );

              $id_1 = '';
              $id_2 = '';
              $id_3 = $c_obj->id;
              $word_id_1 = '';
              $word_id_2 = '';
              $word_1 = '';
              $word_2 = '';
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

              $row = array(
                       'id_1' => $id_1,
                       'id_2' => $id_2,
                       'id_3' => $id_3,
                       'rank' => $rank,
                       'word_id_1' => $word_id_1,
                       'word_1' => $word_1,
                       'word_id_2' => $word_id_2,
                       'word_2' => $word_2,
                       'adjudicate' => $adjudicate );

              // get word classfications
              if( $test_type_name == 'classification' )
              {
                $classification_1 = '';
                $classification_2 = '';
                if( $word_id_1 !== '' )
                {
                  $db_word = lib::create( 'database\word', $word_id_1 );
                  $dictionary_id = $db_word->dictionary_id;
                  $classification_1 = array_key_exists( $dictionary_id, $classification ) ?
                    $classification[ $dictionary_id ] : '';
                }
                if( $word_id_2 !== '' )
                {
                  $db_word = lib::create( 'database\word', $word_id_2 );
                  $dictionary_id = $db_word->dictionary_id;
                  $classification_2 = array_key_exists( $dictionary_id, $classification ) ?
                    $classification[ $dictionary_id ] : '';
                }

                $row['classification_1'] = $classification_1;
                $row['classification_2'] = $classification_2;
              }

              $entry_data[] = $row;
              $rank = $rank + 1;
              next( $a );
              next( $b );
              next( $c );
            }
          }
          else if( $test_type_name == 'ranked_word' )
          {
            $db_language = $db_test_entry->get_assignment()->get_participant()->get_language();
            if( is_null( $db_language ) )
              $db_language = lib::create( 'business\session' )->get_service()->get_language();

            $rank_modifier = lib::create( 'database\modifier' );
            $rank_modifier->order( 'ranked_word_set.rank' );
            $a = $db_test_entry->$get_list_function( clone $rank_modifier );
            $b = $db_sibling_test_entry->$get_list_function( clone $rank_modifier );
            $c = $db_adjudicate_test_entry->$get_list_function( clone $rank_modifier );

            // now get the intrusions and append them to the primary word entries
            $intrusion_modifier = lib::create( 'database\modifier' );
            $intrusion_modifier->where( 'selection', '=', NULL );
            $intrusion_modifier->where( 'ranked_word_set_id', '=', NULL );

            $a_intrusion = $db_test_entry->$get_list_function( clone $intrusion_modifier );
            $b_intrusion = $db_sibling_test_entry->$get_list_function( clone $intrusion_modifier );
            $c_intrusion = $db_adjudicate_test_entry->$get_list_function( clone $intrusion_modifier );

            if( 0 < count( $a_intrusion ) )
              $a = array_merge( $a, $a_intrusion );
            if( 0 < count( $b_intrusion ) )
              $b = array_merge( $b, $b_intrusion );
            if( 0 < count( $c_intrusion ) )
              $c = array_merge( $c, $c_intrusion );

            //create additional entries if necessary
            $count = abs( max( array( count( $a_intrusion ), count( $b_intrusion ) ) ) -
              count( $c_intrusion ) );
            if( 0 < $count )
            {
              for( $i = 0; $i < $count; $i++ )
              {
                $db_entry = lib::create( 'database\test_entry_' . $test_type_name );
                $db_entry->test_entry_id = $db_adjudicate_test_entry->id;
                $db_entry->save();
                array_push( $c, $db_entry );
              }
            }

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
              $word_1 = '';
              $word_2 = '';
              $classification_1 = '';
              $classification_2 = '';
              $selection_1 = '';
              $selection_2 = '';
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

                  $ranked_word_set_word = $a_obj->get_word( $db_language );
                  $ranked_word_set_id = $a_obj->get_ranked_word_set()->id;
                }

                $adjudicate = ( $a_obj->word_id != $b_obj->word_id ||
                                $a_obj->selection != $b_obj->selection );

                //copy the progenitor to the adjudicate
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

              $entry_data[] = array(
                       'id_1' => $id_1,
                       'id_2' => $id_2,
                       'id_3' => $id_3,
                       'ranked_word_set_id' => $ranked_word_set_id,
                       'ranked_word_set_word' => $ranked_word_set_word,
                       'selection_1' => $selection_1,
                       'selection_2' => $selection_2,
                       'word_id_1' => $word_id_1,
                       'word_1' => $word_1,
                       'classification_1' => $classification_1,
                       'word_id_2' => $word_id_2,
                       'word_2' => $word_2,
                       'classification_2' => $classification_2,
                       'adjudicate' => $adjudicate );

              next( $a );
              next( $b );
              next( $c );
            }
          }
        }
        $adjudicate_data = array( 'entry_data' => $entry_data, 'status_data' => $status_data );
      }
    }

    if( is_null( $adjudicate_data ) )
      throw lib::create( 'exception\runtime',
        'Adjudication is not required for one or more entries for the '.
        $db_test_entry->get_test()->name . ' test pertaining to participant UID '.
        $db_test_entry->get_assignment()->get_participant()->uid, __METHOD__ );

    return $adjudicate_data;
  }
}
