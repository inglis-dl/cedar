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

    log::debug( $columns );

    // if the assignment id is null and the participant id is not null
    // this is an adjudication
    // otherwise,
    // if the assignment id is not null and the participant id is null 
    // this is a standard new

    $record = $this->get_record();

    $db_participant = NULL;
    $adjudicate = ( is_null( $record->assignment_id ) && !is_null( $record->test_id ) );
    log::debug( $adjudicate );
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
      }
    }
    else if( $test_type_name == 'confirmation' )
    {
      $args = array();
      $args['columns']['test_entry_id'] = $record->id;
      $operation = lib::create( 'ui\push\test_entry_confirmation_new', $args );
      $operation->process();
      if( $adjudicate )
      {
      }
    }
    else if( $test_type_name == 'classification' )
    {
      // create a default of 40 to start with
      for( $rank = 1; $rank < 41; $rank++ )
      {
        $args = array();
        $args['columns']['test_entry_id'] = $record->id;
        $args['columns']['rank'] = $rank;
        $operation = lib::create( 'ui\push\test_entry_classification_new', $args );
        $operation->process();
      }
      if( $adjudicate )
      {
      }
    }
    else if( $test_type_name == 'alpha_numeric' )
    {
      // Alpha numeric MAT alternation test has a dictionary of a-z and 1-20.
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
