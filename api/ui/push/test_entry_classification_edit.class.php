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
   * @throws exception\runtime
   */
  protected function execute()
  {
    parent::execute();

    $record = $this->get_record();
    $db_test_entry = $record->get_test_entry();
    $db_test = $db_test_entry->get_test();

    // note that for adjudication entries, there is no assignment and such
    // entries cannot be edited
    $db_assignment = $db_test_entry->get_assignment();
    if( is_null( $db_assignment ) )
      throw lib::create( 'exception\runtime',
        'Tried to edit an adjudication entry', __METHOD__ );

    // allow bilingual responses for FAS classification tests
    $language = 'any';
    if( !preg_match( '/FAS/', $db_test->name ) )
    {
      $language = $db_assignment->get_participant()->language;
      $language = is_null( $language ) ? 'en' : $language;
    }  
    
    $data = $db_test->get_word_classification( $record->word_candidate, $language );
    $db_word = $data['word'];

    // consider the test entry completed if 1 or more entries exist
    // if none exist, the typist must defer to the admin to set completed status
    $completed = false;
    if( $db_word !== NULL )
    {
      $record->word_id = $db_word->id;
      $record->word_candidate = NULL;
      $record->save();
      $completed = true;
    }
    else
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'word_id', '!=', '' );
      $modifier->where( 'word_candidate', '!=', '', true, true );
      $test_entry_classification_class_name = 
        lib::get_class_name('database\test_entry_classification');
      $completed = $test_entry_classification_class_name::count( $modifier ) > 0 ? true : false;
    }

    $db_test_entry->update_status_fields( $completed );
  }
}
