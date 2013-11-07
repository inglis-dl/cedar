<?php
/**
 * user.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace curry\database;
use cenozo\lib, cenozo\log, curry\util;

/**
 * user: record
 */
class user extends \cenozo\database\user
{
  /** 
   * Make sure to only include cohorts which this user has access to.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( database\cohort )
   * @access public
   */
  protected function get_record_list(
    $record_type, $modifier = NULL, $inverted = false, $count = false )
  {
    if( 'cohort' == $record_type )
    {   
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'user_has_cohort.user_id', '=',
                        lib::create( 'business\session' )->get_user()->id );
    }   
    return parent::get_record_list( $record_type, $modifier, $inverted, $count );
  }

  /**
   * Adds one or more cohorts so a user.
   * This method effectively overrides the parent add_records() method so that grouping can also
   * be included.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param int|array(int) $cohort_ids A single or array of cohort ids
   * @access public
   */
  public function add_cohort( $cohort_ids, $grouping )
  {
    parent::add_cohort( $cohort_ids );

    // do nothing if the user has no primary key
    if( is_null( $this->id ) ) return;

    $database_class_name = lib::get_class_name( 'database\database' );

    // cohort_ids may be a single integer, make sure it is an array
    if( !is_array( $cohort_ids ) ) $cohort_ids = array( $cohort_ids );

    database::$debug = true;
    static::db()->execute( sprintf(
      'UPDATE user_has_cohort '.
      'WHERE user_id = %s '.
      'AND cohort_id IN ( %s )',
      $database_class_name::format_string( $this->id ),
      $database_class_name::format_string( implode( ',', $cohort_ids ) ) ) );
    database::$debug = false;
  }
}


