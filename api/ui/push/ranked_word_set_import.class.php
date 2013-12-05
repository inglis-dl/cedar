<?php
/**
 * ranked_word_set_import.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: dictionary import words
 *
 * Import words into a dictionary.
 */
class ranked_word_set_import extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'ranked_word_set', 'import', $args );
  }

  /**
   * This method executes the operation's purpose.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $db_test = lib::create( 'database\test', $this->get_argument( 'id' ) );
    $db_dictionary = lib::create( 'database\dictionary', $db_test->dictionary_id );

    $word_class_name = lib::get_class_name( 'database\word' );
    $languages = $word_class_name::get_enum_values( 'language' );
    $word_count = array();
    foreach( $languages as $language )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'word.language', '=', $language );
      $word_count[] = $db_dictionary->get_word_count( $modifier );
    }
    if( count( array_unique( $word_count ) ) != 1 )
      throw lib::create( 'exception\notice', 
        'The primary dictionary must contain at least one word of each language.',
        __METHOD__ );

    $words_per_language = reset( $word_count );

    $word_ids = array();
    foreach( $languages as $language )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'word.dictionary_id', '=', $db_dictionary->id );
      $modifier->where( 'word.language', '=', $language );
      $count = 0;
      foreach( $word_class_name::select( $modifier ) as $db_word )
      {   
        $word_ids[$language][$count++] = $db_word->id;
      }
    }

    for( $i = 0; $i < $words_per_language; $i++ )
    {
      $db_ranked_word_set = lib::create( 'database\ranked_word_set' );
      $db_ranked_word_set->test_id = $db_test->id;
      foreach( $languages as $language )
      {
         $word_id = 'word_' . $language . '_id';
         $db_ranked_word_set->$word_id = $word_ids[$language][$i];
      }
      $db_ranked_word_set->rank = $i + 1;
      $db_ranked_word_set->save();      
    }
  }
}
