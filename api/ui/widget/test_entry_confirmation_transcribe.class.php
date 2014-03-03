<?php
/**
 * test_entry_confirmation_transcribe.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget test_entry_confirmation transcribe
 */
class test_entry_confirmation_transcribe extends base_transcribe
{
  /** 
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry_confirmation', $args );
  }

  /** 
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $db_test_entry = $this->parent->get_record();
    $db_test = $db_test_entry->get_test();
    $test_type_name = $db_test->get_test_type()->name;

    if( $test_type_name != 'confirmation' )
      throw lib::create( 'exception\runtime',
              'Widget requires test type to be ranked word, not ' . 
              $test_type_name, __METHOD__ );
    
    $instruction = "Was the participant able to ";
    if( preg_match( '/alpha/', $db_test->name ) )
    {
      $instruction = $instruction .
        "recite the alphabet, from A, B, C, D and so on?";
    }
    else
    {
      $instruction = $instruction .
        "count from 1 to 20, from 1, 2, 3, 4 and so on?";
    }

    // Get the db entries
    $test_entry_confirmation_class_name = lib::get_class_name( 'database\test_entry_confirmation' );
    $db_test_entry_confirmation = $test_entry_confirmation_class_name::get_unique_record(
      'test_entry_id', $db_test_entry->id );

    $entry_data = array( 'id' => $db_test_entry_confirmation->id,
                         'confirmation' => is_null( $db_test_entry_confirmation->confirmation ) ? '' :
                            $db_test_entry_confirmation->confirmation,
                         'instruction' => $instruction );
    $this->set_variable( 'entry_data', $entry_data );                     
  }
}
