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
    if( is_null( $this->id ) )
      throw lib::create( 'exception\runtime',
        'Tried to get deferral status for an assignment with no id', __METHOD__ );

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'assignment_id', '=', $this->id);
    $modifier->where( 'deferred', '=', true);
    //$modifier->limit( 1 );
    $sql = sprintf( 'SELECT count(*) FROM test_entry %s', $modifier->get_sql() );
    return 0 !== intval( static::db()->get_one( $sql ) );
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
    if( is_null( $this->id ) )
      throw lib::create( 'exception\runtime',
        'Tried to get completed count for an assignment with no id', __METHOD__ );

    $database_class_name = lib::get_class_name( 'database\database' );
    return static::db()->get_one(
      sprintf( 'SELECT completed FROM test_entry_total_completed WHERE assignment_id=%s',
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
    if( is_null( $this->id ) )
      throw lib::create( 'exception\runtime',
        'Tried to get adjudicate count for an assignment with no id', __METHOD__ );

    $database_class_name = lib::get_class_name( 'database\database' );
    return static::db()->get_one(
      sprintf( 'SELECT adjudicate FROM test_entry_total_adjudicate WHERE assignment_id=%s',
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
    if( is_null( $this->id ) )
      throw lib::create( 'exception\runtime',
        'Tried to get adjudication status for an assignment with no id', __METHOD__ );

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
    if( is_null( $this->id ) )
      throw lib::create( 'exception\runtime',
        'Tried to get counts for an assignment with no id', __METHOD__ );

    $database_class_name = lib::get_class_name( 'database\database' );
    return static::db()->get_row( sprintf(
      'SELECT deferred, adjudicate, completed FROM assignment_total WHERE assignment_id=%s',
      $database_class_name::format_string( $this->id ) ) );
  }

  /**
   * Get the next available participant id to create an assignment for.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param  record db_user A user requesting a participant for a new assignment
   * @throws exception\notice
   * @return string (NULL if none available)
   * @access public
   */
  public static function get_next_available_participant()
  {
    $database_class_name = lib::get_class_name( 'database\database' );
    $participant_class_name = lib::get_class_name( 'database\participant' );
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

    if( false == $has_tracking && false == $has_comprehensive )
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
      '%s ) x ON x.id=uhl.language_id '.
      'WHERE uhl.user_id = %s',
      $modifier->get_sql(),
      $database_class_name::format_string( $db_user->id ) );

    $user_languages = static::db()->get_all( $sql );
    array_walk( $user_languages, function( &$item ){ $item=$item['id']; } );

    if( 0 == count( $user_languages ) )
      $user_languages[] = $db_service->language_id;

    $id = NULL;
    if( $has_tracking )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'participant.active', '=', true );
      $modifier->where( 'user_assignment.id', '=', NULL );
      $modifier->where( 'cohort.name', '=', 'tracking' );
      $modifier->where( 'event_type.name', '=', 'completed (Baseline)' );
      $modifier->where( 'participant_site.site_id', '=', $db_site->id );
      $modifier->where( 'IFNULL( participant.language_id, ' .
        $database_class_name::format_string( current( $user_languages ) ) . ' )',
        'IN', $user_languages );
      $modifier->group( 'participant.id' );

      $sql = sprintf(
        'SELECT participant.id FROM participant '.
        'JOIN participant_site ON participant_site.participant_id = participant.id '.
        'JOIN cohort ON cohort.id = participant.cohort_id '.
        'JOIN event ON event.participant_id = participant.id '.
        'JOIN event_type ON event_type.id = event.event_type_id '.
        'JOIN '.
        '('.
          'SELECT participant_id FROM sabretooth_recording '.
          'GROUP BY participant_id '.
        ') AS temp ON participant.id = temp.participant_id '.
        'LEFT JOIN assignment ON assignment.participant_id = participant.id '.
        'LEFT JOIN assignment AS user_assignment '.
        'ON user_assignment.participant_id = participant.id '.
        'AND user_assignment.user_id = %s %s '.
        'HAVING COUNT(*) < 2 ',
        $database_class_name::format_string( $db_user->id ),
        $modifier->get_sql() );

      $id = static::db()->get_one( $sql );
    }

    // stub until comprehensive recordings are worked out
    if( is_null( $id ) && $has_comprehensive )
    {
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
    // find a sibling assignment based on participant id and user id uniqueness
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'participant_id', '=', $this->participant_id );
    $modifier->where( 'user_id', '!=', $this->user_id );
    $modifier->limit( 1 );
    $db_assignment = current( static::select( $modifier ) );
    return false === $db_assignment ? NULL : $db_assignment;
  }

  /**
   * Returns whether all tests constituting this assignment are complete.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return boolean
   * @access public
   */
  public function all_tests_complete()
  {
    $database_class_name = lib::get_class_name( 'database\database' );
    $id_string = $database_class_name::format_string( $this->id );
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
          'JOIN assignment ON assignment.id = test_entry.assignment_id '.
          'WHERE assignment.id = %s '.
          'AND deferred = false '.
          'AND completed = true '.
        ') '.
      ')', $id_string, $id_string );

    return 0 === intval( static::db()->get_one( $sql ) );
  }

  /**
   * Are there any incomplete test_entry records for this assignment?
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return boolean
   * @access public
   */
  public function has_incompletes()
  {
    return 0 !== $this->all_tests_complete();
  }

  /**
   * Returns the id of a user as an array key having language restrictions that the
   * assignment can be reassigned to with.  The boolean value returned with the key
   * indicates whether to keep the assignment intact or to reinitialize it.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @return associative array  user_id => boolean
   * @access public
   */
  public function get_reassign_user()
  {
    $user_class_name = lib::get_class_name( 'database\user' );
    $role_class_name = lib::get_class_name( 'database\role' );
    $region_site_name = lib::get_class_name( 'database\region_site' );
    $db_role = $role_class_name::get_unique_record( 'name', 'typist' );

    $session = lib::create( 'business\session' );
    $db_service = $session->get_service();
    $db_site = $session->get_site();

    // get all the languages cedar has access to
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'service_id', '=', $db_service->id );
    $modifier->group( 'language_id' );

    $cedar_languages = array();
    foreach( $region_site_name::select( $modifier ) as $db_region_site )
      $cedar_languages[] = $db_region_site->language_id;

    $num_language = count( $cedar_languages );

    // get all typists at this site that can process the current record's participant cohort
    // that have the required (multiple) language restrictions
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'access.role_id', '=', $db_role->id );
    $modifier->where( 'access.site_id', '=', $db_site->id );
    $modifier->where( 'user_has_cohort.cohort_id', '=', $this->get_participant()->get_cohort()->id );
    $modifier->where( 'user_has_language.language_id', 'IN', $cedar_languages );
    $modifier->where( 'user.active', '=', true );
    $modifier->group( 'user.id' );
    $modifier->having( 'COUNT( user.id )', '=', $num_language );

    $db_user_list = $user_class_name::select( $modifier );

    $id_list = array();
    foreach( $db_user_list as $db_user )
      $id_list[] = $db_user->id;

    if( count( $id_list ) < 2 ) return $id_list;

    // if the user assigned to this assignment has the aligned language restrictions
    // then add them to the front of the returned id_list so as not to
    // delete their transcriptions during reassign
    $prepend = in_array( $this->user_id, $id_list );

    // prepare a list of user id's sorted according to the users'
    // number of open assignments from least to most with the
    // objective to reassign the assignments over to those typists
    // with fewer active assignments
    $id_list = array();
    $min = PHP_INT_MAX;
    foreach( $db_user_list as $db_user )
    {
      // how many open assignments does this user have
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'user_id', '=', $db_user->id );
      $modifier->where( 'end_datetime', '=', NULL );
      $count = static::count( $modifier );
      if( $count < $min  )
      {
        $min = $count;
        array_unshift( $id_list, $db_user->id );
      }
      else
      {
        $id_list[] = $db_user->id;
      }
    }

    // check if the sibling assignment's user has a language restriction
    $assignmnet_pool = array();
    $assignment_pool[] = $this->id;

    $db_sibling_assignment = $this->get_sibling_assignment();
    if( is_null( $db_sibling_assignment ) )
      throw lib::create( 'exception\notice',
        'A sibling assignment is required',  __METHOD__ );

    $assignment_pool[] = $db_sibling_assignment->id;
    $id = $db_sibling_assignment->user_id;
    if( in_array( $id, $id_list ) )
    {
      unset( $id_list[ array_search( $id, $id_list ) ] );
      $id_list = array_values( $id_list );
      array_unshift( $id_list, $id );
    }

    if( $prepend )
    {
      if( in_array( $this->user_id, $id_list )  )
      {
        unset( $id_list[ array_search( $this->user_id, $id_list ) ] );
        $id_list = array_values( $id_list );
      }
      array_unshift( $id_list, $this->user_id );
    }

    // truncate to 2 entries
    if( count( $id_list ) > 2 )
      $id_list = array_slice( $id_list, 0, 2 );

    $id_list = array_combine( $id_list, array_fill( 0, 2, true ) );

    if( array_key_exists( $this->user_id, $id_list ) )
    {
      $id_list[ $this->user_id ] = array( false, $this->id );
      unset( $assignment_pool[ array_search( $this->id, $assignment_pool ) ] );
    }

    if( array_key_exists( $db_sibling_assignment->user_id, $id_list ) )
    {
      $id_list[ $db_sibling_assignment->user_id ] = array( false, $db_sibling_assignment->id );
      unset( $assignment_pool[ array_search( $db_sibling_assignment->id, $assignment_pool ) ] );
    }

    if( 0 < count( $assignment_pool ) )
    {
      //find the id_list key that doesnt have an array value
      reset( $assignment_pool );
      foreach( $id_list as $user_id => &$value )
      {
        if( !is_array( $value ) )
        {
          $value = array( $value, current( $assignment_pool ) );
          next( $assignment_pool );
        }
      }
    }

    return $id_list;
  }

  /**
   * Purge the assignment in preparation for deletion.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return boolean
   * @access public
   */
  public function purge()
  {
    
  }
}
