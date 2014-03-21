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
    $site_class_name = lib::create( 'database\site' );
    $event_class_name = lib::create( 'database\event' );

    $restrict_start_date = $this->get_argument( 'restrict_start_date' );
    $restrict_end_date = $this->get_argument( 'restrict_end_date' );
    $now_datetime_obj = util::get_datetime_object();
    $start_datetime_obj = NULL;
    $end_datetime_obj = NULL;

    // the total number of possible participants in each cohort 
    // with completed baseline interviews

    $participant_class_name = lib::get_class_name( 'database\participant' ); 
    $assignment_class_name = lib::get_class_name( 'database\assignment' );
    $event_type_class_name = lib::get_class_name( 'database\event_type' );

    $comp_mod = lib::create( 'database\modifier' );
    $comp_mod->where( 'event.event_type_id', '=',
       $event_type_class_name::get_unique_record( 'name', 'completed (Baseline Site)' )->id );

    $cati_mod = lib::create( 'database\modifier' );
    $cati_mod->where( 'event.event_type_id', '=',
      $event_type_class_name::get_unique_record( 'name', 'completed (Baseline)' )->id );

    $base_cati_mod = clone $cati_mod;
    $base_comp_mod = clone $comp_mod;

    $assign_cati_mod = clone $cati_mod;
    $assign_comp_mod = clone $comp_mod;

    $assign_cati_mod_in_progress = clone $cati_mod;
    $assign_comp_mod_in_progress = clone $comp_mod;

    $total_cati_available = $participant_class_name::count( $cati_mod );
    $total_comp_available = $participant_class_name::count( $comp_mod );

    // get number of completed assignments
    $assign_cati_mod->where( 'assignment.end_datetime', '!=', '' );
    $assign_comp_mod->where( 'assignment.end_datetime', '!=', '' );

    $total_cati_complete = $participant_class_name::count( $assign_cati_mod );
    $total_comp_complete = $participant_class_name::count( $assign_comp_mod );
   
    // get number of in-progress assignments
    $assign_cati_mod_in_progress->where( 'assignment.end_datetime', '=', '' );
    $assign_comp_mod_in_progress->where( 'assignment.end_datetime', '=', '' );

    $total_cati_complete = $participant_class_name::count( $assign_cati_mod_in_progress );
    $total_comp_complete = $participant_class_name::count( $assign_comp_mod_in_progress );


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

    // if there is no start date then start with the earliest started assignment
    if( is_null( $start_datetime_obj ) )
    {
      $assign_mod = lib::create( 'database\modifier' );
      $assign_mod->order( 'start_datetime' );
      $assign_mod->limit( 1 );        
      $assign_list = $assignment_class_name->select( $assign_mod );
      $db_assign = current( $assign_list );
      $start_datetime_obj = util::get_datetime_object( $db_assign->start_datetime );
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
    
    $cati_contents = array();
    $comp_contents = array();
    $interval = new \DateInterval( 'P1M' );
    for( $from_datetime_obj = clone $start_datetime_obj;
         $from_datetime_obj < $end_datetime_obj;
         $from_datetime_obj->add( $interval ) )
    {
      $to_datetime_obj = clone $from_datetime_obj;
      $to_datetime_obj->add( $interval );

      $cati_content =
        array( $from_datetime_obj->format( 'Y' ), $from_datetime_obj->format( 'F' ) );
      $site_content =
        array( $from_datetime_obj->format( 'Y' ), $from_datetime_obj->format( 'F' ) );

      $cati_event_mod = clone $base_cati_mod;
      $cati_event_mod->where( 'assignment.end_datetime', '>=', $from_datetime_obj->format( 'Y-m-d' ) );
      $cati_event_mod->where( 'assignment.end_datetime', '<', $to_datetime_obj->format( 'Y-m-d' ) );
      $cati_content[] = $participant_class_name::count( $cati_event_mod );

      $comp_event_mod = clone $base_comp_mod;
      $comp_event_mod->where( 'assignment.end_datetime', '>=', $from_datetime_obj->format( 'Y-m-d' ) );
      $comp_event_mod->where( 'assignment.end_datetime', '<', $to_datetime_obj->format( 'Y-m-d' ) );
      $comp_content[] = $participant_class_name::count( $comp_event_mod );

      $cati_contents[] = $cati_content;
      $comp_contents[] = $site_content;
    }

    $header = array( 'Year', 'Month' );
    $footer = array( 'sum()', 'sum()' );

    $this->add_table( 'Completed CATI Interviews', $header, $cati_contents, $footer );
    $this->add_table( 'Completed COMP Interviews', $header, $comp_contents, $footer );
  }
}
