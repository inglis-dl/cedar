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
  /**
   * Compare test_entry_ranked_word lists.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   * @return bool true if identical
   */
  public static function compare( $rhs_list, $lhs_list )
  {
    reset( $rhs_list );
    reset( $lhs_list );
    $match = true;
    while( $match && ( !is_null( key( $rhs_list ) ) || !is_null( key ( $lhs_list ) ) ) )
    {
      $rhs_list_obj = current( $rhs_list );
      $lhs_list_obj = current( $lhs_list );
      if( false !== $rhs_list_obj && false !== $lhs_list_obj )
      {
        if( $rhs_list_obj->selection != $lhs_list_obj->selection ||
            $rhs_list_obj->word_id != $lhs_list_obj->word_id ) $match = false;
      }
      else
        $match = false;
      next( $rhs_list );
      next( $lhs_list );
    }
    return $match;
  }
}
