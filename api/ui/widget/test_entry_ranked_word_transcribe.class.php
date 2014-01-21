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
class test_entry_ranked_word_transcribe extends \cenozo\ui\widget
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
   * Processes arguments, preparing them for the operation.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    // parent must be a test_entry_transcribe widget
    if( is_null( $this->parent ) )
      throw lib::create( 'exception\runtime', 'This class must have a parent', __METHOD__ );
   
    $db_test_entry = $this->parent->get_record();

    $db_test = $db_test_entry->get_test();
    $heading = $db_test->name . ' test entry form';

    //TODO put this somewhere else
    if( $db_test_entry->deferred )
      $heading = $heading . ' NOTE: this test is currently deferred';

    $this->set_heading( $heading );
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

    $language = $db_test_entry->get_assignment()->get_participant()->language;
    $language = is_null( $language ) ? 'en' : $language;
    
    $entry_list = array();
    foreach( $db_test_entry->get_test_entry_ranked_word_list() as $db_test_entry_ranked_word )
    {
      $entry_list[ $db_test_entry_ranked_word->get_word()->word ] = 
        array(
          'id' => $db_test_entry_ranked_word->id,
          'selection' => is_null( $db_test_entry_ranked_word->selection ) ? '' :
             $db_test_entry_ranked_word->selection,  
          'word_candidate' => 
            is_null( $db_test_entry_ranked_word->word_candidate ) ? '' :
              $db_test_entry_ranked_word->word_candidate );  
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
      $row = array(
               'id' => '',
               'word_id' => $db_word->id,
               'word' => $db_word->word,
               'selection' => '',
               'word_candidate' => '' );
      if( array_key_exists( $db_word->word, $entry_list ) )
      {
         $row['id'] = $entry_list[$db_word->word]['id'];
         $row['selection'] = $entry_list[$db_word->word]['selection'];
         $row['word_candidate'] = $entry_list[$db_word->word]['word_candidate'];
      }
      $entry_data[] = $row;
    }
    $this->set_variable( 'entry_data', $entry_data );
  }
}
