<?php
/**
 * test_entry_ranked_word_adjudicate.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget test_entry_ranked_word adjudicate
 */
class test_entry_ranked_word_adjudicate extends \cenozo\ui\widget
{
  /** 
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry_ranked_word', 'adjudicate', $args );
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

    // parent must be a test_entry_adjudicate widget
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

    $db_test_entry_adjudicate = $db_test_entry->get_adjudicate_entry();
    
    $a = $db_test_entry->get_test_entry_ranked_word_list();
    $b = $db_test_entry_adjudicate->get_test_entry_ranked_word_list();
    $entry_data = array();

    while( !is_null( key( $a ) ) && !is_null( key ( $b ) ) ) 
    {   
      $a_obj = current( $a );
      $b_obj = current( $b );
      if( $a_obj->selection != $b_obj->selection ||
          $a_obj->word_id != $b_obj->word_id ||
          $a_obj->word_candidate != $b_obj->word_candidate )
      {        
        $db_word_1 = is_null(  $a_obj->word_id ) ? null :
          lib::create( 'database\word', $a_obj->word_id );
        $db_word_2 = is_null(  $b_obj->word_id ) ? null :
          lib::create( 'database\word', $b_obj->word_id );

        $row = array(
                 'id_1' => $a_obj->id,
                 'id_2' => $b_obj->id,
                 'selection_1' => $a_obj->selection,
                 'selection_2' => $b_obj->selection,
                  
                 'word_id_1' => is_null( $db_word_1 ) ? '' :  $db_word_1->id,
                 'word_1' => is_null( $db_word_1 ) ? '' :  $db_word_1->word,
                 'word_id_2' => is_null( $db_word_2 ) ? '' :  $db_word_2->id,
                 'word_2' => is_null( $db_word_2 ) ? '' :  $db_word_2->word 
                 'word_candidate_1' => is_null( $a->word_candidate ) ? '' :  $a->word_candidate,
                 'word_candidate_2' => is_null( $b->word_candidate ) ? '' :  $b->wor    d_candidate );

        $entry_data[] = $row;
      }   
      next( $a );
      next( $b );
    }
    $this->set_variable( 'entry_data', $entry_data );
  }
}
