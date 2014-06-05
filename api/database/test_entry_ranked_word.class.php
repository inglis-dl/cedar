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
    while( !is_null( key( $rhs_list ) ) && !is_null( key ( $lhs_list ) ) )
    {
      $rhs_list_obj = current( $rhs_list );
      $lhs_list_obj = current( $lhs_list );
      if( $rhs_list_obj->selection != $lhs_list_obj->selection ||
          $rhs_list_obj->word_id != $lhs_list_obj->word_id ) return false;
      next( $rhs_list );
      next( $lhs_list );
    }
    return count( $rhs_list ) != count( $lhs_list );
  }

  /**
   * Get the word for this record.  The word returned depends on the
   * state of the word_id, selection, and ranked_word_set_id columns.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   * @param database\language $db_language The language of the primary ranked word.
   * @return database\word $db_word NULL if not found
   */
  public function get_word( $db_language = NULL )
  {
    $db_word = NULL;
    if( is_null( $db_language ) )
    {
      if( !is_null( $this->word_id ) &&
          ( ( is_null( $this->selection ) && is_null( $this->ranked_word_set_id ) ) ||
            'variant' == $this->selection ) )
      {
        $db_word = $this->get_word();
      }
    }
    else
    {
      if( !is_null( $this->ranked_word_set_id ) )
        $db_word = $this->get_ranked_word_set()->get_word( $db_language );
    }
    return $db_word;
  }
}
