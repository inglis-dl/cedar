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

  /**
   * Get the test_entry daughter table word usage count for this dictionary.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\runtime
   * @return integer
   * @access public
   */
  public function get_usage_count()
  {
    $database_class_name = lib::get_class_name( 'database\database' );

    if( is_null( $this->id ) )
    {
      throw lib::create( 'exception\runtime',
        'Tried to get a usage count for a dictionary with no id', __METHOD__ );
    }

    $id_string = $database_class_name::format_string( $this->id );
    $sql = sprintf(
      'SELECT ( '.
      'IFNULL((SELECT SUM(total) FROM alpha_numeric_word_total WHERE dictionary_id=%s), 0) + '.
      'IFNULL((SELECT SUM(total) FROM confirmation_word_total WHERE dictionary_id=%s), 0) + '.
      'IFNULL((SELECT SUM(total) FROM classification_word_total WHERE dictionary_id=%s), 0) + '.
      'IFNULL((SELECT SUM(total) FROM ranked_word_word_total WHERE dictionary_id=%s), 0))',
      $id_string, $id_string, $id_string, $id_string );

    return static::db()->get_one( $sql );
  }
}
