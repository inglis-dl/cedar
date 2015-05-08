<?php
/**
 * self_shortcuts.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget self shortcuts
 */
class self_shortcuts extends \cenozo\ui\widget\self_shortcuts
{
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
    $is_typist = 'typist' == $session->get_role()->name;

    if( $is_typist )
    {
      $db_user = $session->get_user();

      // determine whether the typist is on a break
      $away_time_mod = lib::create( 'database\modifier' );
      $away_time_mod->where( 'end_datetime', '<=>', NULL );
      $this->set_variable( 'on_break',
        0 < $db_user->get_away_time_count( $away_time_mod ) );
    }

    $this->set_variable( 'breaktime', $is_typist );
  }
}
