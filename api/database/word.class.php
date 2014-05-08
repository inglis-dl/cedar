<?php
/**
 * word.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\database;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * word: record
 */
class word extends \cenozo\database\record
{
  /** 
   * Check whether the word contains disallowed characters
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return boolean true if valid word
   * @access protected
   */
  public static function is_valid_word( $word_candidate, $alpha_numeric = false )
  {
    if( $alpha_numeric )
      return ( preg_match( '/^(0|[1-9][0-9]*)$/', $word_candidate ) ||
               preg_match( '/^\pL$/', $word_candidate ) );
    else
      return preg_match( '/^[A-Za-z\pL\-\']+$/', $word_candidate );
  }
}
