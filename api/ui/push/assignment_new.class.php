<?php
/**
 * assignment_new.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: assignment new
 *
 * Create a new assignment.
 */
class assignment_new extends \cenozo\ui\push\base_new
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'assignment', $args );
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

    // make sure the name column isn't blank
    $columns = $this->get_argument( 'columns' );

    if( !array_key_exists( 'user_id', $columns ) || 0 == strlen( $columns['user_id'] ) ) 
      throw lib::create( 'exception\notice',
        'The user\'s name cannot be left blank.', __METHOD__ );
  }

  /** 
   * Finishes the operation with any post-execution instructions that may be necessary.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\runtime
   * @access protected
   */
  protected function finish()
  {
    parent::finish();

    $db_assignment = $this->get_record();
    $columns = $this->get_argument( 'columns' );
    $test_class_name = lib::get_class_name( 'database\test' );

    $modifier = NULL; 
    if( $columns['cohort_name'] == 'tracking' )
    {
      $modifier = lib::create('database\modifier');
      $modifier->where( 'name', 'not like', 'FAS%' );
    }  

    $language = $db_assignment->get_participant()->language;
    $language = is_null( $language ) ? 'en' : $language;
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );

    //create a test entry for each test
    foreach( $test_class_name::select( $modifier ) as $db_test )
    {
      $args = array();
      $args['columns']['test_id'] = $db_test->id;
      $args['columns']['assignment_id'] = $db_assignment->id;
      $operation = lib::create( 'ui\push\test_entry_new', $args );
      $operation->process();
    }
  }
}
