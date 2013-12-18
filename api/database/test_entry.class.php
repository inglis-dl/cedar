<?php
/**
 * test_entry.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\database;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * test_entry: record
 */
class test_entry extends \cenozo\database\record
{

  /** 
   * Get the previous record according to test rank.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   */
  public function get_previous()
  {
    $db_prev_test_entry = NULL;
    if( is_null( $this->id ) )
    {
       log::warning( 'tried to get previous test_entry on test_entry with no id' );
    }
    else
    {
      $test_class_name = lib::get_class_name('database\test');
      $db_prev_test = $test_class_name::get_unique_record( 'rank', $this->get_test()->rank - 1 );
      if( !is_null( $db_prev_test ) ) 
        $db_prev_test_entry = static::get_unique_record( 
          array( 'test_id', 'assignment_id' ),
          array( $db_prev_test->id, $this->assignment_id ) );
    }
    return $db_prev_test_entry;
  }

  /** 
   * Get the next record according to test rank.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   */
  public function get_next()
  {
    $db_next_test_entry = NULL;
    if( is_null( $this->id ) )
    {
       log::warning( 'tried to get next test_entry on test_entry with no id' );
    }
    else
    {
      $test_class_name = lib::get_class_name('database\test');
      $db_next_test = $test_class_name::get_unique_record( 'rank', $this->get_test()->rank + 1 );
      if( !is_null( $db_next_test ) ) 
        $db_next_test_entry = static::get_unique_record( 
          array( 'test_id', 'assignment_id' ),
          array( $db_next_test->id, $this->assignment_id ) );
    }
    return $db_next_test_entry;
  }

}
