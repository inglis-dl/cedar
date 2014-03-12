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
class test_entry_ranked_word_adjudicate extends base_adjudicate
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

    $language = $db_test_entry->get_assignment()->get_participant()->language;
    $language = is_null( $language ) ? 'en' : $language;

    $db_test_entry_adjudicate = $db_test_entry->get_adjudicate_record();

    $word_mod = lib::create( 'database\modifier' );
    $word_mod->where( 'word_id', '!=', '' );
    $word_mod->where( 'selection', '!=', '' );
    $word_mod->order( 'id' );
    $a = $db_test_entry->get_test_entry_ranked_word_list( clone $word_mod ); 
    $b = $db_test_entry_adjudicate->get_test_entry_ranked_word_list( clone $word_mod );

    $entry_data = array();
    while( !is_null( key( $a ) ) && !is_null( key ( $b ) ) ) 
    {   
      $a_obj = current( $a );
      $b_obj = current( $b );
      if( $a_obj->selection != $b_obj->selection ||
          $a_obj->word_candidate != $b_obj->word_candidate )
      {        
        if( $a_obj->word_id != $b_obj->word_id )
        {
          throw lib::create( 'exception\runtime',
            'Unmatched words found in adjudicate pair',  __METHOD__ );
        }    

        $db_word_1 = lib::create( 'database\word', $a_obj->word_id );
        $db_word_2 = lib::create( 'database\word', $b_obj->word_id );

        $word_candidate_1 = is_null( $a_obj->word_candidate ) ? '' : $a_obj->word_candidate;
        $word_candidate_2 = is_null( $b_obj->word_candidate ) ? '' : $b_obj->word_candidate;
        $selection_1 = is_null( $a_obj->selection ) ? '' : $a_obj->selection;
        $selection_2 = is_null( $b_obj->selection ) ? '' : $b_obj->selection;
        $classification_1 = '';
        $classification_2 = '';
        if( !empty( $word_candidate_1 ) && $selection_1 == 'variant' )
        {
          $data = $db_test->get_word_classification( $word_candidate_1, $language );
          $classification_1 = $data['classification'];
        }
        if( !empty( $word_candidate_2 ) && $selection_2 == 'variant' )
        {
          $data = $db_test->get_word_classification( $word_candidate_2, $language );
          $classification_2 = $data['classification'];
        }

        $entry_data[] = array(
           'id_1' => $a_obj->id,
           'id_2' => $b_obj->id,
           'selection_1' => $selection_1,
           'selection_2' => $selection_2,                  
           'word_id_1' => $db_word_1->id,
           'word_1' => $db_word_1->word,
           'word_id_2' => $db_word_2->id,
           'word_2' => $db_word_2->word,
           'word_candidate_1' => $word_candidate_1,
           'word_candidate_2' => $word_candidate_2,
           'classification_1' => $classification_1,
           'classification_2' => $classification_2 );
      }   
      next( $a );
      next( $b );
    }

    $intrusion_mod = lib::create( 'database\modifier' );
    $intrusion_mod->where( 'selection', '=', '' );
    $intrusion_mod->where( 'word_candidate', '!=', '' );
    $intrusion_mod->order( 'id' );
    $a = $db_test_entry->get_test_entry_ranked_word_list( clone $intrusion_mod );
    $b = $db_test_entry_adjudicate->get_test_entry_ranked_word_list( clone $intrusion_mod );
    
    while( !is_null( key( $a ) ) && !is_null( key ( $b ) ) ) 
    {   
      $a_obj = current( $a );
      $b_obj = current( $b );
      if( $a_obj->word_candidate != $b_obj->word_candidate )
      {        
        $word_candidate_1 = is_null( $a_obj->word_candidate ) ? '' : $a_obj->word_candidate;
        $word_candidate_2 = is_null( $b_obj->word_candidate ) ? '' : $b_obj->word_candidate;
        $classification_1 = '';
        $classification_2 = '';
        if( !empty( $word_candidate_1 ) )
        {
          $data = $db_test->get_word_classification( $word_candidate_1, $language );
          $classification_1 = $data['classification'];
        }
        if( !empty( $word_candidate_2 ) )
        {
          $data = $db_test->get_word_classification( $word_candidate_2, $language );
          $classification_2 = $data['classification'];
        }
        $entry_data[] = array(
           'id_1' => $a_obj->id,
           'id_2' => $b_obj->id,
           'selection_1' => '',
           'selection_2' => '',                  
           'word_id_1' => '',
           'word_1' => '',
           'word_id_2' => '',
           'word_2' => '',
           'word_candidate_1' => $word_candidate_1,
           'word_candidate_2' => $word_candidate_2,
           'classification_1' => $classification_1,
           'classification_2' => $classification_2 );
      }   
      next( $a );
      next( $b );
    }
    $this->set_variable( 'entry_data', $entry_data );
  }
}
