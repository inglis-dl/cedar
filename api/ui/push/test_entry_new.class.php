<?php
/**
 * test_entry_new.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: test_entry new
 *
 * Create a new test entry.
 */
class test_entry_new extends \cenozo\ui\push\base_new
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

    $columns = $this->get_argument( 'columns', array() );
    $record = $this->get_record();

    $db_participant = NULL;
    $db_test_entry_1 = NULL;
    $db_test_entry_2 = NULL;

    $adjudicate = ( is_null( $record->assignment_id ) && !is_null( $record->test_id ) );
    if( $adjudicate )
    {
      $db_participant = $record->get_participant();
    }
    else
    {
      $db_participant = $record->get_assignment()->get_participant();
    }

    if( is_null( $db_participant ) ) 
      throw lib::create( 'exception\runtime',
        'Test entry requires a valid participant', __METHOD__ );

    $language = $db_participant->language;
    $language = is_null( $language ) ? 'en' : $language;

    // create default test_entry sub tables
    $db_test = $record->get_test();
    $test_type_name = $db_test->get_test_type()->name;
    
    if( $test_type_name == 'ranked_word' )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->order( 'rank' );

      foreach( $db_test->get_ranked_word_set_list( $modifier )
        as $db_ranked_word_set )
      {
        // get the word in the participant's language
        $word_id = 'word_' . $language . '_id';
        $args = array();
        $args['columns']['test_entry_id'] = $record->id;
        $args['columns']['word_id'] = $db_ranked_word_set->$word_id;
        $operation = lib::create( 'ui\push\test_entry_ranked_word_new', $args );
        $operation->process();
      }

      if( $adjudicate )
      {
        $db_test_entry_1 = lib::create( 'database\test_entry', $columns['id_1'] );
        $db_test_entry_2 = lib::create( 'database\test_entry', $columns['id_2'] );

        $a = $db_test_entry_1->get_test_entry_ranked_word_list();
        $b = $db_test_entry_2->get_test_entry_ranked_word_list();
        $c = $record->get_test_entry_ranked_word_list();

        $count = max( array( count( $a ), count( $b ) ) ) - count( $c );

        if( 0 < $count )
        {
          $args = array();
          $args['columns']['test_entry_id'] = $record->id;
          for( $i = 0; $i < $count; $i++ )
          {
            $operation = lib::create( 'ui\push\test_entry_ranked_word_new', $args );
            $operation->process();
          }
        }

        $c = $record->get_test_entry_ranked_word_list();
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

        $data = $columns['data'];
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
    }
    else if( $test_type_name == 'confirmation' )
    {
      $args = array();
      $args['columns']['test_entry_id'] = $record->id;

      if( $adjudicate )
      {
        $data = $columns['data'];
        if( !array_key_exists( 'confirmation', $data ) )
          throw lib::create( 'exception\runtime',
            'Test entry adjudication requires a valid confirmation', __METHOD__ );

        $args['columns']['confirmation'] = $data['confirmation'];  
      }

      $operation = lib::create( 'ui\push\test_entry_confirmation_new', $args );
      $operation->process();

      if( $adjudicate )
      {
        $db_test_entry_1 = lib::create( 'database\test_entry', $columns['id_1'] );
        $db_test_entry_2 = lib::create( 'database\test_entry', $columns['id_2'] );
      }
    }
    else if( $test_type_name == 'classification' )
    {
      $setting_manager = lib::create( 'business\setting_manager' );
      $max_rank = $setting_manager->get_setting( 'interface', 'classification_max_rank' );

      for( $rank = 1; $rank <= $max_rank; $rank++ )
      {
        $args = array();
        $args['columns']['test_entry_id'] = $record->id;
        $args['columns']['rank'] = $rank;
        $operation = lib::create( 'ui\push\test_entry_classification_new', $args );
        $operation->process();
      }

      if( $adjudicate )
      {
        // get the two records that required adjudication
        $db_test_entry_1 = lib::create( 'database\test_entry', $columns['id_1'] );
        $db_test_entry_2 = lib::create( 'database\test_entry', $columns['id_2'] );

        $modifier = lib::create('database\modifier');
        $modifier->order( 'rank' );

        $a = $db_test_entry_1->get_test_entry_classification_list( clone $modifier );
        $b = $db_test_entry_2->get_test_entry_classification_list( clone $modifier );

        // the new record created for the adjudication
        $c = $record->get_test_entry_classification_list( clone $modifier );

        // ensure the adjudication record can hold the maximum required entries
        $count = max( array( count( $a ), count( $b ) ) ) - count( $c );
        if( 0 < $count )
        {
          $args = array();
          $args['columns']['test_entry_id'] = $record->id;
          for( $i = 0; $i < $count; $i++ )
          {
            $operation = lib::create( 'ui\push\test_entry_classification_new', $args );
            $operation->process();
          }
        }

        // loop over the records and identify differences that required adjudication
        $c = $record->get_test_entry_classification_list( clone $modifier );
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
        $data = $columns['data'];
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
              $db_word = $word_class_name::select( $modifier );
              if( !empty( $db_word ) ) 
              {   
                $db_entry->word_id = $db_word[0]->id;
                $db_entry->word_candidate = NULL;
              }
              else $db_entry->word_id = NULL;
            }
            $db_entry->save();
          }
        }
      }
    }
    else if( $test_type_name == 'alpha_numeric' )
    {
      // Alpha numeric MAT alternation test has a dictionary of a-z and initially
      // a minimal number set containing 1-20.
      // Create empty entry fields for the maximum possible number of entries.
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'language', '=', $language );
      $word_count = $db_test->get_dictionary()->get_word_count( $modifier );
      for( $rank = 1; $rank <= $word_count; $rank++ )
      {
        $args = array();
        $args['columns']['test_entry_id'] = $record->id;
        $args['columns']['rank'] = $rank;
        $operation = lib::create( 'ui\push\test_entry_alpha_numeric_new', $args );
        $operation->process();
      }
      if( $adjudicate )
      {
        $db_test_entry_1 = lib::create( 'database\test_entry', $columns['id_1'] );
        $db_test_entry_2 = lib::create( 'database\test_entry', $columns['id_2'] );

        $modifier = lib::create('database\modifier');
        $modifier->order( 'rank' );

        $a = $db_test_entry_1->get_test_entry_classification_list( clone $modifier );
        $b = $db_test_entry_2->get_test_entry_classification_list( clone $modifier );
        $c = $record->get_test_entry_classification_list( clone $modifier );

        $count = max( array( count( $a ), count( $b ) ) ) - count( $c );

        if( 0 < $count )
        {
          $args = array();
          $args['columns']['test_entry_id'] = $record->id;
          for( $i = 0; $i < $count; $i++ )
          {
            $operation = lib::create( 'ui\push\test_entry_alpha_numeric_new', $args );
            $operation->process();
          }
        }

        $c = $record->get_test_entry_alpha_numeric_list( clone $modifier );
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

        $data = $columns['data'];
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
              $db_word = $word_class_name::select( $modifier );
              if( !empty( $db_word ) ) 
              {   
                $db_entry->word_id = $db_word[0]->id;
              }     
              else $db_entry->word_id = NULL;
            }     
            $db_entry->save();
          }     
        }
      }
    }
    else
    {
      throw lib::create( 'exception\runtime',
        'Test entry requires a valid test type, not ' .
        $test_type_name, __METHOD__ );
    }

    if( $adjudicate )
    {
      $db_test_entry_1->adjudicate = 0;
      $db_test_entry_2->adjudicate = 0;
      $db_test_entry_1->save();
      $db_test_entry_2->save();
      $record->completed = 1;
      $record->save();
    }
  }
}
