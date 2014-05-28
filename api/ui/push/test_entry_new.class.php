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

    $columns = $this->get_argument( 'columns', array() );
    $record = $this->get_record();

    $db_participant = NULL;
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

        if( !array_key_exists( 'data', $columns ) &&
            !array_key_exists( 'intrusion_data', $columns ) )
          throw lib::create( 'exception\runtime',
            'Test entry adjudication requires data', __METHOD__ );

        reset( $c );
        if( array_key_exists( 'data', $columns ) )
        {
          $data = $columns['data'];
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

        if( array_key_exists( 'intrusion_data', $columns ) )
        {
          $intrusion_data = $columns['intrusion_data'];
          if( 0 < count( $intrusion_data ) )
          {
            $rank = 1;
            $intrusion_modifier = lib::create( 'database\modifier' );
            $intrusion_modifier->where( 'selection', '=', NULL );
            $c = $record->get_test_entry_ranked_word_list( $intrusion_modifier );
            foreach( $c as $db_entry )
            {
              if( is_null( $db_entry->word_id ) && is_null( $db_entry->word_candidate ) &&
                  array_key_exists( $rank, $intrusion_data ) )
              {
                $word_candidate = $intrusion_data[ $rank ];
                $db_entry->word_candidate = $word_candidate;
                $db_entry->save();
              }
              $rank = $rank + 1;
            }
          }
        }

        $db_test_entry_1->adjudicate = 0;
        $db_test_entry_2->adjudicate = 0;
        $db_test_entry_1->save();
        $db_test_entry_2->save();
        $record->completed = 1;
        $record->save();
      }
    }
    else if( $test_type_name == 'confirmation' )
    {
      $args = array();
      $args['columns']['test_entry_id'] = $record->id;

      if( $adjudicate )
      {
        if( !array_key_exists( 'data', $columns ) )
          throw lib::create( 'exception\runtime',
            'Test entry adjudication requires data', __METHOD__ );

        $data = $columns['data'];
        $args['columns']['confirmation'] = $data;
      }

      $operation = lib::create( 'ui\push\test_entry_confirmation_new', $args );
      $operation->process();

      if( $adjudicate )
      {
        $db_test_entry_1 = lib::create( 'database\test_entry', $columns['id_1'] );
        $db_test_entry_2 = lib::create( 'database\test_entry', $columns['id_2'] );
        $db_test_entry_1->adjudicate = 0;
        $db_test_entry_2->adjudicate = 0;
        $db_test_entry_1->save();
        $db_test_entry_2->save();
        $record->completed = 1;
        $record->save();
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
        if( !array_key_exists( 'data', $columns ) )
          throw lib::create( 'exception\runtime',
            'Test entry adjudication requires data', __METHOD__ );

        reset( $c );
        $data = $columns['data'];
        foreach( $c as $db_entry )
        {
          if( array_key_exists( $db_entry->rank, $data ) )
          {
            $word_candidate = $data[$db_entry->rank]['word_candidate'];
            $word_data = $db_test->get_word_classification( $word_candidate, $language );
            if( !is_null( $word_data['word'] ) )
            {
              $db_entry->word_id = $word_data['word']->id;
              $db_entry->word_candidate = NULL;
            }
            else
            {
              $db_entry->word_id = NULL;
              $db_entry->word_candidate = $word_candidate;
            }
            $db_entry->save();
          }
        }

        $db_test_entry_1->adjudicate = 0;
        $db_test_entry_2->adjudicate = 0;
        $db_test_entry_1->save();
        $db_test_entry_2->save();
        $record->completed = 1;
        $record->save();
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

        if( !array_key_exists( 'data', $columns ) )
          throw lib::create( 'exception\runtime',
            'Test entry adjudication requires data', __METHOD__ );

        reset( $c );
        $data = $columns['data'];
        foreach( $c as $db_entry )
        {
          if( array_key_exists( $db_entry->rank, $data ) )
          {
            $word_candidate = $data[$db_entry->rank]['word_candidate'];
            $word_data = $db_test->get_word_classification( $word_candidate, $language );
            if( !is_null( $word_data['word'] ) )
            {
              $db_entry->word_id = $word_data['word']->id;
            }
            else
            {
              $db_entry->word_id = NULL;
            }
            $db_entry->save();
          }
        }
        $db_test_entry_1->adjudicate = 0;
        $db_test_entry_2->adjudicate = 0;
        $db_test_entry_1->save();
        $db_test_entry_2->save();
        $record->completed = 1;
        $record->save();
      }
    }
    else
    {
      throw lib::create( 'exception\runtime',
        'Test entry requires a valid test type, not ' .
        $test_type_name, __METHOD__ );
    }
  }
}
