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

    $operation_class_name = lib::get_class_name( 'database\operation' );

    $this->add_column( 'test.rank', 'constant', 'Order', true );
    $this->add_column( 'test_id', 'string', 'Test', true );
    $this->add_column( 'language', 'string', 'Language', false );
    $this->add_column( 'audio_status', 'string', 'Audio Status' );
    $this->add_column( 'participant_status', 'string', 'Participant Status' );
    $this->add_column( 'completed', 'boolean', 'Completed', true );
    $this->add_column( 'deferred', 'boolean', 'Deferred', false );

    $db_operation = $operation_class_name::get_operation( 'widget', 'test_entry', 'adjudicate' );
    if( lib::create( 'business\session' )->is_allowed( $db_operation ) )
    {
      $this->adjudicate_allowed = true;
      $this->add_column( 'adjudicate', 'boolean', 'Adjudicate', true );
    }
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

    foreach( $this->get_record_list() as $db_test_entry )
    {
      $db_test = $db_test_entry->get_test();
      $db_language_list = $db_test_entry->get_language_list();
      $db_language = current( $db_language_list );

      $columns = array(
        'test.rank' => $db_test->rank,
        'test_id' => $db_test->name,
        'language' =>  1 < count( $db_language_list ) ? 'Multiple' :
          (is_null( $db_language ) ? 'None' : $db_language->name ),
        'audio_status' =>
          is_null( $db_test_entry->audio_status ) ? '(N/A)' : $db_test_entry->audio_status,
        'participant_status' =>
          is_null( $db_test_entry->participant_status ) ? '(N/A)' : $db_test_entry->participant_status,
        $db_test_entry->participant_status,
        'deferred' =>  in_array( $db_test_entry->deferred, array( 'requested', 'pending' ) ),
        'completed' => $db_test_entry->completed,
        // note count isn't a column, it's used for the note button
        'note_count' => $db_test_entry->get_note_count() );

      if( $this->adjudicate_allowed )
        $columns['adjudicate'] = $db_test_entry->adjudicate;

      $this->add_row( $db_test_entry->id, $columns );
    }
  }

  /**
   * Are adjudications allowed.
   * @var adjudicate_allowed
   * @access protected
   */
  protected $adjudicate_allowed = false;
}
