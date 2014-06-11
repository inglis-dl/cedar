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
    {
      $word_list = explode( ' ', $word_candidate );
      foreach( $word_list as $word )
      {
        if( !preg_match( '/^[A-Za-z\pL\-\']+$/', $word ) ) return false;
      }
      return true;
    }
  }

  /**
   * Get the test_entry daughter table usage count for this word.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\runtime
   * @return integer
   * @access public
   */
  public function get_usage_count()
  {
    if( is_null( $this->id ) )
    {
      throw lib::create( 'exception\runtime',
        'Tried to get a usage count for a word with no id', __METHOD__ );
    }
    $test_class_name = lib::get_class_name( 'database\test' );
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'dictionary_id', '=', $this->dictionary_id );
    $modifier->or_where( 'variant_dictionary_id', '=', $this->dictionary_id );
    $modifier->or_where( 'intrusion_dictionary_id', '=', $this->dictionary_id );
    $modifier->or_where( 'mispelled_dictionary_id', '=', $this->dictionary_id );
    $db_test = current( $test_class_name::select( $modifier ) );
    $column = $db_test->get_test_type()->name . '_word_total';

    return static::db()->get_one(
      sprintf( 'SELECT total FROM %s WHERE word_id = %s',
               $column, $this->id ) );
  }
}
