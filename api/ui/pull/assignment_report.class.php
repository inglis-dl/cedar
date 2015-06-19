<?php
/**
 * assignment_report.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\pull;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * Consent form report data.
 *
 * @abstract
 */
class assignment_report extends \cenozo\ui\pull\base_report
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
    parent::__construct( 'assignment', $args );
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
    $cohort_class_name = lib::get_class_name( 'database\cohort' );
    $event_type_class_name = lib::get_class_name( 'database\event_type' );
    $operation_class_name = lib::get_class_name( 'database\operation' );
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $role_class_name = lib::get_class_name( 'database\role' );
    $site_class_name = lib::get_class_name( 'database\site' );
    $user_class_name = lib::get_class_name( 'database\user' );
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );

    // get the total number of possible participants in each cohort
    // with completed baseline interviews
    $base_cati_mod = lib::create( 'database\modifier' );
    $base_cati_mod->where( 'event.event_type_id', '=',
      $event_type_class_name::get_unique_record( 'name', 'completed (Baseline)' )->id );

    $base_comp_mod = lib::create( 'database\modifier' );
    $base_comp_mod->where( 'event.event_type_id', '=',
       $event_type_class_name::get_unique_record( 'name', 'completed (Baseline Site)' )->id );

    $cohort_list = array();
    $total_participant_complete = array();
    $header = array( 'Year', 'Month' );
    $footer = array( '--', '--' );
    foreach( $cohort_class_name::select() as $db_cohort )
    {
      $cohort_list[$db_cohort->name] = $db_cohort->id;
      $total_participant_complete[$db_cohort->name] = 0;
      $header[] = ucwords( $db_cohort->name ) . ' Closed (Participants)';
      $footer[] = 'sum()';
      $header[] = ucwords( $db_cohort->name ) . ' Open (Participants)';
      $footer[] = '--';
      $header[] = ucwords( $db_cohort->name ) . ' Closed (Assignments)';
      $footer[] = 'sum()';
      $header[] = ucwords( $db_cohort->name ) . ' Open (Assignments)';
      $footer[] = '--';
      $header[] = ucwords( $db_cohort->name ) . ' Started (Assignments)';
      $footer[] = '--';
    }

    $total_available['tracking']      = $participant_class_name::count( $base_cati_mod );
    $total_available['comprehensive'] = $participant_class_name::count( $base_comp_mod );

    $mod = lib::create( 'database\modifier' );
    $mod->where( 'end_datetime', '=', NULL );
    $mod->where( 'participant.cohort_id', '=', $cohort_list['comprehensive'] );

    $total_open = $assignment_class_name::count($mod);
    log::debug( 'number of open comprehensive UIDs: ' . $total_open );
    $done = array();
    $assignment_manager = lib::create( 'business\assignment_manager' );
    $visited = array();
    $total_visited = 0;
    $total_corrected = 0;
    $last_progress = -1;
    $num_adj = 0;
    $total_adj_del = 0;
    $verbose = true;
    $precheck = true;
    foreach( $assignment_class_name::select( $mod ) as $db_assignment )
    {
      $progress = intval(100.0*$total_visited++ / floatval($total_open));
      if( $last_progress != $progress && $verbose )
      {
        log::debug( 'progress: ' . $progress .
                    '( assignments corrected: ' . count($done) .
                    ', tests corrected: ' . $total_corrected .
                    ', adjudications checked: '. $num_adj . ')');
        $last_progress = $progress;
      }
      if( in_array( $db_assignment->id, $visited ) ) continue;
      $visited[] = $db_assignment->id;
      $db_sibling = $db_assignment->get_sibling_assignment();
      if( is_null( $db_sibling ) ) continue;
      if( in_array( $db_sibling->id, $visited ) ) continue;
      $visited[] = $db_sibling->id;
      $uid = $db_assignment->get_participant()->uid;
      if($verbose)
        log::debug( 'processing UID: ' . $uid );

      $testmod = lib::create( 'database\modifier' );
      $testmod->where( 'assignment_id', '=', $db_assignment->id );
      $testmod->where( 'completed', '=', 'submitted' );
      $testmod->where( 'IFNULL(adjudicate,true)', '=', true );
      $testmod->where( 'IFNULL(deferred,"NULL")', 'NOT IN',
         $test_entry_class_name::$deferred_states );

      $num_adj_pre = $test_entry_class_name::count( $testmod );
      if( 0 == $num_adj_pre ) continue;
      $num_adj+=$num_adj_pre;

      $completed = true;
      $num_adj_del = 0;
      $num_adj_post = 0;
      foreach( $test_entry_class_name::select( $testmod ) as $db_test_entry )
      {
        $db_sibling_test_entry = $db_test_entry->get_sibling_test_entry();
        $adj_update_state = NULL;
        if( !$db_test_entry->compare( $db_sibling_test_entry ) )
        {
          $adj_update_state = true;
          $completed = false;
          if($verbose)
            log::debug( 'test adjudication required for ' . $db_test_entry->get_test()->name );
        }
        else
        {
          // if they are identical check if there is an adjudicate entry and delete it
          $db_adjudicate_test_entry = $db_test_entry->get_adjudicate_test_entry();
          if( !is_null( $db_adjudicate_test_entry ) )
          {
            if( !$precheck) 
              $db_adjudicate_test_entry->delete();
            $num_adj_del++;
            if($verbose)
              log::debug( 'removing adjudication for ' . $db_test_entry->get_test()->name );
          }
        }
        if( !($adj_update_state == $db_test_entry->adjudicate &&
              $adj_update_state == $db_sibling_test_entry->adjudicate) )
        {
          if( $verbose)
            log::debug( 'updating adjudication state from ' .
              (is_null($db_test_entry->adjudicate)?'null' : $db_test_entry->adjudicate) .
              ' to ' . (is_null($adj_update_state)?'null' : $adj_update_state));

          if( !$precheck )    
          {
            $db_test_entry->adjudicate = $adj_update_state;
            $db_test_entry->save();
            $db_sibling_test_entry->adjudicate = $adj_update_state;
            $db_sibling_test_entry->save();
          }  
          $num_adj_post++;
        }
      }
      $total_adj_del += $num_adj_del;
      $total_corrected += $num_adj_post;
      if( $completed )
      {
        if( $verbose)
          log::debug( 'setting ' . $uid . ' assignment end datetimes to close' );
        // both assignments are now complete: set their end datetimes
        if( !$precheck )
        {
          $end_datetime = util::get_datetime_object()->format( "Y-m-d H:i:s" );
          $db_assignment->end_datetime = $end_datetime;
          $db_assignment->save();
          $db_sibling->end_datetime = $end_datetime;
          $db_sibling->save();
        }
        $done[] = $db_assignment->id;
        $done[] = $db_sibling->id;
      }
      else
      {
        if( !(is_null( $db_assignment->end_datetime ) && is_null( $db_sibling->end_datatime ) ) )
        {
          if( $verbose )
            log::debug( 'resetting ' . $uid . ' assignment end datetimes to null' );
          if( !$precheck ) 
          {
            $db_assignment->end_datetime = NULL;
            $db_assignment->save();
            $db_sibling->end_datetime = NULL;
            $db_sibling->save();
          }  
        }
      }
    }
    if( $verbose )
    {
      log::debug( 'number of comp assignments completed: '.
        count($done) . ' of ' . $total_visited );
      log::debug( 'number of deleted adjudications: ' . $total_adj_del );
      log::debug( 'number of corrected test entries: ' . $total_corrected );
    }

    $restrict_start_date = $this->get_argument( 'restrict_start_date' );
    $restrict_end_date = $this->get_argument( 'restrict_end_date' );
    $now_datetime_obj = util::get_datetime_object();
    $start_datetime_obj = NULL;
    $end_datetime_obj = NULL;

    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    $site_mod = lib::create( 'database\modifier' );
    if( $restrict_site_id )
      $site_mod->where( 'id', '=', $restrict_site_id );

    // validate the dates
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

    $db_role = $role_class_name::get_unique_record( 'name', 'typist' );
    $db_operation =  $operation_class_name::get_unique_record(
      array( 'type', 'name', 'subject' ),
      array( 'push', 'new', 'assignment' ) );

    $base_started_mod = lib::create( 'database\modifier' );
    $base_started_mod->where( 'activity.operation_id', '=', $db_operation->id );
    $base_started_mod->where( 'activity.role_id', '=', $db_role->id );

    // if there is no start date then start with the earliest created assignment
    if( is_null( $start_datetime_obj ) )
    {
      $first_assignment_mod = clone $base_started_mod;
      $first_assignment_mod->order( 'activity.datetime' );
      $first_assignment_mod->limit( 1 );
      $db_activity = current( $activity_class_name::select( $first_assignment_mod ) );
      if( false !== $db_activity )
        $start_datetime_obj = util::get_datetime_object( $db_activity->datetime );
    }

    if( is_null( $end_datetime_obj ) )
      $end_datetime_obj = clone $now_datetime_obj;

    // we only care about what months have been selected, set days of month appropriately
    // such that the for loop below will include the start and end date's months
    $start_datetime_obj->setDate(
      $start_datetime_obj->format( 'Y' ),
      $start_datetime_obj->format( 'n' ),
      1 );
    $end_datetime_obj->setDate(
      $end_datetime_obj->format( 'Y' ),
      $end_datetime_obj->format( 'n' ),
      2 );

    $site_list = $site_class_name::select( $site_mod );
    $do_summary_table = 1 < count( $site_list );
    $summary_content = array();
    $interval = new \DateInterval( 'P1M' );

    $sql_closed =
      'SELECT COUNT(*) FROM ( '.
      'SELECT COUNT( participant_id ) AS participant_count FROM assignment '.
      'JOIN access ON access.user_id = assignment.user_id '.
      'JOIN participant ON participant.id = assignment.participant_id %s '.
      ') tmp';

    $sql_open =
      'SELECT COUNT(*) FROM ( '.
      'SELECT assignment.* FROM assignment '.
      'JOIN access ON access.user_id = assignment.user_id '.
      'JOIN participant ON participant.id = assignment.participant_id %s '.
      ') tmp ';

    // now create a table for every site included in the report
    foreach( $site_list as $db_site )
    {
      $title = $db_site->name;

      $user_mod = lib::create( 'database\modifier' );
      $user_mod->where( 'access.role_id', '=', $db_role->id );
      $user_mod->where( 'access.site_id', '=', $db_site->id );

      // skip if no typists at this site
      if( 0 == $user_class_name::count( $user_mod ) )
      {
        $this->add_table(
          $title, $header, array( '--','--', 0, '0', 0, '0', '0', 0, '0', 0, '0', '0' ), $footer );
        continue;
      }

      $content = array();
      for( $from_datetime_obj = clone $start_datetime_obj;
           $from_datetime_obj < $end_datetime_obj;
           $from_datetime_obj->add( $interval ) )
      {
        $to_datetime_obj = clone $from_datetime_obj;
        $to_datetime_obj->add( $interval );

        // set the year and month columns
        $row =
          array( $from_datetime_obj->format( 'Y' ), $from_datetime_obj->format( 'F' ) );

        $complete_mod = lib::create( 'database\modifier' );
        $complete_mod->where( 'access.site_id', '=', $db_site->id );
        $complete_mod->where( 'assignment.end_datetime', '>=', $from_datetime_obj->format( 'Y-m-d' ) );
        $complete_mod->where( 'assignment.end_datetime', '<', $to_datetime_obj->format( 'Y-m-d' ) );
        $complete_mod->group( 'assignment.id' );

        $in_progress_mod = lib::create( 'database\modifier' );
        $in_progress_mod->where( 'access.site_id', '=', $db_site->id );
        $in_progress_mod->where( 'assignment.start_datetime', '<', $to_datetime_obj->format( 'Y-m-d' ) );
        $in_progress_mod->where( 'assignment.end_datetime', '=', NULL );
        $in_progress_mod->group( 'assignment.id' );

        $created_mod = lib::create( 'database\modifier' );
        $created_mod->where( 'access.site_id', '=', $db_site->id );
        $created_mod->where( 'assignment.start_datetime', '>=', $from_datetime_obj->format( 'Y-m-d' ) );
        $created_mod->where( 'assignment.start_datetime', '<', $to_datetime_obj->format( 'Y-m-d' ) );
        $created_mod->group( 'assignment.id' );

        foreach( $cohort_list as $cohort_name => $cohort_id )
        {
          // completed assignments with a sibling
          $complete_2_mod = clone $complete_mod;
          $complete_2_mod->where( 'participant.cohort_id', '=', $cohort_id );
          $complete_2_mod->having( 'participant_count', '=', 2 );

          $sql = sprintf( $sql_closed, $complete_2_mod->get_sql() );

          $num_participant_complete = $assignment_class_name::db()->get_one( $sql );

          $num_assignment_complete = $num_participant_complete * 2;

          // completed assignments without a sibling
          $complete_1_mod = clone $complete_mod;
          $complete_1_mod->where( 'participant.cohort_id', '=', $cohort_id );
          $complete_1_mod->having( 'participant_count', '=', 1 );

          $sql = sprintf( $sql_closed, $complete_1_mod->get_sql() );

          $num_participant_partial = $assignment_class_name::db()->get_one( $sql );

          $num_assignment_complete += $num_participant_partial;

          // assignments in progress
          $complete_0_mod = clone $in_progress_mod;
          $complete_0_mod->where( 'participant.cohort_id', '=', $cohort_id );

          $sql = sprintf( $sql_open, $complete_0_mod->get_sql() );

          $num_assignment_in_progress = $assignment_class_name::db()->get_one( $sql );

          $sql = $sql . 'GROUP BY participant_id';
          $num_participant_started = $assignment_class_name::db()->get_one( $sql );

          // assignments created
          $assignment_created_mod = clone $created_mod;
          $assignment_created_mod->where( 'participant.cohort_id', '=', $cohort_id );

          $sql = sprintf(
            'SELECT COUNT(*) FROM ( '.
            'SELECT COUNT(*) FROM assignment '.
            'JOIN access ON access.user_id = assignment.user_id '.
            'JOIN participant ON participant.id = assignment.participant_id %s '.
            ') tmp', $assignment_created_mod->get_sql() );

          $num_assignment_started = $assignment_class_name::db()->get_one( $sql );

          $row[] = $num_participant_complete;
          $row[] = $num_participant_partial + $num_participant_started;
          $row[] = $num_assignment_complete;
          $row[] = is_null( $num_assignment_in_progress ) ? 0 : $num_assignment_in_progress;
          $row[] = is_null( $num_assignment_started ) ? 0 : $num_assignment_started;

          $total_participant_complete[$cohort_name] += $num_participant_complete;
        }

        $content[] = $row;

        if( $do_summary_table )
        {
          // generate a key YearMonth from first two row elements
          $key = implode( array_slice( $row, 0, 2 ) );
          if( !array_key_exists( $key, $summary_content ) )
          {
            $summary_content[ $key ] = $row;
          }
          else
          {
            for( $i = 2; $i < count( $row ); $i++ )
              $summary_content[ $key ][ $i ] += $row[ $i ];
          }
        }
      }

      $this->add_table( $title, $header, $content, $footer );
    }

    if( $do_summary_table )
    {
      $this->add_table( 'Summary (All Sites)',
        $header, array_values( $summary_content ), $footer );
    }

    $status_heading =  array( 'Cohort', 'Closed (Participants)', 'Remaining (Participants)' );
    $status_content = array();
    $status_footer =  array( '--', 'sum()', 'sum()' );

    foreach( $cohort_list as $cohort_name => $cohort_id )
    {
      $status_content[] =
        array( ucwords( $cohort_name ),
               $total_participant_complete[$cohort_name],
               $total_available[$cohort_name] - $total_participant_complete[$cohort_name] );
    }

    $this->add_table( 'Status (All Sites)',
      $status_heading, $status_content, $status_footer );
  }
}
