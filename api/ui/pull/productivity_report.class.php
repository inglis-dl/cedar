<?php
/**
 * productivity_report.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\pull;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * pull: productivity report
 *
 * Generate a report file containing typist productivity info
 */
class productivity_report extends \cenozo\ui\pull\base_report
{
  /**
   * Constructor
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param string $subject The subject to retrieve the primary information from.
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'productivity', $args );
  }

  /**
   * Builds the report.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $activity_class_name = lib::get_class_name( 'database\activity' );
    $assignment_class_name = lib::get_class_name( 'database\assignment' );
    $db = lib::create( 'business\session' )->get_database();
    $role_class_name = lib::get_class_name( 'database\role' );
    $site_class_name = lib::get_class_name( 'database\site' );
    $test_class_name = lib::get_class_name( 'database\test' );
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );
    $user_class_name = lib::get_class_name( 'database\user' );
    $user_time_class_name = lib::get_class_name( 'database\user_time' );

    // determine whether or not to round time to 15 minute increments
    $round_times = $this->get_argument( 'round_times', true );

    $db_role = $role_class_name::get_unique_record( 'name', 'typist' );
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    $site_mod = lib::create( 'database\modifier' );
    if( $restrict_site_id )
      $site_mod->where( 'id', '=', $restrict_site_id );

    $restrict_start_date = $this->get_argument( 'restrict_start_date' );
    $restrict_end_date = $this->get_argument( 'restrict_end_date' );
    $now_datetime_obj = util::get_datetime_object();
    $start_datetime_obj = NULL;
    $end_datetime_obj = NULL;

    if( $restrict_start_date )
    {
      $start_datetime_obj = util::get_datetime_object( $restrict_start_date );
      if( $start_datetime_obj > $now_datetime_obj )
        $start_datetime_obj = clone $now_datetime_obj;
    }
    if( $restrict_end_date )
    {
      $end_datetime_obj = util::get_datetime_object( $restrict_end_date );
      if( $end_datetime_obj > $now_datetime_obj )
        $end_datetime_obj = clone $now_datetime_obj;
    }
    if( $restrict_start_date && $restrict_end_date && $end_datetime_obj < $start_datetime_obj )
    {
      $temp_datetime_obj = clone $start_datetime_obj;
      $start_datetime_obj = clone $end_datetime_obj;
      $end_datetime_obj = clone $temp_datetime_obj;
    }

    // determine whether we are running the report for a single date or not
    $single_date = ( !is_null( $start_datetime_obj ) &&
                     !is_null( $end_datetime_obj ) &&
                     $start_datetime_obj == $end_datetime_obj ) ||
                   ( !is_null( $start_datetime_obj ) &&
                     $start_datetime_obj == $now_datetime_obj );

    $base_activity_mod = lib::create( 'database\modifier' );
    $base_activity_mod->where( 'activity.role_id', '=', $db_role->id );
    $base_activity_mod->where( 'operation.subject', '!=', 'self' );

    $base_assignment_mod = lib::create( 'database\modifier' );

    if( $restrict_start_date && $restrict_end_date )
    {
      $base_activity_mod->where( 'datetime', '>=',
        $start_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
      $base_activity_mod->where( 'datetime', '<=',
        $end_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
      $base_assignment_mod->where( 'start_datetime', '>=',
        $start_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
    }
    else if( $restrict_start_date && !$restrict_end_date )
    {
      $base_activity_mod->where( 'datetime', '>=',
        $start_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
      $base_assignment_mod->where( 'start_datetime', '>=',
        $start_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
    }
    else if( !$restrict_start_date && $restrict_end_date )
    {
      $base_activity_mod->where( 'datetime', '<=',
        $end_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
      $base_assignment_mod->where( 'start_datetime', '<=',
        $end_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
    }

    $base_day_activity_mod = lib::create( 'database\modifier' );
    if( $single_date )
    {
      $base_day_activity_mod->where( 'activity.role_id', '=', $db_role->id );
      $base_day_activity_mod->where( 'operation.subject', '!=', 'self' );
      $base_day_activity_mod->where( 'datetime', '>=',
        $start_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
      $base_day_activity_mod->where( 'datetime', '<=',
        $start_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
    }

    $sql_queries = array();
    $column_remove = array( 'id', 'test_entry_id', 'update_timestamp', 'create_timestamp' );
    foreach( $test_class_name::select() as $db_test )
    {
      $table_name = 'test_entry_' . $db_test->get_test_type()->name;
      $table_class_name = lib::get_class_name( 'database\\' . $table_name );
      $column_names = array_diff(
        $table_class_name::db()->get_column_names( $table_class_name::get_table_name() ),
        $column_remove );

      $sql_pre = 'SELECT COUNT(*) FROM ( SELECT ';
      $sql_columns1 = 'SELECT ';
      $sql_columns2 = 'SELECT ';
      $sql_post = ') temp GROUP BY ';
      $last = end( $column_names );
      foreach( $column_names as $column )
      {
        if( $last == $column )
        {
          $sql_columns1 = $sql_columns1 . 't1.'. $column;
          $sql_columns2 = $sql_columns2 . 't2.'. $column;
          $sql_post = $sql_post . $column;
        }
        else
        {
          $sql_columns1 = $sql_columns1 . 't1.'. $column . ', ';
          $sql_columns2 = $sql_columns2 . 't2.'. $column . ', ';
          $sql_post = $sql_post . $column . ', ';
        }
        $sql_pre = $sql_pre . $column . ', ';
      }

      $sql_pre = $sql_pre . ' COUNT(*) AS c FROM (';
      $sql_post = $sql_post . ' HAVING c=1 ) temp2';

      $sql_columns1 = $sql_columns1 . ' FROM '. $table_name . ' t1 ' .
        'WHERE t1.test_entry_id=%s';
      $sql_columns2 = $sql_columns2 . ' FROM '. $table_name . ' t2 ' .
        'WHERE t2.test_entry_id=%s';

      $sql_queries[ $db_test->id ] =
        $sql_pre . $sql_columns1 . ' UNION ALL ' . $sql_columns2 . $sql_post;
    }

    $temp_user_mod = clone $base_assignment_mod;
    $temp_user_mod->where( 'assignment.end_datetime', '!=', NULL );

    $sql = sprintf(
      'CREATE TEMPORARY TABLE temp_user_adjudicate AS '.
      'SELECT assignment.user_id, '.
      't1.id AS adjudicate_entry_id, '.
      't2.id AS progenitor_entry_id, '.
      't1.test_id AS test_id, '.
      't1.audio_status <=> t2.audio_status '.
      'AND t1.participant_status <=> t2.participant_status AS status_equal '.
      'FROM assignment '.
      'JOIN test_entry t1 ON t1.participant_id=assignment.participant_id '.
      'LEFT JOIN test_entry t2 ON t2.test_id=t1.test_id %s '.
      'AND t2.assignment_id=assignment.id', $temp_user_mod->get_sql() );

    $assignment_class_name::db()->execute( $sql );
    $sql =
      'ALTER TABLE temp_user_adjudicate '.
      'ADD INDEX dk_user_id (user_id), ADD INDEX dk_test_id (test_id)';
    $assignment_class_name::db()->execute( $sql );

    $sql = sprintf(
      'CREATE TEMPORARY TABLE temp_user_complete AS '.
      'SELECT user_id, '.
      'COUNT(*) AS assignment_count, '.
      'SUM( complete_status ) AS complete_count '.
      'FROM ( '.
        'SELECT assignment.id, '.
        'assignment.user_id AS user_id, '.
        'IF( ( '.
          'COUNT( test_entry.id ) - '.
          'SUM( IF( test_entry.completed = "submitted", '.
            'IF( test_entry.deferred <=> "requested", 0, '.
              'IF( test_entry.deferred <=> "pending", 0, 1 ) ), 0 ) )'.
          ') = 0, 1, 0 ) '.
      'AS complete_status '.
      'FROM assignment '.
      'LEFT JOIN test_entry ON assignment.id=test_entry.assignment_id %s'.
      'GROUP BY assignment.id '.
      ') AS tmp '.
      'GROUP BY user_id ', $base_assignment_mod->get_sql() );

    $assignment_class_name::db()->execute( $sql );
    $sql = 'ALTER TABLE temp_user_complete ADD INDEX dk_user_id (user_id)';
    $assignment_class_name::db()->execute( $sql );

    // create a table for every site included in the report
    foreach( $site_class_name::select( $site_mod ) as $db_site )
    {
      $contents = array();
      // start by determining the table contents
      $grand_total_time = 0;
      $grand_total_complete = 0;
      $grand_total_incomplete = 0;
      $grand_total_adjudicate = 0;

      $user_mod = lib::create( 'database\modifier' );
      $user_mod->where( 'access.site_id', '=', $db_site->id );
      $user_mod->where( 'access.role_id', '=', $db_role->id );
      foreach( $user_class_name::select( $user_mod ) as $db_user )
      {
        // ensure the typist has min/max time for this date range
        $activity_mod = clone $base_activity_mod;
        $activity_mod->where( 'activity.user_id', '=', $db_user->id );
        $activity_mod->where( 'activity.site_id', '=', $db_site->id );

        // if there is no activity then skip this user
        if( 0 == $activity_class_name::count( $activity_mod ) ) continue;

        // Determine the total time spent as a typist over the desired period
        $total_time = $user_time_class_name::get_sum(
          $db_user, $db_site, $db_role, $start_datetime_obj, $end_datetime_obj, $round_times );

        // if there was no time spent then ignore this user
        if( 0 == $total_time ) continue;

        // Determine the number of completed assignments and their average length.
        //////////////////////////////////////////////////////////////////////////
        $num_complete    = 0;
        $num_incomplete  = 0;
        $num_adjudicate  = 0;
        $assignment_time = 0;

        $id_string = $db->format_string( $db_user->id );

        $sql = sprintf(
          'SELECT * FROM temp_user_complete '.
          'WHERE user_id=%s', $id_string );

        $data = $assignment_class_name::db()->get_row( $sql );

        if( array_key_exists( 'complete_count', $data ) )
          $num_complete = $data[ 'complete_count' ];

        if( array_key_exists( 'assignment_count', $data ) )
          $num_incomplete = $data[ 'assignment_count' ] - $num_complete;

        $sql = sprintf(
          'SELECT * FROM temp_user_adjudicate '.
          'WHERE user_id=%s', $id_string );

        $num_adjudicate = 0;

        foreach( $assignment_class_name::db()->get_all( $sql ) as $data )
        {
          if( 0 == $data[ 'status_equal' ] )
          {
            $num_adjudicate++;
          }
          else
          {
            $sql = sprintf(
              $sql_queries[ $data[ 'test_id' ] ],
              $db->format_string( $data['progenitor_entry_id'] ),
              $db->format_string( $data['adjudicate_entry_id'] ) );

            if( 0 < $assignment_class_name::db()->get_one( $sql ) ) $num_adjudicate++;
          }
        }

        // if there were no assignments then ignore this user
        if( 0 == ( $num_complete + $num_incomplete + $num_adjudicate ) ) continue;

        // Now we can use all the information gathered above to fill in the contents of the table.
        ///////////////////////////////////////////////////////////////////////////////////////////
        if( $single_date )
        {
          $day_activity_mod = clone $base_day_activity_mod;
          $day_activity_mod->where( 'activity.user_id', '=', $db_user->id );
          $day_activity_mod->where( 'activity.site_id', '=', $db_site->id );

          $min_datetime_obj = $activity_class_name::get_min_datetime( $day_activity_mod );
          $max_datetime_obj = $activity_class_name::get_max_datetime( $day_activity_mod );

          $contents[] = array(
            $db_user->name,
            $num_adjudicate,
            $num_complete,
            $num_incomplete,
            is_null( $min_datetime_obj ) ? '??' : $min_datetime_obj->format( "H:i" ),
            is_null( $max_datetime_obj ) ? '??' : $max_datetime_obj->format( "H:i" ),
            sprintf( '%0.2f', $total_time ),
            0 < $total_time ?
              sprintf( '%0.2f', $num_complete / $total_time ) : '',
            0 < $total_time ?
              sprintf( '%0.2f', ( $num_complete + $num_incomplete ) / $total_time ) : '' );
        }
        else
        {
          $contents[] = array(
            $db_user->name,
            $num_adjudicate,
            $num_complete,
            $num_incomplete,
            sprintf( '%0.2f', $total_time ),
            0 < $total_time ?
              sprintf( '%0.2f', $num_complete / $total_time ) : '',
            0 < $total_time ?
              sprintf( '%0.2f', ( $num_complete + $num_incomplete ) / $total_time ) : '' );
        }

        $grand_total_adjudicate += $num_adjudicate;
        $grand_total_complete   += $num_complete;
        $grand_total_incomplete += $num_incomplete;
        $grand_total_time       += $total_time;
      }

      $average_complete_PH = 0 < $grand_total_time ? sprintf( '%0.2f',
        $grand_total_complete / $grand_total_time ) : 'N/A';
      $average_assignment_PH = 0 < $grand_total_time ? sprintf( '%0.2f',
        ( $grand_total_complete +  $grand_total_incomplete ) / $grand_total_time ) : 'N/A';

      if( $single_date )
      {
        $header = array(
          "Typist",
          "Adjudicate",
          "Complete",
          "Incomplete",
          "Start Time",
          "End Time",
          "Total Time",
          "Complete PH",
          "Assignment PH" );

        $footer = array(
          "Total",
          "sum()",
          "sum()",
          "sum()",
          "--",
          "--",
          "sum()",
          $average_complete_PH,
          $average_assignment_PH );
      }
      else
      {
        $header = array(
          "Typist",
          "Adjudicate",
          "Complete",
          "Incomplete",
          "Total Time",
          "Complete PH",
          "Assignment PH" );

        $footer = array(
          "Total",
          "sum()",
          "sum()",
          "sum()",
          "sum()",
          $average_complete_PH,
          $average_assignment_PH );
      }

      $title = 0 == $restrict_site_id ? $db_site->name : NULL;
      $this->add_table( $title, $header, $contents, $footer );
    }
  }
}
