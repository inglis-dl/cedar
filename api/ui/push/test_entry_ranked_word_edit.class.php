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
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
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

    $language = $db_assignment->get_participant()->language;
    $language = is_null( $language ) ? 'en' : $language;

    if( !is_null( $record->word_candidate ) )
    {
      $data = $db_test->get_word_classification( 
        $record->word_candidate, $language );

      $classification = $data['classification'];  

      if( $record->selection == 'variant' )
      {
        if( $classification == 'primary' )
        {
          throw lib::create( 'exception\notice',
            'The word "' . $record->word_candidate . '" is one of the '.
            'primary words and cannot be added as a variant.',
             __METHOD__ );
         }    
      }
      else if( is_null( $record->selection ) )
      {
        if( $classification == 'primary' ||
            $classification == 'variant' )
        {    
          throw lib::create( 'exception\notice',
            'The word "' . $record->word_candidate . '" is one of the '.
            $classification . ' words and cannot be entered as an intrusion.',
            __METHOD__ );
        }
        else if( $classfication == 'candidate' )
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
          }
        }           
      }
    }

    $base_mod = lib::create( 'database\modifier' );
    $base_mod->where( 'test_entry_id', '=', $db_test_entry->id );

    $modifier = clone $base_mod;
    $modifier->where( 'selection', '=', '' );
    $test_entry_ranked_word_class_name = lib::get_class_name( 'database\test_entry_ranked_word' ); 
    $num_empty_selected = $test_entry_ranked_word_class_name::count( $modifier );

    $modifier = clone $base_mod;
    $modifier->where( 'selection', '=', 'variant' );
    $modifier->where( 'word_candidate', '=', '' );
    $num_empty_variant = $test_entry_ranked_word_class_name::count( $modifier );
   
    $completed = $num_empty_selected == 0 && $num_empty_variant == 0 ? 1 : 0;
    $db_test_entry->update_status_fields( $completed );
  }
}
