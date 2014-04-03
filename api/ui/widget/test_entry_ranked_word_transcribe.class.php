<?php
/**
 * test_entry_ranked_word_transcribe.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget test_entry_ranked_word transcribe
 */
class test_entry_ranked_word_transcribe extends base_transcribe
{
  /** 
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry_ranked_word', $args );
  }

  /** 
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $db_test_entry = $this->parent->get_record();
    $db_test = $db_test_entry->get_test();

    $db_participant = $db_test_entry->get_assignment()->get_participant();
    $language = $db_participant->language;
    $language = is_null( $language ) ? 'en' : $language;
    $word_id = 'word_' . $language . '_id';
    
    $modifier = lib::create( 'database\modifier' );
    $modifier->order( 'ranked_word_set.rank' );
    $entry_data = array();
    foreach( $db_test_entry->get_test_entry_ranked_word_list( $modifier ) as 
             $db_test_entry_ranked_word )
    {
      $db_ranked_word_set = $db_test_entry_ranked_word->get_ranked_word_set();
      if( !is_null( $db_ranked_word_set ) )
      $db_ranked_word_set_word = is_null( $db_ranked_word_set ) ? NULL : 
        lib::create( 'database\word', $db_ranked_word_set->$word_id );

      $selection = $db_test_entry_ranked_word->selection;
      $db_word = $db_test_entry_ranked_word->get_word();
      $classification = '';

      if( is_null( $selection ) && is_null( $db_ranked_word_set_word ) && !is_null( $db_word ) )
      {
        $classification = 'intrusion';
      }
      else if( !is_null( $db_word ) && $selection == 'variant' )
      {
        $classification = 'variant';
      }      

      $entry_data[] =
        array(
          'id' => $db_test_entry_ranked_word->id,
          'ranked_word_set_id' => is_null( $db_ranked_word_set ) ? '' : $db_ranked_word_set->id,
          'ranked_word_set_word' => 
            is_null( $db_ranked_word_set_word ) ? '' : $db_ranked_word_set_word->word,
          'word_id' => is_null( $db_word ) ? '' : $db_word->id,
          'word' => is_null( $db_word ) ? '' : $db_word->word,
          'selection' => is_null( $selection ) ? '' : $selection,
          'classification' => $classification );
    }

    $this->set_variable( 'entry_data', $entry_data );
  }
}
