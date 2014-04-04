<?php
/**
 * test_entry_ranked_word_edit.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: test_entry_ranked_word edit
 *
 * Edit a ranked word test entry.
 */
class test_entry_ranked_word_edit extends \cenozo\ui\push\base_edit
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry_ranked_word', $args );
  }

  /** 
   * This method executes the operation's purpose.
   * This type of test has two possible sources of edits from the UI layer:
   * 1) when a text entry field for a variant or intrusion changes
   * 2) when a selection changes
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $word_class_name = lib::get_class_name( 'database\word' );

    $db_test_entry_ranked_word = $this->get_record();
    $db_test_entry = $db_test_entry_ranked_word->get_test_entry();
    $db_test = $db_test_entry->get_test();

    $language = $db_test_entry->get_assignment()->get_participant()->language;
    $language = is_null( $language ) ? 'en' : $language;

    $columns = $this->get_argument( 'columns' );

    $word_candidate = array_key_exists( 'word_candidate', $columns ) ? 
      $columns['word_candidate'] : NULL;

    if( !is_null( $word_candidate ) )
    {
      $data = $db_test->get_word_classification( $word_candidate, $language );
      $classification = $data['classification'];  

      if( $db_test_entry_ranked_word->selection == 'variant' )
      {
        if( $classification != 'variant' )
        {
          throw lib::create( 'exception\notice',
            'The word "' . $word_candidate . '" is not one of the '.
            'accepted variant words: add as an intrusion instead.',
             __METHOD__ );
        }
      }
      // NULL selection implies test_entry was created for an intrusion
      else if( is_null( $db_test_entry_ranked_word->selection ) )
      {
        // reject words that are already in the primary or variant dictionaries
        if( $classification == 'primary' ||
            $classification == 'variant' )
        {    
          throw lib::create( 'exception\notice',
            'The word "' . $word_candidate . '" is one of the '.
            $classification . ' words and cannot be entered as an intrusion.',
            __METHOD__ );
        }
        // not a primary, variant, or intrusion
        else if( $classification == 'candidate' )
        {
          //get the test's intrusion dictionary and add it as an intrusion
          $db_dictionary = $db_test->get_intrusion_dictionary();
          if( is_null( $db_dictionary ) )
          {
            throw lib::create( 'exception\notice',
              'Trying to add the word "'.  $word_candidate .
              '" to a non-existant intrusion dictionary.  Assign an intrusion dictionary for the '.
              $db_test->name . ' test.', __METHOD__ );
          }
          else
          {
            $db_new_word = lib::create( 'database\word' );
            $db_new_word->dictionary_id = $db_dictionary->id;
            $db_new_word->word = $word_candidate;
            $db_new_word->language = $language;
            $db_new_word->save();
            $db_test_entry_ranked_word->word_id = $word_class_name::db()->insert_id();
          }
        }
      }

      $db_test_entry_ranked_word->save();
    }
    $assignment_manager = lib::create( 'business\assignment_manager' );
    $assignment_manager::complete_test_entry( $db_test_entry );
  }
}
