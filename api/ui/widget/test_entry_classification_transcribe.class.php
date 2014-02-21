<?php
/**
 * test_entry_classification_transcribe.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget test_entry_classification transcribe
 */
class test_entry_classification_transcribe extends base_transcribe
{
  /** 
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry_classification', 'transcribe', $args );
  }

  /** 
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\runtime
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $db_test_entry = $this->parent->get_record();
    $db_test = $db_test_entry->get_test();
    $test_type_name = $db_test->get_test_type()->name;

    if( $test_type_name != 'classification' )
      throw lib::create( 'exception\runtime',
              'Widget requires test type to be classification, not ' .
              $test_type_name, __METHOD__ );

    $db_participant = $db_test_entry->get_assignment()->get_participant();
    $language = $db_participant->language;
    $language = is_null( $language ) ? 'en' : $language;

    $modifier = lib::create( 'database\modifier' );
    $modifier->order( 'rank' );
    $entry_data = array();
    foreach( $db_test_entry->get_test_entry_classification_list( $modifier ) as $db_test_entry_classification )
    {
      $word_id = is_null( $db_test_entry_classification->word_id ) ? 
                     '' : $db_test_entry_classification->word_id;
      $word = '';
      $word_candidate = is_null( $db_test_entry_classification->word_candidate ) ? 
                           '' :  $db_test_entry_classification->word_candidate;
      $classification = '';

      if( !empty( $word_id ) )
      {
        $db_word = lib::create( 'database\word', $word_id );
        $data = $db_test->get_word_classification( $db_word->word, $db_word->language );
        $word = $db_word->word;
        $classification = $data['classification'];
      }
      else if( !empty( $word_candidate ) )
      {
        $data = $db_test->get_word_classification( $word_candidate, $language );
        $classification = $data['classification'];
      }
      
      $row = array(
               'id' => $db_test_entry_classification->id,
               'rank' => $db_test_entry_classification->rank,
               'word_id' => $word_id,
               'word' => $word,
               'word_candidate' => $word_candidate,
               'classification' => $classification  );

      $entry_data[] = $row;              
    }

    $this->set_variable( 'entry_data', $entry_data );
  }
}
