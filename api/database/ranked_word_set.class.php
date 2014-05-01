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
   * @var string $language the enum language code (default english)
   * @access public
   */
  public function get_word( $language = 'en' )
  {
    $word_id = 'word_' . $language . '_id';
    if( !$this->column_exists( $word_id ) )
      throw lib::create( 'exception\argument', 'language', $language, __METHOD__ );
    return lib::create( 'database\word', $this->$word_id );
  }

  /** 
   * The type of record which the record has a rank for.
   * @var string
   * @access protected
   * @static
   */
  protected static $rank_parent = 'test';
}
