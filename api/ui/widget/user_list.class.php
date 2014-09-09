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
    // skip the parent method
    $grand_parent = get_parent_class( get_parent_class( get_class() ) );
    $grand_parent::prepare();

    $this->add_column( 'name', 'string', 'Username', true );
    $this->add_column( 'first_name', 'string', 'First Name', true );
    $this->add_column( 'last_name', 'string', 'Last Name', true );
    $this->add_column( 'active', 'boolean', 'Active', true );
    $this->add_column( 'site.name', 'string', 'Site', false );
    $this->add_column( 'role.name', 'string', 'Role', false );
    $this->add_column( 'language.name', 'string', 'Language', false );
    $this->add_column( 'cohort.name', 'string', 'Cohort', false );
    $this->add_column( 'last_activity', 'fuzzy', 'Last activity', false );
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

    foreach( $this->get_record_list() as $db_user )
    {
      // determine the role
      $modifier = lib::create( 'database\modifier' );
      if( !is_null( $this->db_restrict_site ) )
        $modifier->where( 'access.site_id', '=', $this->db_restrict_site->id );

      $site = 'none';
      $db_sites = $db_user->get_site_list();
      if( 1 == count( $db_sites ) ) $site = $db_sites[0]->get_full_name(); // only one site?
      else if( 1 < count( $db_sites ) ) $site = 'multiple'; // multiple sites?

      $role = 'none';
      $db_roles = $db_user->get_role_list( $modifier );
      if( 1 == count( $db_roles ) ) $role = $db_roles[0]->name; // only one role?
      else if( 1 < count( $db_roles ) ) $role = 'multiple'; // multiple roles?

      // determine the last activity
      $db_activity = $db_user->get_last_activity();
      $last = is_null( $db_activity ) ? NULL : $db_activity->datetime;

      $language = 'none';
      $db_languages = $db_user->get_language_list();
      if( 1 == count( $db_languages ) ) $language = $db_languages[0]->name; // only one language?
      else if( 1 < count( $db_languages ) ) $language = 'multiple'; // multiple languages?

      $cohort = 'none';
      $db_cohort = $db_user->get_cohort_list();

      if( 1 == count( $db_cohort ) ) $cohort = $db_cohort[0]->name; // only one cohort?
      else if( 1 < count( $db_cohort ) ) $cohort = 'multiple'; // multiple cohorts?

      // assemble the row for this record
      $this->add_row( $db_user->id,
        array( 'name' => $db_user->name,
               'first_name' => $db_user->first_name,
               'last_name' => $db_user->last_name,
               'active' => $db_user->active,
               'site.name' => $site,
               'role.name' => $role,
               'language.name' => $language,
               'cohort.name' => $cohort,
               'last_activity' => $last ) );
    }
  }
}
