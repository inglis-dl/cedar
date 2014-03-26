<?php
/**
 * assignment.class.php
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
    $assignment_class_name = lib::get_class_name( 'database\assignment' );
    $event_type_class_name = lib::get_class_name( 'database\event_type' );
    $participant_class_name = lib::get_class_name( 'database\participant' ); 
    $role_class_name = lib::get_class_name( 'database\role' );
    $site_class_name = lib::get_class_name( 'database\site' );   
    $user_class_name = lib::get_class_name( 'database\user' );

    $restrict_start_date = $this->get_argument( 'restrict_start_date' );
    $restrict_end_date = $this->get_argument( 'restrict_end_date' );
    $now_datetime_obj = util::get_datetime_object();
    $start_datetime_obj = NULL;
    $end_datetime_obj = NULL;

    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    $site_mod = lib::create( 'database\modifier' );
    if( $restrict_site_id )
      $site_mod->where( 'id', '=', $restrict_site_id );

    // get the total number of possible participants in each cohort 
    // with completed baseline interviews
    $base_cati_mod = lib::create( 'database\modifier' );
    $base_cati_mod->where( 'event.event_type_id', '=',
      $event_type_class_name::get_unique_record( 'name', 'completed (Baseline)' )->id );

    $base_comp_mod = lib::create( 'database\modifier' );
    $base_comp_mod->where( 'event.event_type_id', '=',
       $event_type_class_name::get_unique_record( 'name', 'completed (Baseline Site)' )->id );

    $cohort_mod_list['cati'] = $base_cati_mod;
    $cohort_mod_list['comp'] = $base_comp_mod;  

    $total_available['cati'] = $participant_class_name::count( $base_cati_mod );
    $total_available['comp'] = $participant_class_name::count( $base_comp_mod );

    $header = array( 'Year', 'Month', 
       'CATI Closed', 'CATI Open', 'COMP Closed', 'COMP Open' );
    $footer = array( '--', '--', 'sum()', 'sum()', 'sum()', 'sum()' );

    $total_complete['cati'] = 0;
    $total_complete['comp'] = 0;
    $total_open['cati'] = 0;
    $total_open['comp'] = 0;

    // validate the dates
    if( $restrict_start_date )
    {
      $start_datetime_obj = util::get_datetime_object( $restrict_start_date );
      if( $start_datetime_obj > $now_datetime_obj ) $start_datetime_obj = clone $now_datetime_obj;
    }
    
    if( $restrict_end_date )
    {
      $end_datetime_obj = util::get_datetime_object( $restrict_end_date );
      if( $end_datetime_obj > $now_datetime_obj ) $end_datetime_obj = clone $now_datetime_obj;
    }
    else
    {
      $end_datetime_obj = $now_datetime_obj;
    }

    if( $restrict_start_date && $restrict_end_date && $end_datetime_obj < $start_datetime_obj )
    {
      $temp_datetime_obj = clone $start_datetime_obj;
      $start_datetime_obj = clone $end_datetime_obj;
      $end_datetime_obj = clone $temp_datetime_obj;
    }

    // if there is no start date then start with the earliest created assignment
    if( is_null( $start_datetime_obj ) )
    {
      $assignment_mod = lib::create( 'database\modifier' );
      $assignment_mod->order( 'start_datetime' );
      $assignment_mod->limit( 1 );        
      $assignment_list = $assignment_class_name::select( $assignment_mod );
      $db_assignment = current( $assignment_list );
      $start_datetime_obj = util::get_datetime_object( $db_assignment->start_datetime );
    }

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
    
    $db_role = $role_class_name::get_unique_record( 'name', 'typist' );

    $site_list = $site_class_name::select( $site_mod );
    $do_summary_table = count( $site_list ) > 1;
    $summary_content = array();

    // now create a table for every site included in the report
    foreach( $site_list as $db_site )
    {
      $title = $db_site->name . ' Assignments';

      // get all the typists from this site
      $id_list = array();
      $user_mod = lib::create( 'database\modifier' );
      $user_mod->where( 'access.role_id', '=', $db_role->id );
      $user_mod->where( 'access.site_id', '=', $db_site->id );
      foreach( $user_class_name::select( $user_mod ) as $db_user )
      {
        $id_list[] = $db_user->id;
      }

      // skip if no users at this site
      if( empty( $id_list ) )
      {
        $this->add_table( $title, $header, array( '--','--', 0, 0, 0, 0 ), $footer );        
        continue;
      }

      $content = array();
      $interval = new \DateInterval( 'P1M' );
      for( $from_datetime_obj = clone $start_datetime_obj;
           $from_datetime_obj < $end_datetime_obj;
           $from_datetime_obj->add( $interval ) )
      {
        $to_datetime_obj = clone $from_datetime_obj;
        $to_datetime_obj->add( $interval );

        $row =
          array( $from_datetime_obj->format( 'Y' ), $from_datetime_obj->format( 'F' ) );

        foreach( $cohort_mod_list as $cohort_key => $cohort_mod )
        {
          $complete_mod = clone $cohort_mod;
          $complete_mod->where( 'assignment.user_id', 'IN', $id_list );
          $complete_mod->where( 'assignment.end_datetime', '>=', 
            $from_datetime_obj->format( 'Y-m-d' ) );
          $complete_mod->where( 'assignment.end_datetime', '<', 
            $to_datetime_obj->format( 'Y-m-d' ) );
          $complete_list = array();
          foreach( $participant_class_name::select( $complete_mod ) as $db_participant )
          {
            if( array_key_exists( $db_participant->id, $complete_list ) )
            {
              $complete_list[$db_participant->id]++;
            }  
            else
            {
              $complete_list[$db_participant->id] = 1;
            }
          }

          $complete_values = array_count_values( array_values( $complete_list ) );

          // number completed by two typists
          $num_complete = array_key_exists( '2', $complete_values ) ? $complete_values['2'] : 0;
          // number completed by one typist
          $num_partial = array_key_exists( '1', $complete_values ) ? $complete_values['1'] : 0;

          $id_exclude = array_keys( $complete_list, 1 );

          $in_progress_mod = clone $cohort_mod;
          if( count( $id_exclude ) )
            $in_progress_mod->where( 'participant.id', 'NOT IN', $id_exclude );
          $in_progress_mod->where( 'assignment.user_id', 'IN', $id_list );
          $in_progress_mod->where( 'assignment.start_datetime', '<', 
            $to_datetime_obj->format( 'Y-m-d' ) );
          $in_progress_mod->where( 'assignment.end_datetime', '=', NULL );

          // number started but not finished
          $num_started = $participant_class_name::count( $in_progress_mod );

          $row[] = $num_complete;
          $row[] = $num_partial + $num_started;
          $total_complete[ $cohort_key ] += $num_complete;
          $total_open[ $cohort_key ] = $num_partial + $num_started;
        }            

        $content[] = $row;

        if( $do_summary_table )
        {
          $key = implode( array_slice( $row, 0, 2 ) );         
          if( !array_key_exists( $key, $summary_content ) )
          {
            $summary_content[ $key ] = $row;
          }  
          else
          {
            for( $i = 2; $i < 6; $i++ )
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

    $this->add_table( 'Status (All Sites)', 
      array( 'Cohort', 'Closed', 'Remaining', 'Open' ),
      array( 
        array( 'CATI', 
                $total_complete['cati'], 
                $total_available['cati'] - $total_complete['cati'], 
                $total_open['cati'] ),
        array( 'COMP', 
                $total_complete['comp'], 
                $total_available['comp'] - $total_complete['comp'], 
                $total_open['comp'] ) ),
      array( '--', 'sum()', 'sum()', 'sum()' ) );          
  }
}
