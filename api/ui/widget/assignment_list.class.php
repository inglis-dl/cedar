<?php
/**
 * assignment_list.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget assignment list
 */
class assignment_list extends \cenozo\ui\widget\base_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the assignment list.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'assignment', $args );
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
    
    $this->add_column( 'uid', 'constant', 'UId', true );
    $this->add_column( 'cohort', 'constant', 'Cohort', true );
    $this->add_column( 'user', 'constant', 'User', true );
    /*
    $this->add_column( 'language', 'constant', 'Language', true );
    $this->add_column( 'defer', 'constant', 'Defer', true );
    $this->add_column( 'adjudicate', 'string', 'Adjudicate', true );
    $this->add_column( 'complete', 'string', 'Complete', true );
    */
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

    $participant_class_name = lib::get_class_name( 'database\participant' );
    foreach( $this->get_record_list() as $record )
    {
      $db_participant = $record->get_participant();
      $language = $db_participant->language;
      $this->add_row( $record->id,
        array( 'uid' => $db_participant->uid,
               'cohort' => $db_participant->get_cohort()->name,
               'user' => $record->get_user()->name
               /*
               'language' => (is_null($language) ? 'en' : $language ),
               'defer' => '0/X',
               'adjudicate' => '0/X',
               'complete' =>'0/X' 
               */
               ) );
    }
  }
}
