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
    $db_language = current( $db_test_entry->get_language_list() );

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'ranked_word_set_id', '!=', NULL );
    $modifier->order( 'ranked_word_set.rank' );
    $entry_data = array();

    // get the primary word entries
    foreach( $db_test_entry->get_test_entry_ranked_word_list( $modifier ) as
             $db_test_entry_ranked_word )
    {
      $db_ranked_word_set = $db_test_entry_ranked_word->get_ranked_word_set();
      $selection = $db_test_entry_ranked_word->selection;
      $db_ranked_word_set_word = $db_ranked_word_set->get_word( $db_language );
      $db_word = $db_test_entry_ranked_word->get_word();
      $classification = '';

      if( !is_null( $db_word ) && 'variant' == $selection )
      {
        $classification = 'variant';
      }

      $entry_data[] =
        array(
          'id' => $db_test_entry_ranked_word->id,
          'ranked_word_set_id' => $db_ranked_word_set->id,
          'ranked_word_set_word' => $db_ranked_word_set_word->word,
          'word_id' => is_null( $db_word ) ? '' : $db_word->id,
          'word' => is_null( $db_word ) ? '' : $db_word->word,
          'selection' => is_null( $selection ) ? '' : $selection,
          'classification' => $classification );
    }

    // now get the intrusions
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'ranked_word_set_id', '=', NULL );
    foreach( $db_test_entry->get_test_entry_ranked_word_list( $modifier ) as
             $db_test_entry_ranked_word )
    {
      $db_word = $db_test_entry_ranked_word->get_word();

      $entry_data[] =
        array(
          'id' => $db_test_entry_ranked_word->id,
          'ranked_word_set_id' => '',
          'ranked_word_set_word' => '',
          'word_id' => is_null( $db_word ) ? '' : $db_word->id,
          'word' => is_null( $db_word ) ? '' : $db_word->word,
          'selection' => '',
          'classification' => is_null( $db_word ) ? '' : 'intrusion' );
    }

    $this->set_variable( 'entry_data', $entry_data );
  }
}
