<?php
/**
 * test_entry_reset.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: test_entry reset
 *
 * Edit a test entry.
 */
class test_entry_reset extends \cenozo\ui\push\base_record
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry', 'reset', $args );
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

    $db_test_entry = $this->get_record();
    $test_type_name = $db_test_entry->get_test()->get_test_type()->name;
    if( $test_type_name == 'ranked_word' )
    {
      foreach( $db_test_entry->get_test_entry_ranked_word_list() 
        as $db_test_entry_ranked_word )
      {
        $db_test_entry_ranked_word->selection = NULL;
        $db_test_entry_ranked_word->word_candidate = NULL;
        $db_test_entry_ranked_word->save();        
      }      
    }
    else if( $test_type_name == 'confirmation' )
    {  
      $test_entry_confirmation_class_name = lib::get_class_name(
        'database\test_entry_confirmation' );      
      $db_test_entry_confirmation = 
        $test_entry_confirmation_class_name::get_unique_record(
          'test_entry_id', $db_test_entry->id );

      $db_test_entry_confirmation->confirmation = NULL;
      $db_test_entry_confirmation->save(); 
    }
    else if( $test_type_name == 'classification' )
    {
      foreach( $db_test_entry->get_test_entry_classification_list() as
        $db_test_entry_classification )
      {
        $db_test_entry_classification->word_id = NULL;
        $db_test_entry_classification->word_candidate = NULL;
        $db_test_entry_classification->save(); 
      }
    }
    else if( $test_type_name == 'alpha_numeric' )
    {
      foreach( $db_test_entry->get_test_entry_alpha_numeric_list() as
        $db_test_entry_alpha_numeric )
      {
        $db_test_entry_alpha_numeric->word_id = NULL;
        $db_test_entry_alpha_numeric->save(); 
      }
    }
    else
    {
      throw lib::create( 'exception\runtime',
        'Assignment requires a valid test type, not ' . 
        $test_type_name, __METHOD__ );
    }
    $db_test_entry->completed = 0;
    $db_test_entry->save();
  }
}
