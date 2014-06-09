<?php
/**
 * test_entry_alpha_numeric_transcribe.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget test_entry_alpha_numeric transcribe
 */
class test_entry_alpha_numeric_transcribe extends base_transcribe
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry_alpha_numeric', $args );
  }

  /**
   * Sets up the operation with any pre-execution instructions that may be necessary.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\runtime
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $db_test_entry = $this->parent->get_record();

    $modifier = lib::create( 'database\modifier' );
    $modifier->order( 'rank' );
    $entry_data = array();
    foreach( $db_test_entry->get_test_entry_alpha_numeric_list( $modifier ) as
             $db_test_entry_alpha_numeric )
    {
      $db_word = $db_test_entry_alpha_numeric->get_word();

      $row = array(
               'id' => $db_test_entry_alpha_numeric->id,
               'rank' => $db_test_entry_alpha_numeric->rank,
               'word_id' => is_null( $db_word ) ? '' :  $db_word->id,
               'word' => is_null( $db_word ) ? '' :  $db_word->word );

      $entry_data[] = $row;
    }

    $this->set_variable( 'entry_data', $entry_data );
  }
}
