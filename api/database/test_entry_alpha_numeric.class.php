<?php
/**
 * test_entry_alpha_numeric.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\database;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * test_entry_alpha_numeric: record
 */
class test_entry_alpha_numeric extends \cenozo\database\record
{
  /** 
   * Compare test entry lists for adjudication.  Returns true
   * for a difference in entry fields.
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
      if( $a_obj->rank != $b_obj->rank ||
          $a_obj->word_id != $b_obj->word_id ) return 1;
      next( $a );
      next( $b );
    }
    return 0;
  }
}
