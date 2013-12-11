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
    
    $this->add_column( 'uid', 'string', 'UId', true );
    $this->add_column( 'cohort', 'string', 'Cohort', true );
    $this->add_column( 'user', 'string', 'User', true );
    $this->add_column( 'defer', 'string', 'Defer', true );
    $this->add_column( 'adjudicate', 'string', 'Adjudicate', true );
    $this->add_column( 'complete', 'string', 'Complete', true );    
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

    $assignment_list = $this->get_record_list();
    $test_entry_class_name = lib::get_class_name('database\test_entry');
    $test_class_name = lib::get_class_name('database\test');
    $test_count = $test_class_name::count();

    $session = lib::create( 'business\session' );
    $db_role = $session->get_role();

    foreach( $assignment_list as $db_assignment )
    {
      $base_mod = lib::create( 'database\modifier' );
      $base_mod->where( 'assignment_id', '=', $db_assignment->id );

      $mod_complete = clone $base_mod;
      $mod_complete->where( 'completed', '=', true );
      $complete_count = $test_entry_class_name::count( $mod_complete );

      if( $complete_count == $test_count && $db_role->name == 'typist' )
        continue;
      
      $db_participant = $db_assignment->get_participant();
      $language = $db_participant->language;

      $mod_defer = clone $base_mod;
      $mod_defer->where( 'deferred', '=', true );
      $defer_count = $test_entry_class_name::count( $mod_defer );

      $mod_adjudicate = clone $base_mod;
      $mod_adjudicate->where( 'adjudicate', '=', true );
      $adjudicate_count = $test_entry_class_name::count( $mod_adjudicate );        

      $this->add_row( $db_assignment->id,
        array( 'uid' => $db_participant->uid,
               'cohort' => $db_participant->get_cohort()->name,
               'user' => $db_assignment->get_user()->name,
               'defer' => $defer_count . '/' . $test_count,
               'adjudicate' => $adjudicate_count . '/' . $test_count,
               'complete' =>  $complete_count . '/' . $test_count ) );
    }
  }
}
