<?php
/**
 * test_entry_new_language.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log;

/**
 * push: test_entry new_language
 */
class test_entry_new_language extends \cenozo\ui\push\base_record
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @language public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry', 'new_language', $args );
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

    $db_test_entry = $this->get_record();
    $test_type_name = $db_test_entry->get_test()->get_test_type()->name;

    if( 'ranked_word' == $test_type_name || 'alpha_numeric' == $test_type_name )
    {
      if( 1 == $db_test_entry->get_language_count() );
        throw lib::create( 'exception\notice',
          'The ' .  $db_test_entry->get_test()->name .
          ' test cannot have more than one language. '.
          'Try removing the other language first.', __METHOD__ );
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

    $this->get_record()->add_language( $this->get_argument( 'id_list' ) );
  }
}
