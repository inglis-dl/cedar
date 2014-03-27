<?php
/**
 * assignment_manager.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\business;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * Manages assignments.
 */
class assignment_manager extends \cenozo\singleton
{
  /**
   * Constructor.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function __construct()
  {
  }
   
  public static function initialize( $db_assignment, $db_cohort )
  {
    $test_class_name = lib::get_class_name( 'database\test' );
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );
    // if there are any test entries, delete them all

    // get the cohort name
    // set the end_datetime to NULL

    // get the participant cohort since some tests depend on cohort
    $modifier = NULL;
    if( $columns['cohort_name'] == 'tracking' )
    {
      $modifier = lib::create('database\modifier');
      $modifier->where( 'name', 'NOT LIKE', 'FAS%' );
    }  
    $language = $db_assignment->get_participant()->language;
    $language = is_null( $language ) ? 'en' : $language;

    //create a test entry for each test
    foreach( $test_class_name::select( $modifier ) as $db_test )
    {   
      $args = array();
      $args['columns']['test_id'] = $db_test->id;
      $args['columns']['assignment_id'] = $db_assignment->id;
      $operation = lib::create( 'ui\push\test_entry_new', $args );
      $operation->process();
    }



    $db_assignment->end_datetime = NULL;
    $db_assignment->save();      
  }

  /** 
   * Update a test_entry, its assigment, its sibling assignment and its sibling
   * assignment's test_entry based on 
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param  database\test_entry
   * @access public
   */
  public static function complete_test_entry( $db_test_entry )
  {
    $db_test_entry->completed = $db_test_entry->is_completed();
          
    // check if we need to adjudicate
    if( $db_test_entry->completed && !$db_test_entry->deferred )
    {   
      $db_assignment = $db_test_entry->get_assignment();
      // does the sibling assignment exist ?
      $db_sibling_assignment = $db_assignment->get_sibling_assignment();
      if( !is_null( $db_sibling_assignment ) ) 
      {   
        // get the sibling test entry
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'test_id', '=', $db_test_entry->test_id );
        $db_sibling_test_entry = current( $db_sibling_assignment->get_test_entry_list( modifier ) );
        // only check for adjudication if both tests are complete and not deferred
        if( $db_sibling_test_entry->completed && !$db_sibling_test_entry->deferred ) 
        {   
          // compare the daughter table entries, true if identical
          if( $db_test_entry->compare( $db_sibling_test_entry ) ) 
          {   
            $db_test_entry->adjudicate = 0;
            $db_sibling_test_entry->adjudicate = 0;

            $modifier = lib::create( 'database\modifier' );
            $modifier->where( 'assignment_id', 'IN', 
              array( $db_assignment->id, $db_sibling_assignment->id ) );
            // count the number of records requiring adjudication or have not been tested
            $modifier->where( 'IFNULL( adjudicate, 1 )', '=', 1 );

            if( 0 == $test_entry_class_name::count( $modifier ) ) 
            {   
              // both assignments are now complete so set the end datetime
              $end_datetime = util::get_datetime_object()->format( "Y-m-d H:i:s" );
              $db_assignment->end_datetime = $end_datetime;
              $db_sibling_assignment->end_datetime = $end_datetime;
              $db_assignment->save();
              $db_sibling_assignment->save(); 
            }   
          }   
          else
          {   
            $db_test_entry->adjudicate = 1;
            $db_sibling_test_entry->adjudicate = 1;
          }   

          $db_sibling_test_entry->save();
        }   
      }   
    }   

    $db_test_entry->save();
  } 
}
