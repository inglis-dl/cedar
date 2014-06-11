<?php
/**
 * setting_manager.class.php
 *
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace cedar\business;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * Manages software settings
 */
class setting_manager extends \cenozo\business\setting_manager
{
  /**
   * Constructor.
   *
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\argument
   * @access protected
   */
  protected function __construct( $arguments )
  {
    parent::__construct( $arguments );

    $static_settings = $arguments[0];

    // add a few categories to the manager
    foreach( array( 'sabretooth' ) as $category )
    {
      // make sure the category exists
      if( !array_key_exists( $category, $static_settings ) )
        throw lib::create( 'exception\argument',
          'static_settings['.$category.']', NULL, __METHOD__ );

      $this->static_settings[$category] = $static_settings[$category];
    }
  }
}
