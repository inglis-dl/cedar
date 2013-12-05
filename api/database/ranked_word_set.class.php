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
   * The type of record which the record has a rank for.
   * @var string
   * @access protected
   * @static
   */
  protected static $rank_parent = 'test';
}
