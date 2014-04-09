<?php
/**
 * assignment.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\database;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * assignment: record
 */
class assignment extends \cenozo\database\record
{
  /** 
   * Get the sibling of this assignment.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return record (NULL if no sibling)
   * @access public
   */
  public function get_sibling_assignment()
  {
    // find a sibling assignment based on participant id and user id uniqueness
    $sibling_mod = lib::create( 'database\modifier' );
    $sibling_mod->where( 'participant_id', '=', $this->participant_id );
    $sibling_mod->where( 'user_id', '!=', $this->user_id );
    $db_assignment = current( static::select( $sibling_mod ) );
    return false === $db_assignment ? NULL : $db_assignment;
  }

  /** 
   * Returns whether all tests constituting this assignment are complete.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param string the role for which completeness is to be determined
   * @return boolean
   * @access public
   */
  public function all_tests_complete()
  {
    $complete_mod = lib::create( 'database\modifier' );
    $complete_mod->where( 'deferred', '=', false );
    $complete_mod->where( 'completed', '=', true );
    return $this->get_test_entry_count() == $this->get_test_entry_count( $complete_mod );

/*
   TODO: check for performance improvements
    $sql = sprintf( 
      'SELECT '.
      '( '.
        '( '.
          'SELECT COUNT(*) FROM test_entry '.
          'JOIN assignment ON assignment.id=test_entry.assigment_id '.
          'WHERE assignment.id = %s '.
        ') - '.
        '( '.
          'SELECT COUNT(*) FROM test_entry '.
          'JOIN assignment ON assignment.id=test_entry.assigment_id '.
          'WHERE assignment.id = %s '.
          'AND deferred = 0 '.
          'AND completed = 1 '.
        ') '.
      ')', $this->id );

    return 0 == static::db()->get_one( $sql );
*/    
  }
}
