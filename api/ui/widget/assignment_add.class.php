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

    $session = lib::create( 'business\session' );
    $db_role = $session->get_role();
    
    // add items to the view
    if( $db_role->name == 'typist' )
      $this->add_item( 'user_id', 'hidden' );
    else
      $this->add_item( 'user_id', 'enum', 'User' );

    $this->add_item( 'participant_id', 'hidden' );
    $this->add_item( 'cohort_name', 'hidden' );
    $this->add_item( 'uid', 'constant', 'UID' );
    $this->add_item( 'language', 'constant', 'Language' );
    $this->add_item( 'cohort', 'constant', 'Cohort' );
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

    // filter on participants with cohorts this user is assigned to
    $cohort_ids = array();
    $has_tracking = false;
    $has_comprehensive = false;
    $cohort_list = array();
    if( $db_role->name == 'typist' )
      $cohort_list = $db_user->get_cohort_list();
    else
    {
      $cohort_class_name = lib::get_class_name( 'database\cohort' );  
      $cohort_list = $cohort_class_name::select();
    }

    foreach( $cohort_list as $db_cohort )
    {
      $cohort_ids[] = $db_cohort->id;
      if( 'tracking' == $db_cohort->name )
        $has_tracking = true;
      if( 'comprehensive' == $db_cohort->name )
        $has_comprehensive = true;
    }

    $base_mod = lib::create( 'database\modifier' );
    $base_mod->where( 'cohort_id', 'IN', $cohort_ids );

    // filter on participants who have the same language as the user
    if( $db_role->name == 'typist' && $db_user->language != 'any' )
    {
      $base_mod->where( 'language', '=', $db_user->language );
    }

    // the participant must have completed their interview
    $event_type_class_name = lib::get_class_name( 'database\event_type' );

    $db_tracking_event_type =
      $event_type_class_name::get_unique_record( 'name', 'completed (Baseline)' );

    $db_comprehensive_event_type =
      $event_type_class_name::get_unique_record( 'name', 'completed (Baseline Site)' );

    if( $has_tracking && $has_comprehensive )
    {
      $base_mod->where_bracket( true );

      // tracking
      $base_mod->where_bracket( true );
      $base_mod->where( 'event.event_type_id', '=', $db_tracking_event_type->id );
      $base_mod->where_bracket( false );

      // comprehensive
      $base_mod->where_bracket( true, true );
      $base_mod->where( 'event.event_type_id', '=', $db_comprehensive_event_type->id );
      $base_mod->where_bracket( false );
      
      $base_mod->where_bracket( false );
    }
    else if( $has_tracking )
    {
      $base_mod->where( 'event.event_type_id', '=', $db_tracking_event_type->id );
    }
    else if( $has_comprehensive )
    {
      $base_mod->where( 'event.event_type_id', '=', $db_comprehensive_event_type->id );
    }

    $participant_class_name = lib::get_class_name( 'database\participant' );
    $assignment_class_name = lib::get_class_name( 'database\assignment' );
    $found = false;
    $uid = '';
    $language = '';
    $participant_id = '';
    $cohort = '';
    $cohort_id = '';
    $limit = 10;
    $offset = 0;
    $participant_count = 0;

    $sabretooth_manager = NULL;
    $args = array();
    $auth = array();
    if( $has_tracking )
    {
      $sabretooth_manager = lib::create( 'business\cenozo_manager', SABRETOOTH_URL );
      $sabretooth_manager->use_machine_credentials( true );
      $args['qnaire_rank'] = 1;
      
      $setting_manager = lib::create( 'business\setting_manager' );
      $user = $setting_manager->get_setting( 'general', 'machine_user' );
      $pass = $setting_manager->get_setting( 'general', 'machine_password' );
      $auth['httpauth'] = $user.':'.$pass;
    }  
    $assignment_mod_base = lib::create( 'database\modifier' );
    $assignment_mod_base->where( 'user_id', '=', $db_user->id );
    $max_try = 10;
    $try = 0;
    do
    {
      $mod_limit = clone $base_mod;
      $mod_limit->limit( $limit, $offset );
      $participant_list = $participant_class_name::select( $mod_limit );

      $participant_count = count( $participant_list );
      if( $participant_count > 0 )
      {
        foreach( $participant_list as $db_participant )
        { 
          $db_cohort = $db_participant->get_cohort();
          $assignment_mod = clone $assignment_mod_base;
          $assignment_mod->where( 'participant_id', '=', $db_participant->id );

          if( $assignment_class_name::count( $assignment_mod ) == 0 )
          {
            if( $db_cohort->name == 'tracking' && $has_tracking )
            {
              // are there  any valid recordings?
              $args['participant_id'] = $db_participant->id;
              $recording_list = $sabretooth_manager->pull( 'recording', 'list', $args );

              if( !is_null( $recording_list ) && $recording_list->success == 1 && 
                   is_array( $recording_list->data ) && count( $recording_list->data ) > 0  )
              { 
                 foreach( $recording_list->data as $data )
                 {
                   $url = str_replace( 'localhost', $_SERVER['SERVER_NAME'],
                                        SABRETOOTH_URL . '/' . $data->url );
                   $response = array();
                   http_head( $url, $auth, $response );
                   if( array_key_exists( 'response_code', $response ) )             
                     $found |= $response['response_code'] == 200 ? true : false; 
                 }
              }
            }
            else
            {
              $found = true;
            }  
            if( $found )
            {
              $uid = $db_participant->uid;
              $language = $db_participant->language;
              $language = is_null( $language ) ? 'en' : $language;
              $participant_id = $db_participant->id;
              $cohort = $db_cohort->name;
              $cohort_id = $db_cohort->id;              
              break;
            }
          }
        }
        $offset += $limit;
      }
    } while( !$found && $participant_count > 0 && $max_try > $try++ );
      
    // throw a notice if no participant was found
    if( !$found ) 
      throw lib::create( 'exception\notice',
        'There are currently no participants available for processing.', __METHOD__ );

    //TODO use a semaphore when generating a new assignment

    if( $db_role->name == 'typist' )
    {
      $this->set_item( 'user_id', $db_user->id, true );
    }  
    else
    {
      // get all users with matching language and cohort attributes to select from
      $user_mod = lib::create( 'database\modifier' );
      $user_mod->where( 'user_has_cohort.cohort_id', '=', $cohort_id );
      $user_mod->where( 'language', 'IN', array( 'any', $language ) );

      $user_class_name = lib::get_class_name( 'database\user' );  
      $user_list = $user_class_name::select( $user_mod );

      if( empty( $user_list ) )
       throw lib::create( 'exception\notice',
         'There must be one or more users having the same language ( ' . $language .
         ' ) and cohort (' . $cohort . ' ) attributes as the participant.', __METHOD__ );

      $users = array();
      foreach( $user_list as $db_user )
        $users[ $db_user->id ] = $db_user->name;

      $this->set_item( 'user_id', '', false, $users );      
    }
    $this->set_item( 'participant_id', $participant_id, true );
    $this->set_item( 'cohort_name', $cohort, true );
    $this->set_item( 'uid', $uid, true );
    $this->set_item( 'language', $language, true );
    $this->set_item( 'cohort', $cohort, true );
  }
}
