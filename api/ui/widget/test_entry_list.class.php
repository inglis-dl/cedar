<?php
/**
 * test_entry_list.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget test_entry list
 */
class test_entry_list extends \cenozo\ui\widget\base_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the test_entry list.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry', $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();
    
    $this->add_column( 'test.rank', 'constant', 'Order', true );
    $this->add_column( 'test_id', 'string', 'Test', true );
    $this->add_column( 'audio_fault', 'boolean', 'Audio Fault', true );
    $this->add_column( 'completed', 'boolean', 'Completed', true );
    $this->add_column( 'deferred', 'boolean', 'Deferred', true );
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    foreach( $this->get_record_list() as $record )
    {
      $db_test = $record->get_test();
      $this->add_row( $record->id,
        array( 'test.rank' => $db_test->rank,
               'test_id' => $db_test->name,
               'audio_fault' => $record->audio_fault,
               'completed' => $record->completed,
               'deferred' => $record->deferred
               ) );
    }
  }
}
