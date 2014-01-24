<?php
/**
 * test_entry_classification.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\database;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * test_entry_classification: record
 */
class test_entry_classification extends \cenozo\database\has_rank
{
  public static function adjudicate_compare( $a, $b ) { 
    for( $i = 0; $i < count( $a ); $i++ )
    {
      if( $a[ $i ]->rank != $b->rank ||
          $a[ $i ]->word_id != $b[ $i ]->word_id ||
          $a[ $i ]->word_candidate != $b[ $i ]->word_candidate ) return 1;
    }
    return 0;
  }
}
