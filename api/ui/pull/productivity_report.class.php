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
    $database_class_name = lib::get_class_name( 'database\database' );
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

    $sql_comparators = array();
    foreach( $test_class_name::select() as $db_test )
    {
      $table_name = 'test_entry_' . $db_test->get_test_type()->name;
      $table_class_name = lib::get_class_name( 'database\\' . $table_name );
      $column_names =
        $table_class_name::db()->get_column_names( $table_class_name::get_table_name() );
      unset( $column_names[ array_search( 'id', $column_names ) ] );
      unset( $column_names[ array_search( 'test_entry_id', $column_names ) ] );
      $sql_comparators[ $db_test->id ][ $table_name ] = array_values( $column_names );
    }

    $temp_user_mod = clone $base_assignment_mod;
    $temp_user_mod->where( 'assignment.end_datetime', '!=', NULL );
    $sql = sprintf(
      'CREATE TEMPORARY TABLE temp_user '.
      'SELECT t1.id AS adjudicate_entry_id, '.
      't1.test_id AS test_id, '.
      't1.audio_status AS adjudicate_audio_status, '.
      't1.participant_status AS adjudicate_participant_status, '.
      't2.id AS progenitor_entry_id, '.
      't2.audio_status AS progenitor_audio_status, '.
      't2.participant_status AS progenitor_participant_status, '.
      'assignment.user_id '.
      'FROM assignment '.
      'JOIN test_entry t1 ON t1.participant_id=assignment.participant_id '.
      'LEFT JOIN test_entry t2 ON t2.test_id=t1.test_id %s '.
      'AND t2.assignment_id=assignment.id', $temp_user_mod->get_sql() );

    $assignment_class_name::db()->execute( $sql );
    $sql = 'ALTER TABLE temp_user ADD INDEX dk_user_id (user_id)';
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

        $assignment_mod = clone $base_assignment_mod;
        $assignment_mod->where( 'assignment.user_id', '=', $db_user->id );
        $assignment_mod->group( 'assignment.id' );

        $sql = sprintf(
          'SELECT COUNT(*) FROM ( '.
          'SELECT '.
          'IF( COUNT( test_entry.id ) - '.
          'SUM( IF(  test_entry.deferred = false, '.
          'IF( test_entry.completed = true , 1, 0 ), 0  ) ), "complete", "incomplete") '.
          'AS complete_status '.
          'FROM assignment '.
          'LEFT JOIN test_entry ON assignment.id=test_entry.assignment_id %s '.
          'HAVING complete_status="incomplete" ) AS tmp', $assignment_mod->get_sql() );

        $num_complete = $assignment_class_name::db()->get_one( $sql );

        $sql = str_replace( 'complete_status="incomplete', 'complete_status="complete', $sql );

        $num_incomplete = $assignment_class_name::db()->get_one( $sql );

        $sql = sprintf(
          'SELECT * FROM temp_user '.
          'WHERE user_id=%s',
          $database_class_name::format_string( $db_user->id ) );

        $num_adjudicate = 0;
        foreach( $assignment_class_name::db()->get_all( $sql ) as $data )
        {
          if( ( $data[ 'adjudicate_audio_status' ] != $data[ 'progenitor_audio_status' ] ) ||
              ( $data[ 'adjudicate_participant_status' ] != $data[ 'progenitor_participant_status' ] ) )
          {
            $num_adjudicate++;
          }
          else
          {
            $test_id = $data[ 'test_id' ];
            $table_name = current( array_keys( $sql_comparators[ $test_id ] ) );
            $table_columns = current( array_values( $sql_comparators[ $test_id ] ) );
            $sql_pre = 'SELECT COUNT(*) FROM ( SELECT ';
            $sql_columns1 = 'SELECT ';
            $sql_columns2 = 'SELECT ';
            $sql_post = ') temp GROUP BY ';
            $last = end( $table_columns );
            foreach( $table_columns as $column )
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
              sprintf( 'WHERE t1.test_entry_id=%s',
              $database_class_name::format_string( $data['progenitor_entry_id'] ) );
            $sql_columns2 = $sql_columns2 . ' FROM '. $table_name . ' t2 ' .
              sprintf( 'WHERE t2.test_entry_id=%s',
              $database_class_name::format_string( $data['adjudicate_entry_id'] ) );

            $sql = $sql_pre . $sql_columns1 . ' UNION ALL ' . $sql_columns2 . $sql_post;

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
