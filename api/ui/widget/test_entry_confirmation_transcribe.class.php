<?php
/**
 * test_entry_confirmation_transcribe.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget test_entry_confirmation transcribe
 */
class test_entry_confirmation_transcribe extends \cenozo\ui\widget
{
  /** 
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry_confirmation', 'transcribe', $args );
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
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $db_test_entry = $this->parent->get_record();
    $db_test = $db_test_entry->get_test();
    $test_type_name = $db_test->get_test_type()->name;

    if( $test_type_name != 'confirmation' )
      throw lib::create( 'exception\runtime',
              'Widget requires test type to be ranked word, not ' . 
              $test_type_name, __METHOD__ );

    $language = $db_test_entry->get_assignment()->get_participant()->language;
    $language = is_null( $language ) ? 'en' : $language;

    // get the Yes or No words from the confirmation dictionary according to the
    // the language of the participant

    // Get the db entries
    $word_entry_list = array();
    foreach( $db_test_entry->get_test_entry_confirmation_list() as $db_test_entry_confirmation )
    {

      $db_test_entry_confirmation->confirmation
      $word_entry_list[ $db_test_entry_confirmatiion->get_word()->word ] =
        array( 'id' => $db_test_entry_confirmation->id,
               'confirmation' => $db_test_entry_confirmation->confirmation );
               
    }

    $modifier = lib::create( 'database\modifier' );
    $modifier->order( 'rank' );
    $word_list = array();
    foreach( $db_test_entry->get_test()->get_ranked_word_set_list( $modifier )
      as $db_ranked_word_set )
    {
      $db_word = $db_ranked_word_set->get_word( $language );
      $row = array(
               'entry_id' => '',
               'word_id' => $db_word->id,
               'word' => $db_word->word,
               'selection' => '' );
      if( array_key_exists( $db_word->word, $word_entry_list ) )
      {
         $row['entry_id'] = $word_entry_list[$db_word->word]['id'];
         $row['selection'] = $word_entry_list[$db_word->word]['selection'];
      }
      $word_list[] = $row;
    }
    $this->set_variable( 'word_list', $word_list );
    $instruction = "Was the participant able to ";
    if( preg_match( '/alpha/', $db_test->name ) )
    {
      $instruction = $instruction .
        "recite the alphabet, from A, B, C, D and so on?";
    }
    else
    {
      $instruction = $instruction .
        "count from 1 to 20, from 1, 2, 3, 4 and so on?";
    }
    $this->set_variable( 'instruction', $instruction );
    log::debug($word_list);
  }
}
