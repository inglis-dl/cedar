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
class test_entry_alpha_numeric extends \cenozo\database\has_rank
{
  /** 
   * Compare test_entry_alpha_numeric list.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   * @param array (database\test_entry_alpha_numeric) $rhs_list
   * @param array (database\test_entry_alpha_numeric) $lhs_list
   * @return bool true if identical
   */
  public static function compare( $rhs_list, $lhs_list )
  { 
    reset( $rhs_list );
    reset( $lhs_list );
    while( !is_null( key( $rhs_list ) ) && !is_null( key ( $lhs_list ) ) )
    {
      $rhs_list_obj = current( $rhs_list ); 
      $lhs_list_obj = current( $lhs_list ); 
      if( $rhs_list_obj->rank != $lhs_list_obj->rank ||
          $rhs_list_obj->word_id != $lhs_list_obj->word_id ) return false;
      next( $rhs_list );
      next( $lhs_list );
    }
    return true;
  }

  /** 
   * The type of record which the record has a rank for.
   * @var string
   * @access protected
   * @static
   */
  protected static $rank_parent = 'test_entry';
}
