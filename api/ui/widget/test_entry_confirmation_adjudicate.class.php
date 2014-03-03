<?php
/**
 * test_entry_confirmation_adjudicate.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget test_entry_confirmation adjudicate
 */
class test_entry_confirmation_adjudicate extends base_adjudicate
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
              'Widget requires test type to be confirmation, not ' . 
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

    $db_test_entry_adjudicate = $db_test_entry->get_adjudicate_entry(); 

    // Get the db entries
    $test_entry_confirmation_class_name = lib::get_class_name( 'database\test_entry_confirmation' );
    $a = $test_entry_confirmation_class_name::get_unique_record(
      'test_entry_id', $db_test_entry->id );
    $b = $test_entry_confirmation_class_name::get_unique_record(
      'test_entry_id', $db_test_entry_adjudicate->id );
    
    $entry_data[ 'instruction' ] = $instruction;
    if( $a->confirmation != $b->confirmation )
    {
      $entry_data[ 'id_1' ] = $a->id;
      $entry_data[ 'id_2' ] = $b->id;
      $entry_data[ 'confirmation_1' ] = is_null( $a->confirmation ) ? '' : $a->confirmation;
      $entry_data[ 'confirmation_2' ] = is_null( $b->confirmation ) ? '' : $b->confirmation;
    }                     
    $this->set_variable( 'entry_data', $entry_data );
  }
}
