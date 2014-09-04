<?php
/**
 * test_entry_ranked_word_delete.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: test_entry_ranked_word delete
 *
 * Delete a test_entry_ranked_word.
 */
class test_entry_ranked_word_delete extends \cenozo\ui\push\base_delete
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
   * Validate the operation.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   * @throws exception/runtime
   */
  protected function validate()
  {
    parent::validate();

    if( !is_null( $this->get_record()->get_ranked_word_set() ) )
      throw lib::create( 'exception\runtime',
        'Only intrusions may be deleted', __METHOD__ );
  }
}
