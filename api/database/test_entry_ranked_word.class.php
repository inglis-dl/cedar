<?php
/**
 * test_entry_ranked_word.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\database;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * test_entry_ranked_word: record
 */
class test_entry_ranked_word extends \cenozo\database\record 
{
  //TODO make this an abstract requirement of all test_entry types
  /** 
   * Compare test entry lists for adjudication.  Returns true
   * for a difference in entry fields or their number.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   */
  public static function adjudicate_compare( $a, $b )
  {
    reset( $a );
    reset( $b );
    while( !is_null( key( $a ) ) && !is_null( key ( $b ) ) ) 
    {   
      $a_obj = current( $a );  
      $b_obj = current( $b );  
      if( $a_obj->selection != $b_obj->selection ||
          $a_obj->word_id != $b_obj->word_id ||
          $a_obj->word_candidate != $b_obj->word_candidate ) return 1;
      next( $a );
      next( $b );
    }   
    return count( $a ) != count( $b );
  }
}
