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
  public static function adjudicate_compare( $a, $b ) { 
    for( $i = 0; $i < count( $a ); $i++ )
    {   
      if( $a[ $i ]->rank != $b[ $i ]->rank ||
          $a[ $i ]->word_id != $b[ $i ]->word_id ) return 1;
    }     
    return 0;
  }
}
