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
    $role_class_name = lib::get_class_name( 'database\role' );
    $site_class_name = lib::get_class_name( 'database\site' );
    $user_class_name = lib::get_class_name( 'database\user' );
    $activity_class_name = lib::get_class_name( 'database\activity' );
    $assignment_class_name = lib::get_class_name( 'database\assignment' );
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );
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
    if( $single_date ) $single_datetime_obj = clone $start_datetime_obj;

    // create a table for every site included in the report
    foreach( $site_class_name::select( $site_mod ) as $db_site )
    {
      $contents = array();
      // start by determining the table contents
      $grand_total_time = 0;
      $grand_total_complete = 0;
      $grand_total_incomplete = 0;
      $grand_total_adjudicate = 0;
      $grand_total_defer = 0;

      $user_mod = lib::create( 'database\modifier' );
      $user_mod->where( 'access.site_id', '=', $db_site->id );
      $user_mod->where( 'access.role_id', '=', $db_role->id );
      foreach( $user_class_name::select( $user_mod ) as $db_user )
      {
        // ensure the typist has min/max time for this date range
        $activity_mod = lib::create( 'database\modifier' );
        $activity_mod->where( 'activity.user_id', '=', $db_user->id );
        $activity_mod->where( 'activity.site_id', '=', $db_site->id );
        $activity_mod->where( 'activity.role_id', '=', $db_role->id );
        $activity_mod->where( 'operation.subject', '!=', 'self' );

        $assignment_mod = lib::create( 'database\modifier' );
        $assignment_mod->where( 'user_id', '=', $db_user->id );

        if( $restrict_start_date && $restrict_end_date )
        {
          $activity_mod->where( 'datetime', '>=',
            $start_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
          $activity_mod->where( 'datetime', '<=',
            $end_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
          $assignment_mod->where( 'start_datetime', '>=',
            $start_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
          $assignment_mod->where( 'end_datetime', '<=',
            $end_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
        }
        else if( $restrict_start_date && !$restrict_end_date ) 
        {
          $activity_mod->where( 'datetime', '>=',
            $start_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
          $assignment_mod->where( 'start_datetime', '>=',
            $start_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
        }
        else if( !$restrict_start_date && $restrict_end_date )
        {
          $activity_mod->where( 'datetime', '<=',
            $end_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
          $assignment_mod->where( 'start_datetime', '<=',
            $end_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
        }

        // if there is no activity then skip this user
        if( 0 == $activity_class_name::count( $activity_mod ) ) continue;

        // Determine the total time spent as a typist over the desired period
        $total_time = $user_time_class_name::get_sum(
          $db_user, $db_site, $db_role, $start_datetime_obj, $end_datetime_obj, $round_times );

        // if there was no time spent then ignore this user
        if( 0 == $total_time ) continue;
        
        // Determine the number of completed assignments and their average length.
        //////////////////////////////////////////////////////////////////////////
        $num_complete = 0;
        $num_incomplete = 0;
        $num_adjudicate = 0;
        $num_defer = 0;
        $assignment_time = 0;
        foreach( $db_user->get_assignment_list( $assignment_mod ) as $db_assignment )
        {
          // are all of the assignment's tests complete?
          if( $db_assignment->all_tests_complete() )
          {
            $num_complete++;

            // each test_entry deferral must have a note created by the user
            $test_entry_mod = lib::create( 'database\modifier' );
            $test_entry_mod->where( 'assignment_id', '=', $db_assignment->id );
            $test_entry_mod->where( 'test_entry_note.user_id', '=', $db_user->id );
            if( 0 < $test_entry_class_name::count( $test_entry_mod ) ) $num_defer++;

            // count the adjudicate submissions
            $test_entry_mod = lib::create( 'database\modifier' );
            $test_entry_mod->where( 'assignment_id', '=', NULL );
            $test_entry_mod->where( 'participant_id', '=', $db_assignment->participant_id );
            foreach( $test_entry_class_name::select( $test_entry_mod ) as $db_adjudicate_test_entry )
            {
              // this is the adjudicated test entry submitted by an administrator
              // get the test entries from the test transcribed by the current user
              // and compare them

              $db_test_entry = $test_entry_class_name::get_unique_record(
                array( 'assignment_id', 'test_id' ),
                array( $db_assignment->id, $db_adjudicate_test_entry->test_id ) );
              if( !is_null( $db_test_entry ) )
              {
                // if they match, then this user sourced the entries meaning the companion
                // user was in error
                if( !$db_test_entry->compare( $db_adjudicate_test_entry ) ) $num_adjudicate++;
              }              
            } // end loop on test entries
          }
          else
          {
            $num_incomplete++;
          }  

        } // end loop on assignments

        // if there were no completed assignments then ignore this user
        if( 0 == ( $num_complete + $num_incomplete ) ) continue;
        
        // Now we can use all the information gathered above to fill in the contents of the table.
        ///////////////////////////////////////////////////////////////////////////////////////////
        if( $single_date )
        {
          $day_activity_mod = lib::create( 'database\modifier' );
          $day_activity_mod->where( 'activity.user_id', '=', $db_user->id );
          $day_activity_mod->where( 'activity.site_id', '=', $db_site->id );
          $day_activity_mod->where( 'activity.role_id', '=', $db_role->id );
          $day_activity_mod->where( 'operation.subject', '!=', 'self' );
          $day_activity_mod->where( 'datetime', '>=',
            $start_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
          $day_activity_mod->where( 'datetime', '<=',
            $start_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
          
          $min_datetime_obj = $activity_class_name::get_min_datetime( $day_activity_mod );
          $max_datetime_obj = $activity_class_name::get_max_datetime( $day_activity_mod );

          $row = array(
            $db_user->name,
            $num_defer,
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
          $row = array(
            $db_user->name,
            $num_defer,
            $num_adjudicate,
            $num_complete,
            $num_incomplete,
            sprintf( '%0.2f', $total_time ),
            0 < $total_time ?
              sprintf( '%0.2f', $num_complete / $total_time ) : '',
            0 < $total_time ?
              sprintf( '%0.2f', ( $num_complete + $num_incomplete ) / $total_time ) : '' );
        }
 
        $contents[] = $row;

        $grand_total_defer += $num_defer;
        $grand_total_adjudicate += $num_adjudicate;
        $grand_total_complete += $num_complete;
        $grand_total_incomplete += $num_incomplete;
        $grand_total_time += $total_time;
      }

      $average_complete_PH = 0 < $grand_total_time ? sprintf( '%0.2f', 
        $grand_total_complete / $grand_total_time ) : 'N/A';
      $average_assignment_PH = 0 < $grand_total_time ? sprintf( '%0.2f',
        ( $grand_total_complete +  $grand_total_incomplete ) / $grand_total_time ) : 'N/A';

      if( $single_date )
      {
        $header = array(
          "Typist",
          "Defer",
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
          "Defer",
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
          "sum()",
          $average_complete_PH,
          $average_assignment_PH );
      }

      $title = 0 == $restrict_site_id ? $db_site->name : NULL;
      $this->add_table( $title, $header, $contents, $footer );
    }
  }
}
