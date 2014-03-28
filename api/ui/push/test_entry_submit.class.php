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

    $db_test_entry = $this->get_record();
    $assignment_manager = lib::create( 'business\assignment_manager' );
    $assignment_manager::initialize_test_entry( $db_test_entry );

    $word_class_name = lib::get_class_name( 'database\word' );

    $columns = $this->get_argument( 'columns', array() );

    $db_test_entry_1 = lib::create( 'database\test_entry', $columns['id_1'] );
    $db_test_entry_2 = lib::create( 'database\test_entry', $columns['id_2'] );
    $db_participant = $db_test_entry->get_participant();

    if( is_null( $db_participant ) ) 
      throw lib::create( 'exception\runtime',
        'Test entry requires a valid participant', __METHOD__ );

    $language = $db_participant->language;
    $language = is_null( $language ) ? 'en' : $language;

    // create default test_entry sub tables
    $db_test = $db_test_entry->get_test();
    $test_type_name = $db_test->get_test_type()->name;
    $entry_class_name = 'test_entry_' . $test_type_name;

    $data = $columns['data'];
    
    if( $test_type_name == 'ranked_word' )
    {
      $a = $db_test_entry_1->get_test_entry_ranked_word_list();
      $b = $db_test_entry_2->get_test_entry_ranked_word_list();
      $c = $db_test_entry->get_test_entry_ranked_word_list();

      $count = max( array( count( $a ), count( $b ) ) ) - count( $c );

      for( $i = 0; $i < $count; $i++ )
      {
        $db_test_entry_ranked_word = lib::create( 'database\\' . $entry_class_name );
        $db_test_entry_ranked_word->test_entry_id = $db_test_entry->id;
        $db_test_entry_ranked_word->save();
      }

      $c = $db_test_entry->get_test_entry_ranked_word_list();
      while( !is_null( key( $a ) ) && !is_null( key ( $b ) ) && !is_null( ( key ( $c ) ) ) )
      {
        $a_obj = current( $a );
        $b_obj = current( $b );
        $c_obj = current( $c );
        if( $a_obj->selection == $b_obj->selection &&
            $a_obj->word_id == $b_obj->word_id &&
            $a_obj->word_candidate == $b_obj->word_candidate )
        {
          $c_obj->selection = $a_obj->selection;
          $c_obj->word_id = $a_obj->word_id;
          $c_obj->word_candidate = $a_obj->word_candidate;
          $c_obj->save();
        }
        next( $a );
        next( $b );
        next( $c );
      }

      reset( $c );
      foreach( $c as $db_entry )
      {
        if( array_key_exists( $db_entry->word_id, $data ) )
        {
          $db_entry->selection = $data[$db_entry->word_id]['selection'];
          if( !is_null( $db_entry->selection ) && $db_entry->selection == 'variant' )
            $db_entry->word_candidate = $data[$db_entry->word_id]['word_candidate'];
          $db_entry->save();
        }
      }
    }
    else if( $test_type_name == 'confirmation' )
    {
      $db_entry = current( $db_test_entry->get_test_entry_confirmation_list() );
      $db_entry->confirmation = $data['confirmation'];
      $db_entry->save();
    }
    else if( $test_type_name == 'classification' )
    {
      $modifier = lib::create('database\modifier');
      $modifier->order( 'rank' );

      $a = $db_test_entry_1->get_test_entry_classification_list( clone $modifier );
      $b = $db_test_entry_2->get_test_entry_classification_list( clone $modifier );

      // the new record created for the adjudication
      $c = $db_test_entry->get_test_entry_classification_list( clone $modifier );

      // ensure the adjudication record can hold the maximum required entries
      $count = max( array( count( $a ), count( $b ) ) ) - count( $c );
      for( $i = 0; $i < $count; $i++ )
      {
        $db_test_entry_classification = lib::create( 'database\\' . $entry_class_name );
        $db_test_entry_classification->test_entry_id = $db_test_entry->id;
        $db_test_entry_classification->save();
      }

      // loop over the records and identify differences that required adjudication
      $c = $db_test_entry->get_test_entry_classification_list( clone $modifier );
      while( !is_null( key( $a ) ) && !is_null( key ( $b ) ) && !is_null( ( key ( $c ) ) ) )
      {
        $a_obj = current( $a );
        $b_obj = current( $b );
        $c_obj = current( $c );
        if( $a_obj->word_id == $b_obj->word_id &&
            $a_obj->word_candidate == $b_obj->word_candidate )
        {
          $c_obj->word_id = $a_obj->word_id;
          $c_obj->word_candidate = $a_obj->word_candidate;
          $c_obj->save();
        }
        next( $a );
        next( $b );
        next( $c );
      }

      // data is set in the test_entry_classification_adjudicate.twig file
      // during the submit
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
          $db_entry->word_candidate = $data[$db_entry->rank]['word_candidate'];
          $db_entry->word_id = $data[$db_entry->rank]['word_id'];
          if( $db_entry->word_id == 'candidate' )
          {
            // does the word candidate exist in the primary dictionary ?
            $modifier = clone $base_mod;
            $modifier->where( 'word', '=', $db_entry->word_candidate );
            $db_word = current( $word_class_name::select( $modifier ) );
            if( !is_null( $db_word ) ) 
            {   
              $db_entry->word_id = $db_word->id;
              $db_entry->word_candidate = NULL;
            }
            else $db_entry->word_id = NULL;
          }
          $db_entry->save();
        }
      }
    }
    else if( $test_type_name == 'alpha_numeric' )
    {
      $modifier = lib::create('database\modifier');
      $modifier->order( 'rank' );

      $a = $db_test_entry_1->get_test_entry_classification_list( clone $modifier );
      $b = $db_test_entry_2->get_test_entry_classification_list( clone $modifier );
      $c = $db_test_entry->get_test_entry_classification_list( clone $modifier );

      $count = max( array( count( $a ), count( $b ) ) ) - count( $c );
      for( $i = 0; $i < $count; $i++ )
      {
        $db_test_entry_alpha_numeric = lib::create( 'database\\' . $entry_class_name );
        $db_test_entry_alpha_numeric->test_entry_id = $db_test_entry->id;
        $db_test_entry_alpha_numeric->save();
      }

      $c = $db_test_entry->get_test_entry_alpha_numeric_list( clone $modifier );
      while( !is_null( key( $a ) ) && !is_null( key ( $b ) ) && !is_null( ( key ( $c ) ) ) )
      {
        $a_obj = current( $a );
        $b_obj = current( $b );
        $c_obj = current( $c );
        if( $a_obj->word_id == $b_obj->word_id )
        {
          $c_obj->word_id = $a_obj->word_id;
          $c_obj->save();
        }
        next( $a );
        next( $b );
        next( $c );
      }

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
          $word_candidate = $data[$db_entry->rank]['word_candidate'];
          $db_entry->word_id = $data[$db_entry->rank]['word_id'];
          if( $db_entry->word_id == 'candidate' )
          {   
            // does the word candidate exist in the primary dictionary ?
            $modifier = clone $base_mod;
            $modifier->where( 'word', '=', $word_candidate );
            $db_word = current( $word_class_name::select( $modifier ) );
            if( !is_null( $db_word ) ) 
            {   
              $db_entry->word_id = $db_word->id;
            }     
            else $db_entry->word_id = NULL;
          }     
          $db_entry->save();
        }     
      }
    }

    //TODO set the assigments' end_datetime
    $db_test_entry_1->adjudicate = false;
    $db_test_entry_2->adjudicate = false;
    $db_test_entry_1->save();
    $db_test_entry_2->save();
    $db_test_entry->completed = true;
    $db_test_entry->save();
  }
}
