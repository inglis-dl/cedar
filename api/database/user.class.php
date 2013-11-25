<?php
/**
 * user.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\database;
use cenozo\lib, cenozo\log, cedar\util;

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
}
