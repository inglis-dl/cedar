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

    $ranked_word_set_has_language_class_name =
      lib::get_class_name( 'database\ranked_word_set_has_language' );

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'language_id', '=', $db_language->id );
    $modifier->where( 'ranked_word_set_id', '=', $this->id );
    $modifier->limit( 1 );

    $db_ranked_word_set_has_language = current(
      $ranked_word_set_has_language_class_name::select( $modifier ) );
    return $db_ranked_word_set_has_language->get_word();
  }

  /**
   * The type of record which the record has a rank for.
   * @var string
   * @access protected
   * @static
   */
  protected static $rank_parent = 'test';
}
