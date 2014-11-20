<?php
/**
 * test_entry_delete_language.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log;

/**
 * push: test_entry delete_language
 */
class test_entry_delete_language extends \cenozo\ui\push\base_record
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry', 'delete_language', $args );
  }

  /**
   * Validate the operation.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    // do not delete a language if any of this records daughter entries
    // have words of that language in use
    $db_test_entry = $this->get_record();
    $test_type_name = $db_test_entry->get_test()->get_test_type()->name;

    if( 'confirmation' != $test_type_name )
    {
      $get_count_method = 'get_test_entry_' . $test_type_name . '_count';
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'word.language_id', '=', $this->get_argument( 'remove_id' ) );
      if( 0 < $db_test_entry->$get_count_method( $modifier ) )
        throw lib::create( 'exception\notice',
          'Cannot delete language.  Try resetting the test entry first.', __METHOD__ );
    }
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

    $this->get_record()->remove_language( $this->get_argument( 'remove_id' ) );
  }
}
