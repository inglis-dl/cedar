<?php
/**
 * test_entry_classify_word.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\pull;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * Class for word classify pull operations.
 *
 */
class test_entry_classify_word extends \cenozo\ui\pull\base_record
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Pull arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry', 'classify_word', $args );
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
    $child_id = $this->get_argument( 'child_id', 0 );
    $db_test = $db_test_entry->get_test();
    $test_type_name = $db_test->get_test_type()->name;
    $db_child_entry = lib::create( 'database\test_entry_'.$test_type_name, $child_id );
    $word_id = $db_child_entry->word_id;

    $this->data = $db_test->get_word_classification( NULL, $word_id, NULL );
  }

  /**
   * Data returned in JSON format.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_data_type()
  {
    return "json";
  }
}
