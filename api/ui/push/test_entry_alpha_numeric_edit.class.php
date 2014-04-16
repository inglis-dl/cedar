<?php
/**
 * test_entry_alpha_numeric_edit.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: test_entry_alpha_numeric edit
 *
 * Edit a alpha_numeric test entry.
 */
class test_entry_alpha_numeric_edit extends \cenozo\ui\push\base_edit
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry_alpha_numeric', $args );
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
      $class_name = lib::get_class_name( 'database\test_entry_alpha_numeric' );
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
   * Validate the operation.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    $columns = $this->get_argument( 'columns' );

    if( array_key_exists( 'word_candidate', $columns ) ) 
    {   
      if( !preg_match( '/^(0|[1-9][0-9]*)$/', $columns['word_candidate'] ) &&
          !preg_match( '/^\pL$/', $columns['word_candidate'] ) )
        throw lib::create( 'exception\notice',
          'The word "'. $columns['word_candidate'] . '" must be a letter or a number.',
          __METHOD__ );
    }
  }

  /** 
   * This method executes the operation's purpose.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $word_class_name = lib::get_class_name( 'database\word' );

    $db_test_entry_alpha_numeric = $this->get_record();
    $db_test_entry = $db_test_entry_alpha_numeric->get_test_entry();    
    $db_dictionary = $db_test_entry->get_test()->get_dictionary();

    $language = $db_test_entry->get_assignment()->get_participant()->language;
    $language = is_null( $language ) ? 'en' : $language;
    $columns = $this->get_argument( 'columns' );
    $word_candidate = $columns['word_candidate'];

    // does the word candidate exist in the primary dictionary?
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'dictionary_id', '=', $db_dictionary->id );
    $modifier->where( 'language', '=', $language );
    $modifier->where( 'word', '=', $word_candidate );
    $modifier->limit( 1 );

    $db_word = current( $word_class_name::select( $modifier ) );
    if( false !== $db_word )
    {
      $db_test_entry_alpha_numeric->word_id = $db_word->id;
    }
    else
    {
      $db_new_word = lib::create( 'database\word' );
      $db_new_word->dictionary_id = $db_dictionary->id;
      $db_new_word->word = $word_candidate;
      $db_new_word->language = $language;
      $db_new_word->save();
      $db_test_entry_alpha_numeric->word_id = $word_class_name::db()->insert_id();
    }

    $db_test_entry_alpha_numeric->save();

    // consider the test entry completed if 1 or more entries exist and
    // the entry is not deferred
    // if none exist, the typist must defer to the admin to set completed status
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'word_id', '!=', NULL );
    $test_entry_alpha_numeric_class_name = 
      lib::get_class_name('database\test_entry_alpha_numeric');
    $completed = 0 < $test_entry_alpha_numeric_class_name::count( $modifier );
    $db_test_entry->update_status_fields( $completed );
  }
}
