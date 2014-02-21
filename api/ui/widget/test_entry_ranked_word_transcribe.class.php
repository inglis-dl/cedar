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
    parent::__construct( 'test_entry_ranked_word', 'transcribe', $args );
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

    if( $test_type_name != 'ranked_word' )
      throw lib::create( 'exception\runtime',
              'Widget requires test type to be ranked word, not ' . 
              $test_type_name, __METHOD__ );

    $db_participant = $db_test_entry->get_assignment()->get_participant();
    $language = $db_participant->language;
    $language = is_null( $language ) ? 'en' : $language;
    
    $word_list = array();
    $intrusion_list = array();
    foreach( $db_test_entry->get_test_entry_ranked_word_list() as $db_test_entry_ranked_word )
    {

      // if this entry has a word_id, the id refers to that of the ranked word
      // the the selection is variant, then the word_candidate should not be empty
      // if the selection is null and the word_id is null, and the word_candidate is not empty
      // this is an intrusion
      $data = 
          array(
            'id' => $db_test_entry_ranked_word->id,
            'selection' => is_null( $db_test_entry_ranked_word->selection ) ? '' :
               $db_test_entry_ranked_word->selection,  
            'word_candidate' => 
              is_null( $db_test_entry_ranked_word->word_candidate ) ? '' :
                $db_test_entry_ranked_word->word_candidate,
            'classification' => 'empty' );

      if( !is_null( $db_test_entry_ranked_word->word_id ) )
      {
        $word_list[ $db_test_entry_ranked_word->get_word()->word ] = $data;
      }
      else
      { 
        $intrusion_list[] = $data;
      }
    }

    $modifier = lib::create( 'database\modifier' );
    $modifier->order( 'rank' );
    $entry_data = array();

    // Get the list of ranked words in order.
    // Create data for the rows in the transcribe widget's table.
    foreach( $db_test->get_ranked_word_set_list( $modifier )
      as $db_ranked_word_set )
    {
      // Get the word in the participant's language.
      $word_id = 'word_' . $language . '_id';
      $db_word = lib::create( 'database\word', $db_ranked_word_set->$word_id );
      if( array_key_exists( $db_word->word, $word_list ) )
      {
         $entry_data[] = array(
           'id' => $word_list[ $db_word->word ][ 'id' ],
           'word_id' => $db_word->id,
           'word' => $db_word->word,       
           'selection' => $word_list[ $db_word->word ][ 'selection' ],
           'word_candidate' => $word_list[ $db_word->word ][ 'word_candidate' ],
           'classification' => $word_list[ $db_word->word ][ 'classification' ] );
      }
    }

    foreach( $intrusion_list as $intrusion )
    {
      $entry_data[] = array(
        'id' => $intrusion[ 'id' ],
        'word_id' => '',
        'word' => '',
        'selection' => '',
        'word_candidate' => $intrusion[ 'word_candidate' ],
        'classification' => 'intrusion' );      
    }
    $this->set_variable( 'entry_data', $entry_data );
  }
}
