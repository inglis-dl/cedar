<?php
/**
 * test_entry_submit.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: test_entry new
 *
 * Create a new test entry from an adjudication.
 */
class test_entry_submit extends \cenozo\ui\push\base_new
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry', $args );
  }

  /** 
   * Finishes the operation with any post-execution instructions that may be necessary.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\runtime
   * @access protected
   */
  protected function finish() 
  {
    parent::finish();

    $word_class_name = lib::get_class_name( 'database\word' );

    $db_test_entry = $this->get_record();
    $assignment_manager = lib::create( 'business\assignment_manager' );
    $assignment_manager::initialize_test_entry( $db_test_entry );

    $columns = $this->get_argument( 'columns', array() );

    $db_test_entry_1 = lib::create( 'database\test_entry', $columns['id_1'] );
    $db_test_entry_2 = lib::create( 'database\test_entry', $columns['id_2'] );
    $db_participant = $db_test_entry->get_participant();

    if( is_null( $db_participant ) ) 
      throw lib::create( 'exception\runtime',
        'Test entry submitted for adjudication requires a valid participant', __METHOD__ );

    $language = $db_participant->language;
    $language = is_null( $language ) ? 'en' : $language;

    // create default test_entry sub tables
    $db_test = $db_test_entry->get_test();
    $test_type_name = $db_test->get_test_type()->name;
    $entry_class_name = 'test_entry_' . $test_type_name;
    $get_list_function = 'get_' . $entry_class_name . '_list';

    $data = $columns['data'];
    
    if( $test_type_name == 'ranked_word' )
    {
      $rank_modifier = lib::create( 'database\modifier' );
      $rank_modifier->order( 'ranked_word_set.rank' );
      $a = $db_test_entry_1->$get_list_function( clone $rank_modifier );
      $b = $db_test_entry_2->$get_list_function( clone $rank_modifier );
      
      // now get the intrusions and append them to the primary word entries
      $intrusion_modifier = lib::create( 'database\modifier' );
      $intrusion_modifier->where( 'selection', '=', NULL );
      $intrusion_modifier->where( 'word_id', '!=', NULL );
      $intrusion_modifier->where( 'ranked_word_set_id', '=', NULL );

      $a_intrusion = $db_test_entry_1->$get_list_function( clone $intrusion_modifier );
      $b_intrusion = $db_test_entry_2->$get_list_function( clone $intrusion_modifier );

      if( 0 < count( $a_intrusion ) ) 
        $a = array_merge( $a, $a_intrusion );
      if( 0 < count( $b_intrusion ) )
        $b = array_merge( $b, $b_intrusion ); 

      // create additional entries as needed
      $count = max( array( count( $a ), count( $b ) ) ) - 
                 count( $db_test_entry->$get_list_function() );

      for( $i = 0; $i < $count; $i++ )
      {
        $db_test_entry_ranked_word = lib::create( 'database\\' . $entry_class_name );
        $db_test_entry_ranked_word->test_entry_id = $db_test_entry->id;
        $db_test_entry_ranked_word->save();
      }

      $c = $db_test_entry->$get_list_function( clone $rank_modifier );
      $c_intrusion = $db_test_entry->$get_list_function( clone $intrusion_modifier );
      if( 0 < count( $c_intrusion ) ) 
        $c = array_merge( $c, $c_intrusion );

      // copy identical records
      while( !is_null( ( key ( $c ) ) ) )
      {
        $a_obj = current( $a );
        $b_obj = current( $b );
        $c_obj = current( $c );
        if( !( false === $a_obj || false === $b_obj ) )
        { 
          if( $a_obj->ranked_word_set_id == $b_obj->ranked_word_set_id &&
              $a_obj->selection == $b_obj->selection &&
              $a_obj->word_id == $b_obj->word_id )
          {
            $c_obj->ranked_word_set_id = $a_obj->ranked_word_set_id;
            $c_obj->selection = $a_obj->selection;
            $c_obj->word_id = $a_obj->word_id;
            $c_obj->save();
          }
        }
        next( $a );
        next( $b );
        next( $c );
      }

      reset( $c );
      foreach( $c as $db_entry )
      {
        if( array_key_exists( $db_entry->ranked_word_set_id, $data['ranked_words'] ) )
        {
          $db_entry->word_id = $data['ranked_words'][$db_entry->ranked_word_set_id]['word_id'];
          $db_entry->selection = $data['ranked_words'][$db_entry->ranked_word_set_id]['selection'];
          $db_entry->save();
        }
        // TODO: must handle the case when the word id is unknown
        // this will happen when the admin chooses a word different from both typists
        else if( array_key_exists( $db_entry->word_id, $data['intrusions'] ) )
        {
          $db_entry->word_id = $data['intrusions'][$db_entry->word_id];
          $db_entry->save();
        }
      }
    }
    else if( $test_type_name == 'confirmation' )
    {
      $db_entry = current( $db_test_entry->$get_list_function() );
      $db_entry->confirmation = $data['confirmation'];
      $db_entry->save();
    }
    else if( $test_type_name == 'classification' || $test_type_name == 'alpha_numeric' )
    {
      $modifier = lib::create('database\modifier');
      $modifier->order( 'rank' );

      $a = $db_test_entry_1->$get_list_function( clone $modifier );
      $b = $db_test_entry_2->$get_list_function( clone $modifier );

      // the new record created for the adjudication
      $c = $db_test_entry->$get_list_function( clone $modifier );

      // ensure the adjudication record can hold the maximum required entries
      $count = max( array( count( $a ), count( $b ) ) ) - count( $c );
      for( $i = 0; $i < $count; $i++ )
      {
        $db_test_entry_classification = lib::create( 'database\\' . $entry_class_name );
        $db_test_entry_classification->test_entry_id = $db_test_entry->id;
        $db_test_entry_classification->save();
      }

      // loop over the records and identify differences that required adjudication
      $c = $db_test_entry->$get_list_function( clone $modifier );
      while( !is_null( key( $a ) ) && !is_null( key ( $b ) ) && !is_null( ( key ( $c ) ) ) )
      {
        $a_obj = current( $a );
        $b_obj = current( $b );
        $c_obj = current( $c );
        if( $a_obj->word_id == $b_obj->word_id && !is_null( $a_obj->word_id ) )
        {
          $c_obj->word_id = $a_obj->word_id;
          $c_obj->save();
        }
        next( $a );
        next( $b );
        next( $c );
      }

      // data is set in the test_entry_classification_adjudicate.twig file
      
      $db_dictionary = $db_test->get_dictionary();
      $base_mod = lib::create( 'database\modifier' );
      $base_mod->where( 'dictionary_id', '=', $db_dictionary->id );
      $base_mod->where( 'language', '=', $language );
      $base_mod->limit( 1 );
      reset( $c );
      foreach( $c as $db_entry )
      {
        if( array_key_exists( $db_entry->rank, $data ) )
        {
          $db_entry->word_id = $data[$db_entry->rank]['word_id'];
          $db_entry->save();
        }
      }
    }

    $db_test_entry_1->adjudicate = false;
    $db_test_entry_2->adjudicate = false;
    $db_test_entry_1->save();
    $db_test_entry_2->save();
    
    $assignment_manager::complete_assignment( $db_test_entry_1->get_assignment() );

    $db_test_entry->completed = true;
    $db_test_entry->save();
  }
}
