<?php
/**
 * cohort_list.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log;

/**
 * widget cohort list
 */
class cohort_list extends \cenozo\ui\widget\base_list
{

  /**
   * Constructor
   *
   * Defines all variables required by the cohort list.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'cohort', $args );
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

    $this->add_column( 'name', 'string', 'Name', true );
    $this->add_column( 'participants', 'number', 'Participants', false );
    $this->add_column( 'users', 'number', 'Users', false );
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
      $this->add_row( $record->id,
        array( 'name' => $record->name,
               'participants' => $record->get_participant_count(),
               'users' => $record->get_user_count() ) );
    }
  }
}
