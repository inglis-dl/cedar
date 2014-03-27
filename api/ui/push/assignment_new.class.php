<?php
/**
 * assignment_new.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: assignment new
 *
 * Create a new assignment.
 */
class assignment_new extends \cenozo\ui\push\base_new
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'assignment', $args );
  }

  /** 
   * Processes arguments, preparing them for the operation.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    $assignment_class_name = lib::get_class_name( 'database\assignment' );
    $event_type_class_name = lib::get_class_name( 'database\event_type' );
    $participant_class_name = lib::get_class_name( 'database\participant' );

    $columns = $this->get_argument( 'columns', array() );
    if( empty( $columns ) )
    {  
      $this->arguments['columns'] = $columns;
    }
     
    if( ( !array_key_exists( 'user_id', $columns ) || 0 == strlen( $columns['user_id'] ) ) || 
         ( !array_key_exists( 'participant_id', $columns ) || 
           0 == strlen( $columns['participant_id'] ) ) )
    {
      $session = lib::create( 'business\session' );
      $db_role = $session->get_role();
      $db_user = $session->get_user();

      // filter on participants with cohorts this user is assigned to
      $cohort_ids = array();
      $has_tracking = false;
      $has_comprehensive = false;
      $cohort_list = $db_user->get_cohort_list();
      if( is_null( $cohort_list ) )
        throw lib::create( 'exception\notice',
          'There must be one or more cohorts assigned to user: '. $db_user->name,
            __METHOD__ );

      foreach( $cohort_list as $db_cohort )
      {
        $cohort_ids[] = $db_cohort->id;
        $has_tracking |= 'tracking' == $db_cohort->name;
        $has_comprehensive |= 'comprehensive' == $db_cohort->name;
      }

      $base_mod = lib::create( 'database\modifier' );
      $base_mod->where( 'active', '=', true );
      $base_mod->where( 'cohort_id', 'IN', $cohort_ids );

      // filter on participants who have the same language as the user
      if( $db_user->language != 'any' )
      {
        $base_mod->where( 'language', '=', $db_user->language );
      }

      // the participant must have completed their interview
      if( $has_tracking && $has_comprehensive )
      {
        $base_mod->where_bracket( true );

        // tracking
        $base_mod->where_bracket( true );
        $base_mod->where( 'event.event_type_id', '=', 
           $event_type_class_name::get_unique_record( 'name', 'completed (Baseline)' )->id );
         
        $base_mod->where_bracket( false );

        // comprehensive
        $base_mod->where_bracket( true, true );
        $base_mod->where( 'event.event_type_id', '=', 
          $event_type_class_name::get_unique_record( 'name', 'completed (Baseline Site)' )->id );

        $base_mod->where_bracket( false );
        $base_mod->where_bracket( false );
      }
      else if( $has_tracking )
      {
        $base_mod->where( 'event.event_type_id', '=', 
           $event_type_class_name::get_unique_record( 'name', 'completed (Baseline)' )->id );
      }
      else if( $has_comprehensive )
      {
        $base_mod->where( 'event.event_type_id', '=', 
          $event_type_class_name::get_unique_record( 'name', 'completed (Baseline Site)' )->id );
      }

      $sabretooth_manager = NULL;
      if( $has_tracking )
      {
        $setting_manager = lib::create( 'business\setting_manager' );
        $sabretooth_manager = lib::create( 'business\cenozo_manager', SABRETOOTH_URL );
        $sabretooth_manager->set_user( $setting_manager->get_setting( 'sabretooth', 'user' ) );
        $sabretooth_manager->set_password( $setting_manager->get_setting( 'sabretooth', 'password' ) );
        $sabretooth_manager->set_site( $setting_manager->get_setting( 'sabretooth', 'site' ) );
        $sabretooth_manager->set_role( $setting_manager->get_setting( 'sabretooth', 'role' ) );
      } 

      $max_try = 500;
      $try = 0;
      $participant_id = NULL;
      $cohort_name = '';
      $found = false;
      $limit = 10;
      $offset = 0;
      $participant_count = 0;

      // block with a semaphore
      $session->acquire_semaphore();
      do
      {
        $mod_limit = clone $base_mod;
        $mod_limit->limit( $limit, $offset );
        $participant_list = $participant_class_name::select( $mod_limit );

        $participant_count = count( $participant_list );
        if( 0 < $participant_count )
        {
          foreach( $participant_list as $db_participant )
          {
            $db_cohort = $db_participant->get_cohort();
            $db_assignment = $assignment_class_name::get_unique_record(
              array( 'user_id', 'participant_id' ),
              array( $db_user->id, $db_participant->id ) );

            $assignment_total_mod = lib::create( 'database\modifier' );
            $assignment_total_mod->where( 'participant_id', '=', $db_participant->id );
            $assignment_total_count = $assignment_class_name::count( $assignment_total_mod );

            if( is_null( $db_assignment ) && 2 > $assignment_total_count )
            {
              // now see if this participant has any recordings
              if( $has_tracking )
              {
                $args = array(
                  'qnaire_rank' => 1,
                  'participant_id' => $db_participant->id );
                $recording_list = $sabretooth_manager->pull( 'recording', 'list', $args );
                $recording_data = array();
                if( !is_null( $recording_list ) &&
                    1 == $recording_list->success && 0 < count( $recording_list->data ) )
                {
                  $participant_id = $db_participant->id;
                  $cohort_name = $db_cohort->name; 
                  $found = true;
                  break;
                }
              }
              if( !$found && $has_comprehensive )
              {
                 // stub until comprehensive recordings are worked out
              } 
            }
          }
          $offset += $limit;
        }
      } while( !$found && $participant_count > 0 && $max_try > $try++ );

      $session->release_semaphore();

      // throw a notice if no participant was found
      if( !$found ) 
        throw lib::create( 'exception\notice',
          'There are currently no participants available for processing.', __METHOD__ );
       
      $columns['user_id'] = $db_user->id;
      $columns['participant_id'] = $participant_id;
      $columns['cohort_name'] = $cohort_name;
      $this->arguments['columns'] = $columns;
    }        
        
    parent::prepare();
  }

  /** 
   * Finishes the operation with any post-execution instructions that may be necessary.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\runtime
   * @access protected
   */
  protected function finish()
  {
    parent::finish();

    $assignment_manager = lib::create( 'business\assignment_manager' );
    $assignment_manager::initialize( $this->get_record() );
  }
}
