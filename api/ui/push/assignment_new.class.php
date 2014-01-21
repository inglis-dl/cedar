<?php
/**
 * assignment_new.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: assignment new
 *
 * Create a new assignment.
 */
class assignment_new extends \cenozo\ui\push\base_new
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'assignment', $args );
  }

  /** 
   * Validate the operation.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    // make sure the name column isn't blank
    $columns = $this->get_argument( 'columns' );
    if( !array_key_exists( 'user_id', $columns ) || 0 == strlen( $columns['user_id'] ) ) 
      throw lib::create( 'exception\notice',
        'The user\'s name cannot be left blank.', __METHOD__ );
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

    $db_assignment = $this->get_record();
    $columns = $this->get_argument( 'columns' );
    $test_class_name = lib::get_class_name( 'database\test' );

    $modifier = NULL; 
    if( $columns['cohort_name'] == 'tracking' )
    {
      $modifier = lib::create('database\modifier');
      $modifier->where( 'name', 'not like', 'FAS%' );
    }  

    $language = $db_assignment->get_participant()->language;
    $language = is_null( $language ) ? 'en' : $language;
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );

    //TODO consider creating default sub items for test_entry objects
    // in the database api
    
    //creates a test entry automatically for each test
    foreach( $test_class_name::select( $modifier ) as $db_test )
    {
      $args = array();
      $args['columns']['test_id'] = $db_test->id;
      $args['columns']['assignment_id'] = $db_assignment->id;
      $operation = lib::create( 'ui\push\test_entry_new', $args );
      $operation->process();
      $test_entry_id = $test_entry_class_name::db()->insert_id();

      // create default test_entry sub tables
      $test_type_name = $db_test->get_test_type()->name;
      if( $test_type_name == 'ranked_word' )
      {
        $modifier = lib::create( 'database\modifier' );
        $modifier->order( 'rank' );
        $word_list = array();

        // Get the list of ranked words in order.
        // Create data for the rows in the transcribe widget's table.
        // If there is a corresponding db entry, populate data fields accordingly.
        foreach( $db_test->get_ranked_word_set_list( $modifier )
          as $db_ranked_word_set )
        {
          // Get the word in the participant's language.
          $word_id = 'word_' . $language . '_id';
          $args = array();
          $args['columns']['test_entry_id'] = $test_entry_id;
          //$args['columns']['selection'] = '';
          $args['columns']['word_id'] = $db_ranked_word_set->$word_id;
          $operation = lib::create( 'ui\push\test_entry_ranked_word_new', $args );
          $operation->process();             
        }
      }
      else if( $test_type_name == 'confirmation' )
      {
        $args = array();
        $args['columns']['test_entry_id'] = $test_entry_id;
        //$args['columns']['confirmation'] = 0;
        $operation = lib::create( 'ui\push\test_entry_confirmation_new', $args );
        $operation->process();
      }
      else if( $test_type_name == 'classification' )
      {
        // Create a default of 40 to start with.
        for( $rank = 1; $rank < 41; $rank++ )
        {
          $args = array();
          $args['columns']['test_entry_id'] = $test_entry_id;
          $args['columns']['rank'] = $rank;          
          $operation = lib::create( 'ui\push\test_entry_classification_new', $args );
          $operation->process();
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
          $args['columns']['test_entry_id'] = $test_entry_id;
          $args['columns']['rank'] = $rank;          
          $operation = lib::create( 'ui\push\test_entry_alpha_numeric_new', $args );
          $operation->process();
        }         
      }
      else
      {
        throw lib::create( 'exception\runtime',
          'Assignment requires a valid test type, not ' . 
          $test_type_name, __METHOD__ );
      }
    }
  }
}
