<?php
/**
 * dictionary.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\database;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * dictionary: record
 */
class dictionary extends \cenozo\database\record
{

  /**
   * Returns a list of words.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return array
   * @static
   * @access public
   */
  public static function get_word_list_words( $modifier = NULL )
  {
    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );

    return static::db()->get_col( sprintf(
      'SELECT word FROM word %s',
      $modifier->get_sql() ) );
  }

  /**
   * Get the test_entry daughter table word usage count for this dictionary.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\runtime
   * @return integer
   * @access public
   */
  public function get_usage_count()
  {
    $database_class_name = lib::get_class_name( 'database\database' );
    $test_class_name = lib::get_class_name( 'database\test' );

    if( is_null( $this->id ) )
      throw lib::create( 'exception\runtime',
        'Tried to get a usage count for a dictionary with no id', __METHOD__ );

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'dictionary_id', '=', $this->id );
    $modifier->or_where( 'intrusion_dictionary_id', '=', $this->id );
    $modifier->or_where( 'variant_dictionary_id', '=', $this->id );
    $modifier->or_where( 'mispelled_dictionary_id', '=', $this->id );
    $modifier->limit( 1 );
    $db_test = current( $test_class_name::select( $modifier ) );
    $usage_count = 0;
    if( false !== $db_test )
    {
      $id_string = $database_class_name::format_string( $this->id );
      $sql = sprintf(
        'SELECT( IFNULL((SELECT SUM(total) FROM %s_word_total WHERE dictionary_id=%s), 0))' ,
        $db_test->get_test_type()->name, $id_string );

      $usage_count = static::db()->get_one( $sql );
    }
    return $usage_count;
  }

  /**
   * Transfer words from this dictionary record to another or delete those words having no
   * usage and if no destination is specified.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @arg array  $is_list array of word ids to transfer
   * @arg database\record $db_dictionary the dictionary to send the words to (NULL => delete)
   * @throws exception\runtime
   * @access public
   */
  public function transfer_word( $id_list, $db_dictionary = NULL )
  {
    // stub
  }
}
