<?php
/**
 * test.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\database;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * test: record
 */
class test extends \cenozo\database\has_rank
{
  /** 
   * Classify a word candidate based on dictionary membership.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   * @throws exception\runtime
   * @return array()  classification={candidate, primary, intrusion, variant}, record
   */
  public function get_word_classification( $word_candidate, $language = "any" )
  {
    if( is_null( $this->id ) )
      throw lib::create( 'exception\runtime',
        'Tried to classify a word candidate on a test with no id', __METHOD__ );

    $data = array();
    $data['classification'] = 'candidate';
    $data['word'] = NULL;

    $base_mod = lib::create( 'database\modifier' );
    if( 'any' != $language ) $base_mod->where( 'language', '=', $language );
    $base_mod->where( 'word', '=', $word_candidate );
    $base_mod->limit( 1 );

    $modifier = clone $base_mod;
    $modifier->where( 'dictionary_id', '=', $this->get_dictionary()->id );

    $word_class_name = lib::get_class_name( 'database\word' );
    $db_word = $word_class_name::select( $modifier );
    if( !empty( $db_word ) ) 
    {   
      $data['classification'] = 'primary';
      $data['word'] = $db_word[0];
    }    
    else
    {   
      if( $this->strict != 0 ) 
      {   
        $modifier = clone $base_mod;
        $modifier->where( 'dictionary_id', '=', $this->get_intrusion_dictionary()->id );
        $db_word = $word_class_name::select( $modifier );
        if( !empty( $db_word ) ) 
        {   
          $data['classification'] = 'intrusion';
          $data['word'] = $db_word[0];
        }   
        else
        {   
          $modifier = clone $base_mod;
          $modifier->where( 'dictionary_id', '=', $this->get_variant_dictionary()->id );
          $db_word = $word_class_name::select( $modifier );
          if( !empty( $db_word ) ) 
          {   
            $data['classification'] = 'variant';
            $data['word'] = $db_word[0];
          }   
        }   
      }     
    }

    return $data;
  }
}
