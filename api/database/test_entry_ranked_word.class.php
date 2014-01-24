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
  //TODO make this an abstract requirement of all test_entry types
  public static function adjudicate_compare( $a, $b ) {
    for( $i = 0; $i < count( $a ); $i++ )
    {
      if( $a[ $i ]->selection != $b[ $i ]->selection ||
          $a[ $i ]->word_id != $b[ $i ]->word_id ||
          $a[ $i ]->word_candidate != $b[ $i ]->word_candidate ) return 1;
    }      
    return 0;    
  }
}
