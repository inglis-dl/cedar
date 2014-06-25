<?php
/**
 * dictionary.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\database;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * dictionary: record
 */
class dictionary extends \cenozo\database\record
{

  /**
   * Returns a list of words.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return array
   * @static
   * @access public
   */
  public static function get_word_list_words( $modifier = NULL )
  {
    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );

    return static::db()->get_col( sprintf(
      'SELECT word FROM word %s',
      $modifier->get_sql() ) );
  }
}
