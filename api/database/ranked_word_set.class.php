<?php
/**
 * ranked_word_set.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\database;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * ranked_word_set: record
 */
class ranked_word_set extends \cenozo\database\has_rank
{
  /**
   * Get a word from this ranked word set by language.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @var database\language $db_language the language (default English)
   * @access public
   */
  public function get_word( $db_language )
  {
    if( is_null( $db_language ) )
      throw lib::create( 'exception\notice',
        'A language must be specified to retrieve a word from a ranked word set',  __METHOD__ );

    $database_class_name = lib::get_class_name( 'database\database' );
    $sql = sprintf(
      'SELECT word_id FROM ranked_word_set_has_language '.
      'WHERE language_id=%s '.
      'AND ranked_word_set_id=%s',
      $database_class_name::format_string( $db_language->id ),
      $database_class_name::format_string( $this->id ) );
    $id = static::db()->get_one( $sql );

    return is_null( $id ) ? $id : lib::create( 'database\word', $id );
  }

  /**
   * The type of record which the record has a rank for.
   * @var string
   * @access protected
   * @static
   */
  protected static $rank_parent = 'test';
}
