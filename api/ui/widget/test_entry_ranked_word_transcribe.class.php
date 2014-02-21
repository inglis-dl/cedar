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
    $modifier = lib::create( 'database\modifier' );
    $modifier->order( 'id' );
    $entry_data = array();
    foreach( $db_test_entry->get_test_entry_ranked_word_list( $modifier ) as 
             $db_test_entry_ranked_word )
    {
      // if this entry has a word_id, the id refers to that of the ranked word
      // if the selection is 'variant', then the word_candidate should not be empty
      // if the selection is null and the word_id is null, and the word_candidate is not empty
      // this is an intrusion

      $selection = is_null( $db_test_entry_ranked_word->selection ) ? '' :
                            $db_test_entry_ranked_word->selection;                            
      $word_candidate = is_null( $db_test_entry_ranked_word->word_candidate ) ? '' :
                                 $db_test_entry_ranked_word->word_candidate;
      $word_id = is_null( $db_test_entry_ranked_word->word_id ) ? '' : 
                          $db_test_entry_ranked_word->word_id;
      $word = '';                    
      $classification = '';                    

      if( !empty( $word_id ) )
      {
        $word = $db_test_entry_ranked_word->get_word()->word;
        if( !empty( $word_candidate ) && $selection == 'variant' )
        {
          $data = $db_test_entry->get_test()->get_word_classification(
                  $word_candidate, $language );
          $classification = $data['classification'];        
        }
      }
      else
      { 
        $classification = 'intrusion';  
      }

      $entry_data[] = 
          array(
            'id' => $db_test_entry_ranked_word->id,
            'word_id' => $word_id,
            'word' => $word,
            'selection' => $selection,  
            'word_candidate' => $word_candidate,
            'classification' => $classification );
    }

    $this->set_variable( 'entry_data', $entry_data );
  }
}
