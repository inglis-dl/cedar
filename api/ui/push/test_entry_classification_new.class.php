<?php
/**
* test_entry_classification_new.class.php
*
* @author Dean Inglis <inglisd@mcmaster.ca>
* @filesource
*/

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
* push: test_entry_classification new
*
* Create a new classification test entry.
*/
class test_entry_classification_new extends \cenozo\ui\push\base_new
{
  /**
* Constructor.
* @author Dean Inglis <inglisd@mcmaster.ca>
* @param array $args Push arguments
* @access public
*/
  public function __construct( $args )
  {
    if( !array_key_exists( 'rank', $args['columns'] ) || empty( $args['columns']['rank'] ) )
    {
      $test_entry_classification_class_name =
        lib::get_class_name( 'database\test_entry_classification' );
      $modifier = lib::create('database\modifier');
      $modifier->where( 'test_entry_id', '=', $args['columns']['test_entry_id'] );
      $args['columns']['rank'] = $test_entry_classification_class_name::count( $modifier ) + 1;
    }

    parent::__construct( 'test_entry_classification', $args );
  }
}
