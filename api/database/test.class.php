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
   * Get the variant dictionary.
   * This method is required because there is no variant_dictionary table,
   * only a dictionary table.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   * @return record() db_dictionary or NULL
   */
  public function get_variant_dictionary()
  {
    return is_null( $this->variant_dictionary_id ) ? NULL :
           lib::create( 'database\dictionary', $this->variant_dictionary_id );
  }

  /** 
   * Get the intrusion dictionary.
   * This method is required because there is no intrusion_dictionary table,
   * only a dictionary table.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   * @return record() db_dictionary or NULL
   */
  public function get_intrusion_dictionary()
  {
    return is_null( $this->intrusion_dictionary_id ) ? NULL :
           lib::create( 'database\dictionary', $this->intrusion_dictionary_id );
  }

  /** 
   * Classify a word candidate based on dictionary membership.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   * @throws exception\runtime
   * @return array()  classification={candidate, primary, intrusion, variant}, record
   */
  public function get_word_classification( $word_candidate, $language = 'any' )
  {
    if( ( !$this->strict && is_null( $this->dictionary_id ) ) ||
         ( is_null( $this->dictionary_id ) || 
           is_null( $this->intrusion_dictionary_id ) ||
           is_null( $this->variant_dictionary_id ) ) )
      throw lib::create( 'exception\runtime',
        'Word classification requires the test to have the necessary ' .
        'dictionaries assigned.' , __METHOD__ );

    $word_class_name = lib::get_class_name( 'database\word' );

    $data = array();
    $data['classification'] = 'candidate';
    $data['word'] = NULL;

    $base_mod = lib::create( 'database\modifier' );
    if( 'any' != $language ) $base_mod->where( 'language', '=', $language );
    $base_mod->where( 'word', '=', $word_candidate );
    $base_mod->limit( 1 );

    $modifier = clone $base_mod;
    $modifier->where( 'dictionary_id', '=', $this->dictionary_id );

    $db_word = current( $word_class_name::select( $modifier ) );
    if( !is_null( $db_word ) ) 
    {   
      $data['classification'] = 'primary';
      $data['word'] = $db_word;
    }    
    else
    {   
      // non-strict tests must have their variant and intrusion dictionaries assigned
      if( !$this->strict ) 
      {   
        $modifier = clone $base_mod;
        $modifier->where( 'dictionary_id', '=', $this->intrusion_dictionary_id );
        $db_word = current( $word_class_name::select( $modifier ) );
        if( !is_null( $db_word ) ) 
        {   
          $data['classification'] = 'intrusion';
          $data['word'] = $db_word;
        }   
        else
        {   
          $modifier = clone $base_mod;
          $modifier->where( 'dictionary_id', '=', $this->variant_dictionary_id );
          $db_word = current( $word_class_name::select( $modifier ) );
          if( !is_null( $db_word ) ) 
          {   
            $data['classification'] = 'variant';
            $data['word'] = $db_word;
          }   
        }   
      }     
    }

    return $data;
  }
}
