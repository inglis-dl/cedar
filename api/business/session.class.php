<?php
/**
 * session.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\business;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * Extends Cenozo's session class with custom functionality
 */
final class session extends \cenozo\business\session
{
  /**
   * Define the operation being performed.
   * Override parent class method so that certain high frequency edit operations
   * are excluded from being tracked in the activity table.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param ui\operation $operation
   * @access public
   */
  public function set_operation( $operation, $arguments )
  {
    if( !in_array( $operation->get_full_name(), $this->exclude_operation_list ) )
      parent::set_operation( $operation, $arguments );
  }

  /**
   * A list of all operations to exclude from the activity log
   * @var array
   * @access protected
   */
  protected $exclude_operation_list = array(
   'test_entry_alpha_numeric_edit',
   'test_entry_ranked_word_edit',
   'test_entry_confirmation_edit',
   'test_entry_classification_edit' );
}
