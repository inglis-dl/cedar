<?php
/**
 * test_entry_confirmation.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\database;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * test_entry_confirmation: record
 */
class test_entry_confirmation extends \cenozo\database\record 
{
  /** 
   * Compare test_entry_confirmation lists.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   * @return bool true if identical
   */
  public static function compare( $lhs_list, $rhs_list )
  {
    reset( $rhs_list );
    reset( $lhs_list );
    while( !is_null( key( $rhs_list ) ) && !is_null( key ( $lhs_list ) ) ) 
    {   
      $rhs_list_obj = current( $rhs_list );  
      $lhs_list_obj = current( $lhs_list );  
      if( $rhs_list_obj->confirmation != $lhs_list_obj->confirmation ) return false;
      next( $rhs_list );
      next( $lhs_list );
    }   
    return true;
  }    
}
