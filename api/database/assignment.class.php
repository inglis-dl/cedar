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
   * @return string (NULL if none available)
   * @access public
   */
  public static function get_next_available_participant( $db_user )
  {
    $database_class_name = lib::get_class_name( 'database\database' );
    $participant_class_name = lib::get_class_name( 'database\participant' );

    $has_tracking = false;
    $has_comprehensive = false;
    foreach( $db_user->get_cohort_list() as $db_cohort )
    {
      $has_tracking |= 'tracking' == $db_cohort->name;
      $has_comprehensive |= 'comprehensive' == $db_cohort->name;
    }

    if( $has_tracking == false && $has_comprehensive == false )
      throw lib::create( 'exception\notice',
        'There must be one or more cohorts assigned to user: '. $db_user->name,
          __METHOD__ );

    $language_id_list = array();
    foreach( $db_user->get_language_list() as $db_language )
      $language_id_list[] = $db_language->id;

    $id = NULL;

    if( $has_tracking )
    {
      $session = lib::create( 'business\session' );

      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'participant.active', '=', true );
      $modifier->where( 'user_assignment.id', '=', NULL );
      $modifier->where( 'cohort.name', '=', 'tracking' );
      $modifier->where( 'event_type.name', '=', 'completed (Baseline)' );
      $modifier->where( 'participant_site.site_id', '=', $session->get_site()->id );
      if( 0 < count( $language_id_list ) )
      {
        $db_service_language = $session->get_service()->get_language();
        $modifier->where( 'IFNULL( participant.language_id, '.
          $db_service_language->id . ' )', 'IN', $language_id_list );
      }
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

    return is_null( $id ) ? $id : lib::create( 'database\participant', $id );
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

    return 0 == static::db()->get_one( $sql );
  }

  /**
   * Returns the id of a user having no language restrictions that the
   * assignment can be reassigned to.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return array
   * @access public
   */
  public function get_reassign_user()
  {
    $user_class_name = lib::get_class_name( 'database\user' );
    $role_class_name = lib::get_class_name( 'database\role' );
    $db_role = $role_class_name::get_unique_record( 'name', 'typist' );

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'user_has_language.user_id', '!=', NULL );

    // all the users who have a language restriction
    $exclude_ids = array();
    foreach( $user_class_name::select( $modifier ) as $db_user )
    {
      $exclude_ids[] =  $db_user->id;
    }
    $exclude_ids[] = $this->user_id;

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'user_has_cohort.cohort_id', '=', $this->get_participant()->get_cohort()->id );
    $modifier->where( 'access.role_id', '=', $db_role->id );
    $modifier->where( 'user.id', 'NOT IN', $exclude_ids );

    $id_list = array();
    $min = PHP_INT_MAX;
    foreach( $user_class_name::select( $modifier ) as $db_user )
    {
      // how many open assignments does this user have
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'user_id', '=', $db_user->id );
      $modifier->where( 'end_datetime', '=', NULL );
      $count = static::count( $modifier );
      if( $count < $min )
      {
        $min = $count;
        array_unshift( $id_list, $db_user->id );
      }
    }

    return $id_list;
  }
}
