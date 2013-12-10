<?php
/**
 * assignment_add.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget assignment add
 */
class assignment_add extends \cenozo\ui\widget\base_view
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'assignment', 'add', $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();
    
    // add items to the view
    $this->add_item( 'user_id', 'hidden' );
    $this->add_item( 'participant_id', 'hidden' );
    //TODO  these should be constant but base_add.twig will not process constant
    $this->add_item( 'uid', 'string', 'UID' );
    $this->add_item( 'language', 'string', 'Language' );
    $this->add_item( 'cohort', 'string', 'Cohort' );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();
    
    $session = lib::create( 'business\session' );
    $db_role = $session->get_role();
    $db_user = $session->get_user();

    // get the cohorts that this user is assigned to
    $cohort_ids = array();
    $has_tracking = false;
    $has_comprehensive = false;
    foreach( $db_user->get_cohort_list() as $db_cohort )
    {
      $cohort_ids[]= $db_cohort->id;
      if( 'tracking' == $db_cohort->name )
        $has_tracking = true;
      if( 'comprehensive' == $db_cohort->name )
        $has_comprehensive = true;
    }

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'cohort_id', 'IN', $cohort_ids );

    // filter on participants who have the same language as the user
    if( $db_user->language != 'any' )
    {
      $modifier->where( 'language', '=', $db_user->language );
      //TODO the participant language can be NULL, meaning
      // english
    }

    $event_type_class_name = lib::get_class_name( 'database\event_type' );

    $db_tracking_event_type =
      $event_type_class_name::get_unique_record( 'name', 'completed (Baseline Home)' );

    $db_comprehensive_event_type =
      $event_type_class_name::get_unique_record( 'name', 'completed (Baseline)' );

    if( $has_tracking && $has_comprehensive )
    {
      $modifier->where_bracket( true );

      // tracking
      $modifier->where_bracket( true );
      $modifier->where( 'event.event_type_id', '=', $db_tracking_event_type->id );
      $modifier->where_bracket( false );

      // comprehensive
      $modifier->where_bracket( true, true );
      $modifier->where( 'event.event_type_id', '=', $db_comprehensive_event_type->id );
      $modifier->where_bracket( false );
      
      $modifier->where_bracket( false );
    }
    else if( $has_tracking )
    {
      $modifier->where( 'event.event_type_id', '=', $db_tracking_event_type->id );
    }
    else if( $has_comprehensive )
    {
      $modifier->where( 'event.event_type_id', '=', $db_comprehensive_event_type->id );
    }

    $participant_class_name = lib::get_class_name( 'database\participant' );
    $assignment_class_name = lib::get_class_name( 'database\assignment' );
    $found = false;
    $uid = '';
    $language = '';
    $participant_id = '';
    $cohort = '';
    $modifier->limit( 200 );
    foreach( $participant_class_name::select( $modifier ) as $db_participant )
    {   
      $assignment_mod = lib::create( 'database\modifier' );
      $assignment_mod->where( 'participant_id', '=', $db_participant->id );
      $assignment_mod->where( 'user_id', '=', $db_user->id );

      if( $assignment_class_name::count( $assignment_mod ) == 0 )
      {
        $uid = $db_participant->uid;
        $language = $db_participant->language;
        $participant_id = $db_participant->id;
        $cohort = $db_participant->get_cohort()->name;
        //log::debug( array( $db_participant, $db_user ) );
        $found = true;        
        break;
      }  
    }   
      
    // throw a notice if no participant  was found
    if( !$found ) throw lib::create( 'exception\notice',
      sprintf( 'There are currently no %ss available for processing.',
               str_replace( '_', ' ', $this->get_subject() ) ),
      __METHOD__ );

    //TODO use a semaphore when generating a new assignment

   
    //log::debug( array( $db_user->id, $participant_id,  $uid, $language, $cohort ) );

    $this->set_item( 'user_id', $db_user->id, true );
    $this->set_item( 'participant_id', $participant_id, true );
    $this->set_item( 'uid', $uid, true );
    $this->set_item( 'language', $language, true );
    $this->set_item( 'cohort', $cohort, true );
  }
}
