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
  public static function adjudicate_compare( $a, $b ) { 
    for( $i = 0; $i < count( $a ); $i++ )
    {
      if( $a[ $i ]->confirmation != $b[ $i ]->confirmation ) return 1;
    }
    return 0;
  }
}
