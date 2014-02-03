<?php
/**
 * test_entry_submit.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: test_entry submit
 *
 * Create and submit a new test entry from an ajdudication.
 */
class test_entry_submit extends \cenozo\ui\push\base_new
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry', $args );
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
  
    $columns = $this->get_argument( 'columns', array() );

    if( !array_key_exists( 'participant_id', $columns ) ) 
      throw lib::create( 'exception\notice',
        'The user\'s name cannot be left blank.', __METHOD__ );
  }


  protected function execute()
  {
    parent::execute();

    $columns = $this->get_argument( 'columns' );

    log::debug( $columns );

  }
}
