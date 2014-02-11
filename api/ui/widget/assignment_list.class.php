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
    
    $this->add_column( 'participant.uid', 'string', 'UId', true );
    $this->add_column( 'cohort', 'string', 'Cohort', false );
    $this->add_column( 'user.name', 'string', 'User', true );
    $this->add_column( 'defer', 'string', 'Defer', false );
    $this->add_column( 'adjudicate', 'string', 'Adjudicate', false );
    $this->add_column( 'complete', 'string', 'Complete', false );    
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
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );
    $test_class_name = lib::get_class_name( 'database\test' );

    $session = lib::create( 'business\session' );
    $db_role = $session->get_role();
    $db_user = $session->get_user();
    $allow_transcribe_operation = false;
    $allow_adjudicate_operation = false;

    foreach( $assignment_list as $db_assignment )
    {
      if( $db_role->name == 'typist' && $db_assignment->get_user()->name != $db_user->name )
        continue;

      $base_mod = lib::create( 'database\modifier' );
      $base_mod->where( 'assignment_id', '=', $db_assignment->id );
      $test_count = $test_entry_class_name::count( clone $base_mod );

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

      $allow_transcribe = false;
      $allow_adjudicate = false;
      $test_entry_id = null;

      if( $db_role->name == 'typist' )
      {
        $mod_test_entry = clone $base_mod;
        $mod_test_entry->where( 'completed', '=', false );
        $mod_test_entry->order( 'test.rank' );
        $mod_test_entry->limit( 1 );
        $db_test_entry = $test_entry_class_name::select( $mod_test_entry );
        if( !is_null( $db_test_entry[0] ) ) 
        {  
          $test_entry_id = $db_test_entry[0]->id;
          $allow_transcribe = true;
          $allow_transcribe_operation |= $allow_transcribe;
        }
      }
      else if( $db_role->name == 'administrator' )
      {
        if( $complete_count == $test_count && $adjudicate_count > 0 )
        {
          $allow_adjudicate = true;
          $mod_test_entry = clone $base_mod;
          $mod_test_entry->where( 'adjudicate', '=', true );
          $adjudicate_assignment_id = NULL;
          foreach( $test_entry_class_name::select( $mod_test_entry ) as $db_test_entry )
          {
            $db_adjudicate_entry = $db_test_entry->get_adjudicate_entry();
            if( $db_adjudicate_entry == NULL )
            {
              $allow_adjudicate = false;
              break;
             }
             
             if( $adjudicate_assignment_id == NULL )
             {
               $adjudicate_assignment_id = $db_adjudicate_entry->get_assignment()->id;
               $mod_complete = lib::create( 'database\modifier' );
               $mod_complete->where( 'assignment_id', '=', $adjudicate_assignment_id );
               $mod_complete->where( 'completed', '=', false );
               $adjudicate_complete_count = $test_entry_class_name::count( $mod_complete );
               if( $adjudicate_complete_count > 0 )
               {
                 $allow_adjudicate = false;
                 break;
               }
             }
          }
          if( $allow_adjudicate )
          {
            $mod_test_entry = clone $base_mod;
            $mod_test_entry->where( 'adjudicate', '=', true );
            $mod_test_entry->order( 'test.rank' );
            $mod_test_entry->limit( 1 );
            $db_test_entry = $test_entry_class_name::select( $mod_test_entry );
            if( !empty( $db_test_entry ) )
            {
              $test_entry_id = $db_test_entry[0]->id;
              $allow_adjudicate_operation = $allow_adjudicate;
            }
          }
        }
      }

      $this->add_row( $db_assignment->id,
        array( 'participant.uid' => $db_participant->uid,
               'cohort' => $db_participant->get_cohort()->name,
               'user.name' => $db_assignment->get_user()->name,
               'defer' => 
                 $defer_count > 0 ? $defer_count . '/' . $test_count : 'none',
               'adjudicate' => 
                 $adjudicate_count > 0 ? $adjudicate_count . '/' . $test_count : 'none',
               'complete' =>  
                 $complete_count > 0 ? $complete_count . '/' . $test_count : 'none',
               'allow_transcribe' => $allow_transcribe,
               'allow_adjudicate' => $allow_adjudicate,
               'test_entry_id' => $test_entry_id ) );
    }

    // define whether or not test_entry transcribing or adjudicating is allowed
    $operation_class_name = lib::get_class_name( 'database\operation' );
    $db_operation = $operation_class_name::get_operation( 'widget', 'test_entry', 'transcribe' );
    $this->set_variable( 'allow_transcribe',
      ( lib::create( 'business\session' )->is_allowed( $db_operation ) && 
        $allow_transcribe_operation ) );   
    $db_operation = $operation_class_name::get_operation( 'widget', 'test_entry', 'adjudicate' );
    $this->set_variable( 'allow_adjudicate',
      ( lib::create( 'business\session' )->is_allowed( $db_operation ) && 
        $allow_adjudicate_operation ) );
  }
}
