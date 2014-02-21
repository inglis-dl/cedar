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
    $is_FAS = preg_match( '/FAS/', $db_test->name );
    if( !$is_FAS )
    {
      $language = $db_assignment->get_participant()->language;
      $language = is_null( $language ) ? 'en' : $language;
    }  
    
    $data = $db_test->get_word_classification( $record->word_candidate, $language );
    $classification = $data['classification'];
    $db_word = $data['word'];

    if( $db_word !== NULL && $classification != 'candidate' )
    {
      $record->word_id = $db_word->id;
      $record->word_candidate = NULL;
      $record->save();      
    }
    else
    {
      // the word isnt in the primary or intrusion dictionaries
      if( $is_FAS )
      {
        $is_intrusion = false;
        if( preg_match( '/FAS (f words)/', $db_test->name ) &&
            !( 0 === strpos( 'f', $record->word_candidate ) || 
               0 === strpos( 'ph', $record->word_candidate ) ) )
        {
          $is_intrusion = true;
        }
        else if( preg_match( '/FAS (a words)/', $db_test->name ) &&
                 !( 0 === strpos( 'a', $record->word_candidate ) ) )
        {
          $is_intrusion = true;
        }
        else if( preg_match( '/FAS (s words)/', $db_test->name ) &&
                 !( 0 === strpos( 's', $record->word_candidate ) || 
                    0 === strpos( 'c', $record->word_candidate ) ) )
        {
          $is_intrusion = true;
        }          
        if( $is_intrusion )
        { 
          //get the test's intrusion dictionary and add it as an intrusion
          $db_dictionary = $db_test->get_intrusion_dictionary();
          if( is_null( $db_dictionary ) ) 
          {   
            throw lib::create( 'exception\notice',
              'Trying to add the word "'.  $record->word_candidate . '" to a non-existant ' .
              ' intrusion dictionary.  Assign an intrusion dictionary for the ' . 
              $db_test->name . ' test.', __METHOD__ );
          }   
          else
          {   
            $db_new_word = lib::create( 'database\word' );
            $db_new_word->dictionary_id = $db_dictionary->id;
            $db_new_word->word = $record->word_candidate;
            $db_new_word->language = $language;
            $db_new_word->save();
            $record->word_id = static::db()->insert_id();
            $record->word_candidate = NULL;
            $record->save();
          }
        }  
      }
    }

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'word_id', '!=', '' );
    $modifier->where( 'word_candidate', '!=', '', true, true );
    $test_entry_classification_class_name = 
      lib::get_class_name('database\test_entry_classification');
    $completed = $test_entry_classification_class_name::count( $modifier ) > 0 ? true : false;
    

    $db_test_entry->update_status_fields( $completed );
  }
}
