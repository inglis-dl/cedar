<?php
/**
 * away_time_delete.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: away_time delete
 */
class away_time_delete extends \cenozo\ui\push\base_delete
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'away_time', $args );
  }

  /**
   * Delete any user_time for the day the of the away time so that it is re-calculated
   * next time it is needed.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $user_time_class_name = lib::get_class_name( 'database\user_time' );
    $user_time_mod = lib::create( 'database\modifier' );
    $user_time_mod->where( 'user_id', '=', $this->get_record()->user_id );
    $user_time_mod->where( 'site_id', '=', $this->get_record()->site_id );
    $user_time_mod->where( 'role_id', '=', $this->get_record()->role_id );
    $user_time_mod->where(
      'date', '>=', sprintf( 'DATE( "%s" )', $this->get_record()->start_datetime ), false );
    $user_time_mod->where(
      'date', '<=', sprintf( 'DATE( "%s" )', $this->get_record()->end_datetime ), false );
    foreach( $user_time_class_name::select( $user_time_mod ) as $db_user_time )
      $db_user_time->delete();
  }
}
