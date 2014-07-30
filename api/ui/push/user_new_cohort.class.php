<?php
/**
 * user_new_cohort.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log;

/**
 * push: user new_cohort
 *
 * Add a cohort to a user.
 */
class user_new_cohort extends \cenozo\ui\push\base_record
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @cohort public
   */
  public function __construct( $args )
  {
    parent::__construct( 'user', 'new_cohort', $args );
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

    $this->get_record()->add_cohort( $this->get_argument( 'id_list' ) );
  }
}
