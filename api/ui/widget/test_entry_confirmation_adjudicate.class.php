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

    $this->set_variable( 'instruction', $instruction );
  }
}
