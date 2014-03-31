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
   
  /** 
   * Initialize an assignment.  All existing test_entry records are deleted
   * and new test_entry records are created.
   * Only assigments that have never been adjudicated or finished can be initialized.
   * This method is typically called during creation of a db_assignment or
   * to reset a db_test_entry.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @param  database\assignment $db_assignment
   * @param  integer $test_id id of test associated with test_entry records to delete and recreate
   * @access public
   */
  public static function initialize_assignment( $db_assignment, $test_id = NULL )
  {
    if( !is_null( $db_assignment->end_datetime ) )
      throw lib::create( 'exception\notice',
        'The assignment for participant UID ' . $db_assignment->get_participant()->uid .
        'is closed and cannot be initialized', __METHOD__ );

    $test_class_name = lib::get_class_name( 'database\test' );
    $test_entry_class_name = lib::get_class_name( 'database\test_entry' );

    // delete test_entry record(s)
    $base_mod = NULL;
    if( !is_null( $test_id ) )
    {
      $base_mod = lib::create( 'database\modifier' );
      $base_mod->where( 'test_id', '=', $test_id );
    }
    $entry_mod = is_null( $base_mod ) ? NULL : clone $base_mod;
    foreach( $db_assignment->get_test_entry_list( $entry_mod ) as $db_test_entry )
      $db_test_entry->delete();

    // get sibling assignment, reset test_entry adjudicate value from 1 to NULL
    $db_sibling_assignment = $db_assignment->get_sibling_assignment();
    if( !is_null( $db_sibling_assignment ) )
    {
      $adjudicate_mod = is_null( $base_mod ) ? lib::create( 'database\modifier' ) : clone $base_mod;
      $adjudicate_mod->where( 'adjudicate', '=', true );
      foreach( $db_assignment->get_test_entry_list( $adjudicate_mod ) as $db_test_entry )
      {
        $db_test_entry->adjudicate = NULL;
        $db_test_entry->save();
      }
    }

    // test battery depends on cohort
    $test_mod = NULL;
    if( !is_null( $test_id ) )
    {
      $test_mod = lib::create( 'database\modifier' );
      $test_mod->where( 'id', '=', $test_id );
    }
    $db_participant = $db_assignment->get_participant();
    if( $db_participant->get_cohort()->name == 'tracking' )
    {
      if( is_null( $test_mod ) ) $test_mod = lib::create( 'database\modifier' );
      $test_mod->where( 'name', 'NOT LIKE', 'FAS%' );
    }

    $language = $db_participant->language;
    $language = is_null( $language ) ? 'en' : $language;

    // create test_entry record(s)
    foreach( $test_class_name::select( $test_mod ) as $db_test )
    { 
      $db_test_entry = lib::create( 'database\test_entry' );
      $db_test_entry->test_id = $db_test->id;
      $db_test_entry->assignment_id = $db_assignment->id;
      $db_test_entry->save();
      // create daughter entry record(s)
      static::initialize_test_entry( $db_test_entry );
    }
  }

  /** 
   * Initialize a test_entry. Whem optional $args are available, thus signifying an adjudicate
   * test_entry creation and submission, the daughter table entries are created and filled
   * 
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @param  database\test_entry $db_test_entry
   * @param  array() $args Arguments to fill in created daughter table entry data
   * @access public
   */
  public static function initialize_test_entry( $db_test_entry )
  {
    $word_class_name = lib::get_class_name( 'database\word' );

    $db_test = $db_test_entry->get_test();
    $test_type_name = $db_test->get_test_type()->name;
    $entry_class_name = 'test_entry_' . $test_type_name;

    $db_assignment = $db_test_entry->get_assignment();
    // if null db_assignment then db_test_entry is an ajudication
    if( is_null( $db_assignment ) )
      $db_participant = $db_test_entry->get_participant();
    else
      $db_participant = $db_assignment->get_participant();

    $language = $db_participant->language;
    $language = is_null( $language ) ? 'en' : $language;

    if( $test_type_name == 'ranked_word' )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->order( 'rank' );
      foreach( $db_test->get_ranked_word_set_list( $modifier ) as $db_ranked_word_set )
      {
        $db_test_entry_ranked_word = lib::create( 'database\\'. $entry_class_name );
        $word_id = 'word_' . $language . '_id';
        $db_test_entry_ranked_word->word_id = $db_ranked_word_set->$word_id;
        $db_test_entry_ranked_word->test_entry_id = $db_test_entry->id;
        $db_test_entry_ranked_word->save();
      }
    }
    else if( $test_type_name == 'confirmation' )
    {
      $db_test_entry_confirmation = lib::create( 'database\\'. $entry_class_name );
      $db_test_entry_confirmation->test_entry_id = $db_test_entry->id;
      $db_test_entry_confirmation->save();
    }
    else if( $test_type_name == 'classification' )
    {
      $setting_manager = lib::create( 'business\setting_manager' );
      $max_rank = $setting_manager->get_setting( 'interface', 'classification_max_rank' );
      for( $rank = 1; $rank <= $max_rank; $rank++ )
      {
        $db_test_entry_classification = lib::create( 'database\\'. $entry_class_name );
        $db_test_entry_classification->test_entry_id = $db_test_entry->id;
        $db_test_entry_classification->rank = $rank;
        $db_test_entry_classification->save();
      }
    }
    else if( $test_type_name == 'alpha_numeric' )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'language', '=', $language );
      $word_count = $db_test->get_dictionary()->get_word_count( $modifier );
      for( $rank = 1; $rank <= $word_count; $rank++ )
      {
        $db_test_entry_alpha_numeric = lib::create( 'database\\'. $entry_class_name );
        $db_test_entry_alpha_numeric->test_entry_id = $db_test_entry->id;
        $db_test_entry_alpha_numeric->rank = $rank;
        $db_test_entry_alpha_numeric->save();
      }
    }
  }

  /** 
   * Update a test_entry, its assigment, its sibling assignment and its sibling
   * assignment's test_entry based on its complete status.  This method is
   * typically called whenever a daughter table entry is edited.
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
      // does the sibling assignment exist?
      $db_sibling_assignment = $db_assignment->get_sibling_assignment();
      if( !is_null( $db_sibling_assignment ) ) 
      {   
        // get the sibling test entry
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'test_id', '=', $db_test_entry->test_id );
        $db_sibling_test_entry = current( $db_sibling_assignment->get_test_entry_list( $modifier ) );
        // only check for adjudication if both tests are complete and not deferred
        if( $db_sibling_test_entry->completed && !$db_sibling_test_entry->deferred ) 
        {   
          // compare the daughter table entries, true if identical
          if( $db_test_entry->compare( $db_sibling_test_entry ) ) 
          {
            // NOTE: test_entry records with adjudicate set to false cannot be deleted
            $db_test_entry->adjudicate = false;
            $db_sibling_test_entry->adjudicate = false;

            $modifier = lib::create( 'database\modifier' );
            $modifier->where( 'assignment_id', 'IN', 
              array( $db_assignment->id, $db_sibling_assignment->id ) );
            // count the number of records requiring adjudication or have not been tested
            $modifier->where( 'IFNULL( adjudicate, 1 )', '=', 1 );

            if( 0 == $test_entry_class_name::count( $modifier ) ) 
            {   
              // both assignments are now complete so set their end datetimes
              $end_datetime = util::get_datetime_object()->format( "Y-m-d H:i:s" );
              $db_assignment->end_datetime = $end_datetime;
              $db_sibling_assignment->end_datetime = $end_datetime;
              $db_assignment->save();
              $db_sibling_assignment->save(); 
            }   
          }   
          else
          {   
            $db_test_entry->adjudicate = true;
            $db_sibling_test_entry->adjudicate = true;
          }   

          $db_sibling_test_entry->save();
        }   
      }   
    }   

    $db_test_entry->save();
  } 
}
