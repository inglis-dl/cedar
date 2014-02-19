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

    if( is_null( $record->get_word() ) )
    {
      // note that for adjudication entries, there is no assignment and such
      // entries cannot be edited
      $db_assignment = $db_test_entry->get_assignment();
      if( is_null( $db_assignment ) ) 
        throw lib::create( 'exception\runtime',
          'Tried to edit an adjudication entry', __METHOD__ );

      $language = $db_assignment->get_participant()->language;
      $language = is_null( $language ) ? 'en' : $language;

      $data = $db_test_entry->get_test()->get_word_classification( 
        $record->word_candidate, $language );
      $db_word = $data['word'];

      if( $db_word !== NULL )
      {   
        $record->word_id = $db_word->id;
        $record->word_candidate = NULL;
        $record->save();
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
