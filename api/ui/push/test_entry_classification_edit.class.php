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
   * This method executes the operation's purpose.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $db_test_entry_classification = $this->get_record();
    $db_test_entry = $db_test_entry_classification->get_test_entry();
    $db_dictionary = $db_test_entry->get_test()->get_dictionary();

    $language = $db_test_entry->get_assignment()->get_participant()->language;
    $language = is_null( $language ) ? 'en' : $language;

    // does the word candidate exist in the primary dictionary ?
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'dictionary_id','=',$db_dictionary->id);
    $modifier->where( 'language','=',$language);
    $modifier->where( 'word','=',$db_test_entry_classification->word_candidate);
    $modifier->limit( 1 );
    $word_class_name = lib::get_class_name( 'database\word' );
    $db_word = $word_class_name::select( $modifier );
    if( !empty( $db_word ) )
    {
      $db_test_entry_classification->word_id = $db_word[0]->id;
      $db_test_entry_classification->word_candidate = NULL;
      $db_test_entry_classification->save();
    }

    // consider the test entry completed if 1 or more entries exist
    // if none exist, the typist must defer to the admin to set completed status

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'word_id', '!=', '' );
    $modifier->where( 'word_candidate', '!=', '', true, true );
    $test_entry_classification_class_name = 
      lib::get_class_name('database\test_entry_classification');
    $completed = $test_entry_classification_class_name::count( $modifier ) > 0 ? 1 : 0;
    $db_test_entry->update_status_fields( $completed );
  }
}
