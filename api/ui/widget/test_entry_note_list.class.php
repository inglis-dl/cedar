<?php
/**
 * test_entry_note_list.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log;

/**
 * widget test_entry_note list
 */
class test_entry_note_list extends \cenozo\ui\widget
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry_note', 'list', $args );
  }

  /**
   * Defines all rows in the list.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $util_class_name = lib::get_class_name( 'util' );

    // get the record's test_entry_note list
    $test_entry_note_list = array();
    foreach( $this->get_record_list() as $record )
    {
      $datetime = 7 > $util_class_name::get_interval( $record->datetime )->days
                ? $util_class_name::get_fuzzy_period_ago( $record->datetime )
                : $util_class_name::get_formatted_date( $record->datetime );
      $test_entry_note_list[] = array( 'id' => $record->id,
                            'user' => $record->get_user()->name,
                            'datetime' => $datetime,
                            'test_entry_note' => $record->test_entry_note );
    }

    $this->set_variable( 'test_entry_note_list', $test_entry_note_list );

    // allow upper tier roles to modify test_entry_notes
    if( 1 < lib::create( 'business\session' )->get_role()->tier )
    {
      $this->set_variable( 'removable', true );
      $this->set_variable( 'editable', true );
    }
    else
    {
      $this->set_variable( 'removable', false );
      $this->set_variable( 'editable', false );
    }
  }
}
