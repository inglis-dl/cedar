<?php
/**
 * util.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace curry;
use cenozo\lib;

/**
 * util: utility class of static methods
 *
 * Extends cenozo's util class with additional functionality.
 */
class util extends \cenozo\util
{
  /**
   * Attempts to convert a word into its plural form.
   * 
   * Warning: this method by no means returns the correct answer in every case.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $word
   * @return string
   * @static
   * @access public
   */
  public static function pluralize( $word )
  {
    return parent::pluralize( $word );
  }
}
