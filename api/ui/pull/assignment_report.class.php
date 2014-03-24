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
    $event_class_name = lib::create( 'database\event' );

    $restrict_start_date = $this->get_argument( 'restrict_start_date' );
    $restrict_end_date = $this->get_argument( 'restrict_end_date' );
    $now_datetime_obj = util::get_datetime_object();
    $start_datetime_obj = NULL;
    $end_datetime_obj = NULL;

    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    $site_mod = lib::create( 'database\modifier' );
    if( $restrict_site_id )
      $site_mod->where( 'id', '=', $restrict_site_id );

    // the total number of possible participants in each cohort 
    // with completed baseline interviews

    $participant_class_name = lib::get_class_name( 'database\participant' ); 
    $assignment_class_name = lib::get_class_name( 'database\assignment' );
    $event_type_class_name = lib::get_class_name( 'database\event_type' );

    $base_comp_mod = lib::create( 'database\modifier' );
    $base_comp_mod->where( 'event.event_type_id', '=',
       $event_type_class_name::get_unique_record( 'name', 'completed (Baseline Site)' )->id );

    $base_cati_mod = lib::create( 'database\modifier' );
    $base_cati_mod->where( 'event.event_type_id', '=',
      $event_type_class_name::get_unique_record( 'name', 'completed (Baseline)' )->id );

    $total_cati_available = $participant_class_name::count( $base_cati_mod );
    $total_comp_available = $participant_class_name::count( $base_comp_mod );

    $header = array( 'Year', 'Month', 'CATI Done', 'CATIT Open', 'COMP Done', 'COMP Open' );
    $footer = array( '--', '--', 'sum()', 'sum()', 'sum()', 'sum()' );

    $grand_total_complete = 0;
    $grand_total_in_progress = 0;

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
    
    $role_class_name = lib::get_class_name( 'database\role' );
    $db_role = $role_class_name::get_unique_record( 'name', 'typist' );
    $site_class_name = lib::get_class_name( 'database\site' );   
    $user_class_name = lib::get_class_name( 'database\user' );   
    $cohort_class_name = lib::get_class_name( 'database\cohort' );
    $cohort_id_list['cati'] = $cohort_class_name::get_unique_record( 'name', 'tracking' )->id;
    $cohort_id_list['comp'] = $cohort_class_name::get_unique_record( 'name', 'comprehensive' )->id;

    // now create a table for every site included in the report
    foreach( $site_class_name::select( $site_mod ) as $db_site )
    {
      // get all the typists from this site
      $id_list = array();
      $user_mod = lib::create( 'database\modifier' );
      $user_mod->where( 'access.role_id', '=', $db_role->id );
      $user_mod->where( 'access.site_id', '=', $db_site->id );
      $db_user_list = $user_class_name::select( $user_mod );
      foreach( $user_class_name::select( $user_mod ) as $db_user )
      {
        $id_list[] = $db_user->id;
      }

      // skip if no users at this site
      if( empty( $id_list ) ) continue;

      $assignment_mod = lib::create( 'database\modifier' );
      $assignment_mod->where( 'user_id', 'IN', $id_list );

      $contents = array();
      $interval = new \DateInterval( 'P1M' );
      for( $from_datetime_obj = clone $start_datetime_obj;
           $from_datetime_obj < $end_datetime_obj;
           $from_datetime_obj->add( $interval ) )
      {
        $to_datetime_obj = clone $from_datetime_obj;
        $to_datetime_obj->add( $interval );

        $row =
          array( $from_datetime_obj->format( 'Y' ), $from_datetime_obj->format( 'F' ) );

        $cati_complete_mod = clone $assignment_mod;
        //$cati_complete_mod->where( 'participant.cohort_id', '=', $cohort_id_list['cati'] ); 
        $cati_complete_mod->where( 'end_datetime', '>=', 
          $from_datetime_obj->format( 'Y-m-d' ) );
        $cati_complete_mod->where( 'end_datetime', '<', 
          $to_datetime_obj->format( 'Y-m-d' ) );
        $sql = sprintf( '%s %s %s',
          'SELECT COUNT(*) FROM ( SELECT participant_id FROM assignment',
          $cati_complete_mod->get_sql(),
          'GROUP BY participant_id HAVING COUNT(*)=2 ) AS tmp' );
        $row[] = intval( $assignment_class_name::db()->get_one( $sql ) );

        $cati_in_progress_mod = clone $assignment_mod;
        //$cati_in_progress->where( 'participant.cohort_id', '=', $cohort_id_list['cati'] ); 
        $cati_in_progress_mod->where( 'start_datetime', '<', 
          $to_datetime_obj->format( 'Y-m-d' ) );
        $cati_in_progress_mod->where( 'end_datetime', '=', NULL );
        $row[] = $assignment_class_name::count( $cati_in_progress_mod );

        $comp_complete_mod = clone $assignment_mod;
        //$comp_complete_mod->where( 'participant.cohort_id', '=', $cohort_id_list['comp'] ); 
        $comp_complete_mod->where( 'end_datetime', '>=', 
          $from_datetime_obj->format( 'Y-m-d' ) );
        $comp_complete_mod->where( 'end_datetime', '<', 
          $to_datetime_obj->format( 'Y-m-d' ) );
        $sql = sprintf( '%s %s %s',
          'SELECT COUNT(*) FROM ( SELECT participant_id FROM assignment',
          $comp_complete_mod->get_sql(),
          'GROUP BY participant_id HAVING COUNT(*)=2 ) AS tmp' );
        $row[] = intval( $assignment_class_name::db()->get_one( $sql ) );

        $comp_in_progress_mod = clone $assignment_mod;
        //$comp_in_progress_mod->where( 'participant.cohort_id', '=', $cohort_id_list['comp'] ); 
        $comp_in_progress_mod->where( 'start_datetime', '<', 
          $to_datetime_obj->format( 'Y-m-d' ) );
        $comp_in_progress_mod->where( 'end_datetime', '=', NULL );
        $row[] = $assignment_class_name::count( $cati_in_progress_mod );

        $contents[] = $row;
      }

      $title = $db_site->name . ' Assignments';
      $this->add_table( $title, $header, $contents, $cati_footer );      
    }
  }
}
