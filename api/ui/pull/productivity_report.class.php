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
    // determine whether or not to round time to 15 minute increments
    $round_times = $this->get_argument( 'round_times', true );

    $role_class_name = lib::get_class_name( 'database\role' );
    $site_class_name = lib::get_class_name( 'database\site' );
    $user_class_name = lib::get_class_name( 'database\user' );
    $activity_class_name = lib::get_class_name( 'database\activity' );
    $assignment_class_name = lib::get_class_name( 'database\assignment' );
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );

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

    // we define the min and max datetime objects here, they get set in the next foreach loop, then
    // used in the for loop below
    $min_datetime_obj = NULL;
    $max_datetime_obj = NULL;

    // now create a table for every site included in the report

    foreach( $site_class_name::select( $site_mod ) as $db_site )
    {
      $contents = array();
      // start by determining the table contents
      $grand_total_time = 0;
      $grand_total_complete = 0;
      $grand_total_adjudicate = 0;
      $grand_total_defer = 0;

      $user_mod = lib::create( 'database\modifier' );
      $user_mod->where( 'access.site_id', '=', $db_site->id );
      $user_mod->where( 'access.role_id', '=', $db_role->id );

      foreach( $user_class_name::select( $user_mod ) as $db_user )
      {
        // make sure the typist has min/max time for this date range
        $activity_mod = lib::create( 'database\modifier' );
        $activity_mod->where( 'activity.user_id', '=', $db_user->id );
        $activity_mod->where( 'activity.site_id', '=', $db_site->id );
        $activity_mod->where( 'activity.role_id', '=', $db_role->id );
        $activity_mod->where( 'operation.subject', '!=', 'self' );

        $assignment_mod = lib::create( 'database\modifier' );
        $assignment_mod->where( 'user_id', '=', $db_user->id );
        // select non-adjudicate submissions
        $assignment_mod->where( 'assignment_id', '!=', '' );
        // select completed assignments
        $assignment_mod->where( 'end_datetime', '!=', '' );
        
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

        $min_activity_datetime_obj = $activity_class_name::get_min_datetime( $activity_mod );
        $max_activity_datetime_obj = $activity_class_name::get_max_datetime( $activity_mod );
        
        // if there is no activity then skip this user
        if( is_null( $min_activity_datetime_obj ) || 
            is_null( $max_activity_datetime_obj ) ) continue;
        
        // Determine the number of completed assignments and their average length.
        ///////////////////////////////////////////////////////////////////////////////////////////
        
        $num_complete = 0;
        $num_adjudicate = 0;
        $num_defer = 0;
        $assignment_time = 0;
        foreach( $db_user->get_assignment_list( $assignment_mod ) as $db_assignment )
        {
          $test_entry_mod = lib::create( 'database\modifier' );
          $test_entry_mod->where( 'assignment_id', '=', $db_assignment->id );
          $test_entry_mod->where( 'test_entry_note.user_id', '=', $db_user->id );
          if( 0 < $test_entry_class_name::count( $test_entry_mod ) )
            $num_defer++; 

          // count the adjudicate submissions
          $db_participant = $db_assignment->get_participant();
          $test_entry_mod = lib::create( 'database\modifier' );
          $test_entry_mod->where( 'assignment_id', '=', '' );
          $test_entry_mod->where( 'participant_id', '=', $db_participant->id ); 
          foreach( $test_entry_class_name::select( $test_entry_mod ) as $db_adjudicate )
          {
            // this is the adjudicated test entry submitted by an administrator
            // get the test entries from the test transcribed by the current user
            // and compare them

            $test_entry_mod = lib::create( 'database\modifier' );
            $test_entry_mod->where( 'assignment_id', '=', $db_assignment->id );
            $test_entry_mod->where( 'test_id', '=', $db_adjudicate->test_id );
            $db_test_entry = $test_entry_class_name::select( $test_entry_mod );
            if( !empty( $db_test_entry ) )
            {
              // get the name of the test and the method used to compare entries
              $entry_name = 'test_entry_' .  $db_adjudicate->get_test()->get_test_type()->name;
              $get_list_method = 'get_' . $entry_name . '_list';
              $entry_list = $is->$get_list_method();
              $entry_class_name = lib::get_class_name( 'database\\' . $entry_name );
              $adjudicate = $entry_class_name::adjudicate_compare( 
                $db_adjudicate->$get_list_method() , $db_test_entry[0]->$get_list_method() );
              // if they match, then this user sourced the entries meaning the companion
              // user was in error
              if( 0 < $adjudicate )
                $num_adjudicate++;
            }              
          } // end loop on test entries

          $num_complete++;

        } // end loop on assignments

        // Determine the total working time.
        // This is done by finding the minimum and maximum activity time for every day included in
        // the report and calculating the difference between the two times.
        ///////////////////////////////////////////////////////////////////////////////////////////
        $time = 0;
        $total_time = 0;
        $min_activity_datetime_obj->setTime( 0, 0 );
        $max_activity_datetime_obj->setTime( 0, 0 );
        $interval = new \DateInterval( 'P1D' );
        for( $datetime_obj = clone $min_activity_datetime_obj;
             $datetime_obj <= $max_activity_datetime_obj;
             $datetime_obj->add( $interval ) )
        {
          // if reporting a single date restrict the count to that day only
          if( $single_date && $single_datetime_obj != $datetime_obj ) continue;

          // get the elapsed time and round to 15 minute increments (if necessary)
          $time += $activity_class_name::get_elapsed_time(
            $db_user, $db_site, $db_role, $datetime_obj->format( 'Y-m-d' ) );
          $total_time = $round_times ? floor( 4 * $time ) / 4 : $time;
        }

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
            $min_activity_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
          $day_activity_mod->where( 'datetime', '<=',
            $min_activity_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
          
          $min_datetime_obj = $activity_class_name::get_min_datetime( $day_activity_mod );
          $max_datetime_obj = $activity_class_name::get_max_datetime( $day_activity_mod );

          $contents[] = array(
            $db_user->name,
            $num_defer,
            $num_adjudicate,
            $num_complete,
            is_null( $min_datetime_obj ) ? '??' : $min_datetime_obj->format( "H:i" ),
            is_null( $max_datetime_obj ) ? '??' : $max_datetime_obj->format( "H:i" ),
            sprintf( '%0.2f', $total_time ),
            $total_time > 0 ? sprintf( '%0.2f', $num_complete / $total_time ) : '',
            $num_complete > 0 ? sprintf( '%0.2f', $assignment_time / $num_complete / 60 ) : '' );
        }
        else
        {
          $contents[] = array(
            $db_user->name,
            $num_defer,
            $num_adjudicate,
            $num_complete,
            sprintf( '%0.2f', $total_time ),
            $total_time > 0 ? sprintf( '%0.2f', $num_complete / $total_time ) : '',
            $num_complete > 0 ? sprintf( '%0.2f', $assignment_time / $num_complete / 60 ) : '' );
        }

        $grand_total_defer += $num_defer;
        $grand_total_adjudicate += $num_adjudicate;
        $grand_total_complete += $num_complete;
        $grand_total_time += $total_time;
      }

      $average_complete_PH = $grand_total_time > 0 ? 
        sprintf( '%0.2f', $grand_total_complete / $grand_total_time ) : 'N/A';

      if( $single_date )
      {
        $header = array(
          "Typist",
          "Deferrals",
          "Adjudications",
          "Completes",
          "Start Time",
          "End Time",
          "Total Time",
          "CompPH",
          "Avg. Length" );

        $footer = array(
          "Total",
          "sum()",
          "sum()",
          "sum()",
          "--",
          "--",
          "sum()",
          $average_complete_PH,
          "average()" );
      }
      else
      {
        $header = array(
          "Typist",
          "Deferrals",
          "Adjudications",
          "Completes",
          "Total Time",
          "CompPH",
          "Avg. Length" );

        $footer = array(
          "Total",
          "sum()",
          "sum()",
          "sum()",
          "sum()",
          $average_complete_PH,
          "average()" );
      }

      $title = 0 == $restrict_site_id ? $db_site->name : NULL;
      $this->add_table( $title, $header, $contents, $footer );
    }
  }
}
