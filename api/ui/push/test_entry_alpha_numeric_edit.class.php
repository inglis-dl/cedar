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
    parent::prepare();

    $columns = $this->get_argument( 'columns', array() );

    // check to see if site_value is in the column list
    if( array_key_exists( 'word_value', $columns ) ) 
    {   
      $this->word_value = $columns['word_value'];
      unset( $this->arguments['columns']['word_value'] );
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

    $db_test_entry_alpha_numeric = $this->get_record();
    $db_test_entry = $db_test_entry_alpha_numeric->get_test_entry();    
    $db_dictionary = $db_test_entry->get_test()->get_dictionary();

    $language = $db_test_entry->get_assignment()->get_participant()->language;
    $language = is_null( $language ) ? 'en' : $language;

    // does the word candidate exist in the primary dictionary?
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'dictionary_id', '=', $db_dictionary->id );
    $modifier->where( 'language', '=', $language );
    $modifier->where( 'word', '=', $this->word_value );
    $modifier->limit( 1 );
    $word_class_name = lib::get_class_name( 'database\word' );
    $db_word = $word_class_name::select( $modifier );
    if( !empty( $db_word ) )
    {
      $db_test_entry_alpha_numeric->word_id = $db_word[0]->id;
      $db_test_entry_alpha_numeric->save();
    }

    // consider the test entry completed if 1 or more entries exist and
    // the entry is not deferred
    // if none exist, the typist must defer to the admin to set completed status
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'word_id', '!=', '' );
    $test_entry_alpha_numeric_class_name = 
      lib::get_class_name('database\test_entry_alpha_numeric');
    $completed = $test_entry_alpha_numeric_class_name::count( $modifier ) > 0 ? 1 : 0;
    $db_test_entry->update_status_fields( $completed );
  }

  /**
   * If a word_value is being set this member holds its new value.
   * @var string $word
   * @access protected
   */
  protected $word_value = NULL;
}
