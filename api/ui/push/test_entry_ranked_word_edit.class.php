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

     $db_test_entry = $this->get_record()->get_test_entry();
     $base_mod = lib::create( 'database\modifier' );
     $base_mod->where( 'test_entry_id', '=', $db_test_entry->id ); 
     $modifier = clone $base_mod;
     $modifier->where( 'selection', '=', '' );
     $test_entry_ranked_word_class_name = lib::get_class_name( 'database\test_entry_ranked_word' ); 
     $num_selected = $test_entry_ranked_word_class_name::count( $modifier );
     $modifier = clone $base_mod;
     $modifier->where( 'selection', '=', 'variant' );
     $modifier->where( 'word_candidate', '=', '' );
     $num_empty_variant = $test_entry_ranked_word_class_name::count( $modifier );
   
     $completed = $num_selected == 0 && $num_empty_variant == 0 ? 1 : 0;
     if( $db_test_entry->completed != $completed )
     {
       $db_test_entry->completed = $completed;
       $db_test_entry->save();
     }
  }
 
}
