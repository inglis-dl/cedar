<?php
/**
 * test_entry_classification_edit.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: test_entry_classification edit
 *
 * Edit a classification test entry.
 */
class test_entry_classification_edit extends \cenozo\ui\push\base_edit
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry_classification', $args );
  }

  /** 
   * Processes arguments, preparing them for the operation.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    // if the id argument is absent, create a new entry for the data
    $id = $this->get_argument( 'id' );
    if( !isset( $id ) || $id === '' )
    {
      // skip the parent method
      $grand_parent = get_parent_class( get_parent_class( get_class() ) );
      $grand_parent::prepare(); 
      $columns = $this->get_argument( 'columns' );      
      $class_name = lib::get_class_name( 'database\test_entry_classification' );
      $this->set_record( $class_name::get_unique_record( 
        array( 'test_entry_id', 'rank' ),  
        array( $columns['test_entry_id'], $columns['rank'] ) ) );
    }      
    else
    {
      parent::prepare();
    }
  }

  /** 
   * This method executes the operation's purpose.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   * @throws exception\runtime
   */
  protected function execute()
  {
    parent::execute();

    $word_class_name = lib::get_class_name( 'database\word' );

    $db_test_entry_classification = $this->get_record();
    $db_test_entry = $db_test_entry_classification->get_test_entry();
    $db_test = $db_test_entry->get_test();

    // allow bilingual responses for FAS classification tests
    $language = 'any';
    $is_FAS = preg_match( '/FAS/', $db_test->name );
    if( !$is_FAS )
    {
      $db_assignment = $db_test_entry->get_assignment();
      if( is_null( $db_assignment ) )
        $language = $db_test_entry->get_participant()->language;
      else
        $language = $db_assignment->get_participant()->language;      
      $language = is_null( $language ) ? 'en' : $language;
    }

    $columns = $this->get_argument( 'columns' );
    $word_candidate = array_key_exists( 'word_candidate', $columns ) ?
      $columns['word_candidate'] : NULL;
    
    $data = $db_test->get_word_classification( $word_candidate, $language );
    $classification = $data['classification'];
    $db_word = $data['word'];

    if( !is_null( $db_word ) )
    {
      $db_test_entry_classification->word_id = $db_word->id;
    }
    else
    {
      // the word isnt in the primary, variant or intrusion dictionaries
      $db_dictionary = NULL;

      if( $is_FAS )
      {
        $is_intrusion = false;
        // any words that begin with 'f' or 'ph' are intrusions
        if( preg_match( '/FAS (f words)/', $db_test->name ) &&
            !( false === strpos( 'f', $word_candidate ) || 
               false === strpos( 'ph', $word_candidate ) ) )
        {
          $is_intrusion = true;
        }
        // any words that begin with 'a' are intrusions
        else if( preg_match( '/FAS (a words)/', $db_test->name ) &&
                 !( false === strpos( 'a', $word_candidate ) ) )
        {
          $is_intrusion = true;
        }
        // any words begin with 's' or 'c' are intrusions
        else if( preg_match( '/FAS (s words)/', $db_test->name ) &&
                 !( false === strpos( 's', $word_candidate ) || 
                    false === strpos( 'c', $word_candidate ) ) )
        {
          $is_intrusion = true;
        }          
        if( $is_intrusion )
        { 
          //get the test's intrusion dictionary and add it as an intrusion
          $db_dictionary = $db_test->get_intrusion_dictionary();
          if( is_null( $db_dictionary ) ) 
            throw lib::create( 'exception\notice',
              'Trying to add the word "'. $word_candidate .
              '" to a non-existant intrusion dictionary.  Assign an intrusion dictionary for the '.
              $db_test->name . ' test.', __METHOD__ );
        }  
      }

      if( is_null( $db_dictionary ) )
      {
        //get the test's variant dictionary and add it as a variant
        $db_dictionary = $db_test->get_variant_dictionary();
        if( is_null( $db_dictionary ) ) 
          throw lib::create( 'exception\notice',
            'Trying to add the word "'. $word_candidate .
            '" to a non-existant variant dictionary.  Assign a variant dictionary for the '.
            $db_test->name . ' test.', __METHOD__ );
      }
      $db_new_word = lib::create( 'database\word' );
      $db_new_word->dictionary_id = $db_dictionary->id;
      $db_new_word->word = $word_candidate;
      $db_new_word->language = $language;
      $db_new_word->save();
      $db_test_entry_classification->word_id = $word_class_name::db()->insert_id();
    }

    $db_test_entry_classification->save();

    $assignment_manager = lib::create( 'business\assignment_manager' );
    $assignment_manager::complete_test_entry( $db_test_entry );
  }
}
