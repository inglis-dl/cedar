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
   * Get the number of deferred test_entry records for this assignment.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\runtime
   * @return integer
   * @access public
   */
  public function get_deferred_count()
  {
    if( is_null( $this->id ) )
      throw lib::create( 'exception\runtime',
        'Tried to get deferred count for an assignment with no id', __METHOD__ );

    $database_class_name = lib::get_class_name( 'database\database' );
    return static::db()->get_one(
      sprintf( 'SELECT deferred FROM test_entry_total_deferred WHERE assignment_id=%s',
               $database_class_name::format_string( $this->id ) ) );
  }

  /**
   * Are there any deferred test_entry records for this assignment?
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\runtime
   * @return boolean
   * @access public
   */
  public function has_deferrals()
  {
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'assignment_id', '=', $this->id);
    $modifier->where( 'deferred', 'IN', $test_entry_class_name::$deferred_states );

    $sql = sprintf( 'SELECT COUNT(*) FROM test_entry %s', $modifier->get_sql() );
    return 0 < intval( static::db()->get_one( $sql ) );
  }

  /**
   * Get the number of completed test_entry records for this assignment.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\runtime
   * @return integer
   * @access public
   */
  public function get_completed_count()
  {
    $database_class_name = lib::get_class_name( 'database\database' );
    return static::db()->get_one(
      sprintf( 'SELECT completed FROM test_entry_total_completed WHERE assignment_id = %s',
               $database_class_name::format_string( $this->id ) ) );
  }

  /**
   * Get the number of adjudicate test_entry records for this assignment.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\runtime
   * @return integer
   * @access public
   */
  public function get_adjudicate_count()
  {
    $database_class_name = lib::get_class_name( 'database\database' );
    return static::db()->get_one(
      sprintf( 'SELECT adjudicate FROM test_entry_total_adjudicate WHERE assignment_id = %s',
               $database_class_name::format_string( $this->id ) ) );
  }

  /**
   * Are there any test_entry records requiring adjudication for this assignment?
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\runtime
   * @return boolean
   * @access public
   */
  public function has_adjudicates()
  {
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'assignment_id', '=', $this->id );
    $modifier->where( 'adjudicate', '=', true );
    $sql = sprintf( 'SELECT count(*) FROM test_entry %s', $modifier->get_sql() );
    return 0 !== intval( static::db()->get_one( $sql ) );
  }

  /**
   * Get the deferred, adjudicate and complete counts for this assignment.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\runtime
   * @return integer
   * @access public
   */
  public function get_all_counts()
  {
    $database_class_name = lib::get_class_name( 'database\database' );
    return static::db()->get_row( sprintf(
      'SELECT deferred, adjudicate, completed FROM assignment_total WHERE assignment_id = %s',
      $database_class_name::format_string( $this->id ) ) );
  }

  /**
   * Get the next available participant id to create an assignment for.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @return string (NULL if none available)
   * @access public
   */
  public static function get_next_available_participant()
  {
    $database_class_name = lib::get_class_name( 'database\database' );
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $recording_class_name = lib::get_class_name( 'database\recording' );
    $region_site_class_name = lib::get_class_name( 'database\region_site' );

    $session = lib::create( 'business\session' );
    $db_user = $session->get_user();

    $has_tracking = false;
    $has_comprehensive = false;
    foreach( $db_user->get_cohort_list() as $db_cohort )
    {
      $has_tracking |= 'tracking' == $db_cohort->name;
      $has_comprehensive |= 'comprehensive' == $db_cohort->name;
    }

    if( !$has_tracking && !$has_comprehensive )
      throw lib::create( 'exception\notice',
        'There must be one or more cohorts assigned to user: '. $db_user->name,
          __METHOD__ );

    $db_service = $session->get_service();
    $db_site = $session->get_site();

    // get the languages that are common to both site and user
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'service_id', '=', $db_service->id );
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->group( 'language_id' );

    $sql = sprintf(
      'SELECT language_id AS id '.
      'FROM user_has_language uhl '.
      'INNER JOIN ( '.
        'SELECT l.id FROM language l '.
        'JOIN region_site rs ON rs.language_id=l.id '.
      '%s '. // modifier sql
      ') x ON x.id=uhl.language_id '.
      'WHERE uhl.user_id = %s',
      $modifier->get_sql(),
      $database_class_name::format_string( $db_user->id ) );

    $user_languages = static::db()->get_all( $sql );
    array_walk( $user_languages, function( &$item ){ $item=$item['id']; } );

    if( 0 == count( $user_languages ) )
      $user_languages[] = $db_service->language_id;

    $id = NULL;
    $rows = NULL;
    if( $has_tracking )
    {
      $sql =
        'CREATE TEMPORARY TABLE temp_completed AS '.
        'SELECT DISTINCT participant.id AS participant_id '.
        'FROM participant '.
        'JOIN event ON event.participant_id = participant.id '.
        'JOIN event_type ON event_type.id = event.event_type_id '.
        'JOIN cohort ON cohort.id = participant.cohort_id '.
        'WHERE event_type.name = "completed (Baseline)" '.
        'AND participant.active = true '.
        'AND cohort.name = "tracking"';

      static::db()->execute( $sql );

      $sql = 'ALTER TABLE temp_completed ADD INDEX (participant_id)';

      static::db()->execute( $sql );

      $sql =
        'CREATE TEMPORARY TABLE temp_recording AS '.
        'SELECT DISTINCT participant_id '.
        'FROM sabretooth_recording';

      static::db()->execute( $sql );

      $sql = 'ALTER TABLE temp_recording ADD INDEX (participant_id)';

      static::db()->execute( $sql );

      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'participant_site.service_id', '=', $db_service->id );
      $modifier->where( 'participant_site.site_id', '=', $db_site->id );
      $modifier->where( 'IFNULL( participant.language_id, ' .
        $database_class_name::format_string( $db_service->language_id ) . ' )',
        'IN', $user_languages );
      $modifier->group( 'participant.id ');

      $sql = sprintf(
        'CREATE TEMPORARY TABLE temp_assignable AS '.
        'SELECT participant.id AS participant_id, assignment.id AS assignment_id '.
        'FROM participant '.
        'JOIN participant_site ON participant_site.participant_id = participant.id '.
        'JOIN temp_completed ON temp_completed.participant_id = participant.id '.
        'JOIN temp_recording ON temp_recording.participant_id = participant.id '.
        'LEFT JOIN assignment ON assignment.participant_id = participant.id '.
        '%s '. // where statement here
        'HAVING COUNT(*) < 2 ',
        $modifier->get_sql() );

      static::db()->execute( $sql );

      $sql = 'ALTER TABLE temp_assignable ADD INDEX (participant_id)';

      static::db()->execute( $sql );

      $sql = sprintf(
        'SELECT participant_id, assignment_id FROM temp_assignable '.
        'WHERE participant_id NOT IN ( '.
          'SELECT participant_id FROM assignment '.
          'WHERE user_id = %s '.
        ')', $database_class_name::format_string( $db_user->id ) );

      $rows = static::db()->get_all( $sql );
    }

    if( is_null( $rows ) && $has_comprehensive )
    {
      $sql =
        'CREATE TEMPORARY TABLE temp_completed AS '.
        'SELECT DISTINCT participant.id AS participant_id '.
        'FROM participant '.
        'JOIN event AS event1 ON event1.participant_id = participant.id '.
        'JOIN event_type AS event_type1 ON event_type1.id = event1.event_type_id '.
        'JOIN event AS event2 ON event2.participant_id = participant.id '.
        'JOIN event_type AS event_type2 ON event_type2.id = event2.event_type_id '.
        'JOIN cohort ON cohort.id = participant.cohort_id '.
        'WHERE event_type1.name = "completed (Baseline Home)" '.
        'AND event_type2.name = "completed (Baseline Site)" '.
        'AND participant.active = true '.
        'AND cohort.name = "comprehensive"';

      static::db()->execute( $sql );

      $sql = 'ALTER TABLE temp_completed ADD INDEX (participant_id)';

      static::db()->execute( $sql );

      $sql =
        'CREATE TEMPORARY TABLE temp_recording AS '.
        'SELECT DISTINCT participant_id '.
        'FROM recording '.
        'WHERE visit = 1';

      static::db()->execute( $sql );

      $sql = 'ALTER TABLE temp_recording ADD INDEX (participant_id)';

      static::db()->execute( $sql );

      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'participant_site.service_id', '=', $db_service->id );
      $modifier->where( 'participant_site.site_id', '=', $db_site->id );
      $modifier->where( 'IFNULL( participant.language_id, ' .
        $database_class_name::format_string( $db_service->language_id  ) . ' )',
        'IN', $user_languages );
      $modifier->group( 'participant.id ');

      $sql = sprintf(
        'CREATE TEMPORARY TABLE temp_assignable AS '.
        'SELECT participant.id AS participant_id, assignment.id AS assignment_id '.
        'FROM participant '.
        'JOIN participant_site ON participant_site.participant_id = participant.id '.
        'JOIN temp_completed ON temp_completed.participant_id = participant.id '.
        'JOIN temp_recording ON temp_recording.participant_id = participant.id '.
        'LEFT JOIN assignment ON assignment.participant_id = participant.id '.
        '%s '. // where statement here
        'HAVING COUNT(*) < 2 ',
        $modifier->get_sql() );

      static::db()->execute( $sql );

      $sql = 'ALTER TABLE temp_assignable ADD INDEX (participant_id)';

      static::db()->execute( $sql );

      $sql = sprintf(
        'SELECT participant_id, assignment_id FROM temp_assignable '.
        'WHERE participant_id NOT IN ( '.
          'SELECT participant_id FROM assignment '.
          'WHERE user_id = %s '.
        ')', $database_class_name::format_string( $db_user->id ) );

      $rows = static::db()->get_all( $sql );

      if( 0 == count( $rows ) )
      {
        $rows = NULL;
        log::warning(
          'Tried to get the next available comprehensive cohort participant but there are '.
          'no more recording files available.  Please try again.' );

        $recording_class_name::update_recording_list();
      }
    }

    if( !is_null( $rows ) )
    {
      foreach( $rows as $row )
      {
        $assignment_id = $row['assignment_id'];
        $found = false;
        // null case implies no sibling assignment exists
        if( is_null( $assignment_id ) )
        {
          $found = true;
        }
        else
        {
          // a sibling assignment can only be created if the primary assignment
          // has all tests completed, has no deferrals and the language settings
          // of the current user match those of the primary assignment's user
          $db_assignment = lib::create( 'database\assignment', $assignment_id );
          if( !$db_assignment->has_deferrals() && static::all_tests_complete( $assignment_id ) )
          {
            // get the list of users that have language conditions consistent with
            // those of the constituent tests
            $user_list = $db_assignment->get_reassign_user();
            if( 0 < count( $user_list ) )
              $found = array_key_exists( $db_user->id, $user_list );
          }
        }

        if( $found )
        {
          $id = $row['participant_id'];
          break;
        }
      }
    }

    return is_null( $id ) ? NULL : lib::create( 'database\participant', $id );
  }

  /**
   * Get the sibling of this assignment.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return record (NULL if no sibling)
   * @access public
   */
  public function get_sibling_assignment()
  {
    // find a sibling assignment based on participant, site and user id uniqueness
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'participant_id', '=', $this->participant_id );
    $modifier->where( 'user_id', '!=', $this->user_id );
    $modifier->where( 'site_id', '=', $this->site_id );
    $modifier->limit( 1 );
    $db_assignment = current( static::select( $modifier ) );
    return false === $db_assignment ? NULL : $db_assignment;
  }

  /**
   * Returns whether all tests constituting the assignment of $id are completed.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param  integer id An assignment id
   * @param  boolean submitted Search based on test submitted status
   * @return boolean
   * @access public
   */
  public static function all_tests_complete( $id, $submitted = false )
  {
    $database_class_name = lib::get_class_name( 'database\database' );
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'assignment.id', '=', $id );
    $modifier->where( 'IFNULL( deferred, "NULL" )', 'NOT IN',
      $test_entry_class_name::$deferred_states );
    if( $submitted )
      $modifier->where( 'completed', '=', 'submitted' );
    else
      $modifier->where( 'completed', '!=', 'incomplete' );

    $sql = sprintf(
      'SELECT '.
      '( '.
        '( '.
          'SELECT COUNT(*) FROM test_entry '.
          'JOIN assignment ON assignment.id = test_entry.assignment_id '.
          'WHERE assignment.id = %s '.
        ') - '.
        '( '.
          'SELECT COUNT(*) FROM test_entry '.
          'JOIN assignment ON assignment.id = test_entry.assignment_id %s'.
        ') '.
      ')',
      $database_class_name::format_string( $id ),
      $modifier->get_sql() );

    return 0 === intval( static::db()->get_one( $sql ) );
  }

  /**
   * Returns whether all tests constituting the assignment of $id are submitted.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param  integer id An assignment id
   * @return boolean
   * @access public
   */
  public static function all_tests_submitted( $id )
  {
    return static::all_tests_complete( $id, true );
  }

  /**
   * Returns a list of users that the assignment can be reassigned to.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @return associative array  id => name
   * @access public
   */
  public function get_reassign_user()
  {
    $user_class_name = lib::get_class_name( 'database\user' );
    $role_class_name = lib::get_class_name( 'database\role' );
    $region_site_name = lib::get_class_name( 'database\region_site' );
    $user_class_name = lib::get_class_name( 'database\user' );
    $language_class_name = lib::get_class_name( 'database\language' );

    $db_role = $role_class_name::get_unique_record( 'name', 'typist' );

    $session = lib::create( 'business\session' );
    $db_service = $session->get_service();
    $db_site = $session->get_site();

    // get all the languages employed by tests within the current assignment
    $test_language_mod = lib::create( 'database\modifier' );
    $test_language_mod->where( 'test_entry_has_language.test_entry_id', '=', 'test_entry.id' );
    $test_language_mod->where( 'test_entry_has_language.language_id', '=', 'id' );
    $test_language_mod->where( 'test_entry.assignment_id', '=', $this->id );
    $test_language_mod->group( 'id' );
    $test_languages = array();
    foreach( $language_class_name::select( $test_language_mod ) as $db_language )
      $test_languages[] = $db_language->id;

    $user_mod = lib::create( 'database\modifier' );
    $user_mod->where( 'assignment.participant_id', '=', $this->participant_id );

    // get all typists at this site that can process the current record's participant
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'access.role_id', '=', $db_role->id );
    $modifier->where( 'access.site_id', '=', $db_site->id );
    $modifier->where( 'user_has_cohort.cohort_id', '=', $this->get_participant()->get_cohort()->id );
    foreach( $test_languages as $language_id )
      $modifier->where( 'user_has_language.language_id', '=', $language_id );
    $modifier->where( 'user.active', '=', true );
    $modifier->where( 'user.id', 'NOT IN', $user_class_name::select( $user_mod, false, true, true ) );
    $modifier->order( 'user.name' );

    $user_list = array();
    foreach( $user_class_name::select( $modifier ) as $db_user )
      $user_list[$db_user->id] = $db_user->name;

    return $user_list;
  }

  /**
   * Initialize an assignment.  All existing test_entry records are deleted
   * and new test_entry records are created.
   * Only assigments that have never been adjudicated or finished can be initialized.
   * This method is typically called during creation of a db_assignment.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @access public
   */
  public function initialize()
  {
    $test_class_name = lib::get_class_name( 'database\test' );

    $db_participant = $this->get_participant();

    if( !is_null( $this->end_datetime ) )
      throw lib::create( 'exception\notice',
        'The assignment for participant UID ' . $db_participant->uid .
        'is closed and cannot be initialized', __METHOD__ );

    $modifier = NULL;
    if( 'tracking' == $db_participant->get_cohort()->name )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'name', 'NOT LIKE', 'FAS%' );
    }

    // create test_entry record(s)
    foreach( $test_class_name::select( $modifier ) as $db_test )
    {
      $db_test_entry = lib::create( 'database\test_entry' );
      $db_test_entry->test_id = $db_test->id;
      $db_test_entry->assignment_id = $this->id;
      $db_test_entry->save();
      $db_sibling_test_entry =  $db_test_entry->get_sibling_test_entry();
      $use_default = true;
      if( !is_null( $db_sibling_test_entry ) )
      {
        $idlist = $db_sibling_test_entry->get_language_idlist();
        if( 0 < count( $idlist ) )
        {
          $db_test_entry->add_language( $idlist );
          $use_default = false;
        }
      }
      if( $use_default )
      {
        $db_language = $db_test_entry->get_default_participant_language();
        $db_test_entry->add_language( array( $db_language->id ) );
      }
      // create daughter entry record(s)
      $db_test_entry->initialize( false );
    }
  }
}

$assignment_mod = lib::create( 'database\modifier' );
$assignment_mod->where( 'assignment.participant_id', '=', 'participant.id', false );
$assignment_mod->where( 'participant.cohort_id', '=', 'cohort.id', false );
assignment::customize_join( 'cohort', $assignment_mod );
