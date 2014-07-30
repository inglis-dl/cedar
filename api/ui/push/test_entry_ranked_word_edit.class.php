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

    $session = lib::create( 'business\session' );
    $word_class_name = lib::get_class_name( 'database\word' );

    $db_test_entry_ranked_word = $this->get_record();
    $db_test_entry = $db_test_entry_ranked_word->get_test_entry();

    $columns = $this->get_argument( 'columns' );
    $word_candidate =
      array_key_exists( 'word_candidate', $columns ) && $columns['word_candidate'] !== '' ?
      $columns['word_candidate'] : NULL;

    $session->acquire_semaphore();
    if( !is_null( $word_candidate ) )
    {
      $db_test = $db_test_entry->get_test();
      $db_language = NULL;
      $db_assignment = $db_test_entry->get_assignment();
      if( is_null( $db_assignment ) )
        $db_language = $db_test_entry->get_participant()->get_language();
      else
        $db_language = $db_assignment->get_participant()->get_language();

      if( is_null( $db_language ) )
      {
        $session = lib::create( 'business\session' );
        $db_language = $session->get_service()->get_language();
      }

      $data = $db_test->get_word_classification( $word_candidate, $db_language );
      $classification = $data['classification'];
      $db_word = $data['word'];

      // check if mispelled and throw an exception
      if( $classification == 'mispelled' )
        throw lib::create( 'exception\notice',
          'The word "'. $db_word->word . '" is a mispelled word and cannot be accepted.',
          __METHOD__ );

      if( $db_test_entry_ranked_word->selection == 'variant' )
      {
        if( $classification != 'variant' )
        {
          throw lib::create( 'exception\notice',
            'The word "' . $word_candidate . '" is not one of the '.
            'accepted variant words: add as an intrusion instead.',
             __METHOD__ );
        }
        else
          $db_test_entry_ranked_word->word_id = $db_word->id;
      }
      // NULL selection implies test_entry was created for an intrusion
      else if( is_null( $db_test_entry_ranked_word->selection ) )
      {
        // reject words that are in the primary or variant dictionaries
        if( $classification == 'primary' ||
            $classification == 'variant' )
        {
          throw lib::create( 'exception\notice',
            'The word "' . $word_candidate . '" is one of the '.
            $classification . ' words and cannot be entered as an intrusion.',
            __METHOD__ );
        }
        // not a primary or variant
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
            // is this a valid word?
            if( !$word_class_name::is_valid_word( $word_candidate ) )
              throw lib::create( 'exception\notice',
                '"'. $word_candidate . '" is not a valid intrusion word entry.', __METHOD__ );

            $db_new_word = lib::create( 'database\word' );
            $db_new_word->dictionary_id = $db_dictionary->id;
            $db_new_word->word = $word_candidate;
            $db_new_word->language_id = $db_language->id;
            $db_new_word->save();
            $db_test_entry_ranked_word->word_id = $word_class_name::db()->insert_id();
          }
        }
        // it is an existing intrusion
        else
        {
          $db_test_entry_ranked_word->word_id = $db_word->id;
        }
      }

      $db_test_entry_ranked_word->save();
    }
    $session->release_semaphore();

    $assignment_manager = lib::create( 'business\assignment_manager' );
    $assignment_manager::complete_test_entry( $db_test_entry );
  }
}
