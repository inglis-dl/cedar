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
    parent::__construct( 'test_entry', 'ranked_word_transcribe', $args );
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

    log::debug( $db_test_entry );
    die;
    
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
    $language = $db_test_entry->get_assignment()->get_participant()->language;
    $language = is_null( $language ) ? 'en' : $language;

    //get this test_entry's data
    if( $test_type_name == "confirmation" )
    {
    }
    else if( $test_type_name == "alpha_numeric")
    {
    }
    else if( $test_type_name == "classification" )
    {
    }
    else if( $test_type_name == "ranked_word_transcribe" )
    {
      
      $word_entry_list = array();
      foreach( $db_test_entry->get_test_entry_word_list() as $db_test_entry_word )
      {
        $word_entry_list[ $db_test_entry_word->get_word()->word ] = 
          array( 'id' => $db_test_entry_word->id,
                 'selection' => $db_test_entry_word->selection,  
                 'word_candidate' => $db_test_entry_word->word_candidate );  
      }    

      $modifier = lib::create( 'database\modifier' );
      $modifier->order( 'rank' );
      $word_list = array();
      foreach( $db_test->get_ranked_word_transcribe_set_list( $modifier )
        as $db_ranked_word_transcribe_set )
      {
        $db_word = $db_ranked_word_transcribe_set->get_word( $language );
        $row = array(
                 'entry_id' => '', 
                 'word_id' => $db_word->id,
                 'word' => $db_word->word,
                 'selection' => '',
                 'word_candidate' => '' );
        if( array_key_exists( $db_word->word, $word_entry_list ) )
        {
           $row['entry_id'] = $word_entry_list[$db_word->word]['id'];
           $row['selection'] = $word_entry_list[$db_word->word]['selection'];
           $row['word_candidate'] = $word_entry_list[$db_word->word]['word_candidate'];
        }
        $word_list[] = $row;
      }
      $this->set_variable( 'word_list', $word_list );
      log::debug($word_list);
    }
    else
    {
      throw lib::create( 'exception\runtime', 'Unkown test type', __METHOD__ );
    }

  }
}
