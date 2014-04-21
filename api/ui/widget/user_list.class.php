<?php
/**
 * user_list.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log;

/**
 * widget user list
 */
class user_list extends \cenozo\ui\widget\user_list
{
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

    $this->add_column( 'cohort.name', 'string', 'Cohort', false );
  }

  /**
   * Defines all rows in the list.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    // skip the parent method
    $grand_parent = get_parent_class( get_parent_class( get_class() ) );
    $grand_parent::setup();
    
    foreach( $this->get_record_list() as $record )
    {
      // determine the role
      $modifier = lib::create( 'database\modifier' );
      if( !is_null( $this->db_restrict_site ) ) 
        $modifier->where( 'access.site_id', '=', $this->db_restrict_site->id );

      $site = 'none';
      $db_sites = $record->get_site_list();
      if( 1 == count( $db_sites ) ) $site = $db_sites[0]->get_full_name(); // only one site?
      else if( 1 < count( $db_sites ) ) $site = 'multiple'; // multiple sites?
     
      $role = 'none';
      $db_roles = $record->get_role_list( $modifier );
      if( 1 == count( $db_roles ) ) $role = $db_roles[0]->name; // only one role?
      else if( 1 < count( $db_roles ) ) $role = 'multiple'; // multiple roles?
     
      // determine the last activity
      $db_activity = $record->get_last_activity();
      $last = is_null( $db_activity ) ? NULL : $db_activity->datetime;

      $cohort = 'none';
      $db_cohort = $record->get_cohort_list();

      if( 1 == count( $db_cohort ) ) $cohort = $db_cohort[0]->name; // only one cohort?
      else if( 1 < count( $db_cohort ) ) $cohort = 'multiple'; // multiple cohorts?

      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'name' => $record->name,
               'first_name' => $record->first_name,
               'last_name' => $record->last_name,
               'active' => $record->active,
               'site.name' => $site,
               'role.name' => $role,
               'last_activity' => $last,
               'cohort.name' => $cohort ) );
    }
  }
}
