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
    reset( $a );
    reset( $b );
    while( !is_null( key( $a ) ) && !is_null( key ( $b ) ) ) 
    {   
      $a_obj = current( $a );  
      $b_obj = current( $b );  
      if( $a_obj->rank != $b_obj->rank ||
          $a_obj->word_id != $b_obj->word_id ) ||
          $a_obj->word_candidate != $b_obj->word_candidate ) return 1;
      next( $a );
      next( $b );
    }   
    return 0;
  }
}
