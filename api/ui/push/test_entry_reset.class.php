<?php
/**
 * test_entry_reset.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: test_entry reset
 *
 * Edit a test entry.
 */
class test_entry_reset extends \cenozo\ui\push\base_record
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry', 'reset', $args );
  }

  /** 
   * This method executes the operation's purpose.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $db_test_entry = $this->get_record();
    $test_type_name = $db_test_entry->get_test()->get_test_type()->name;
    if( $test_type_name == 'ranked_word' )
    {
      $intrusion_list = array();
      foreach( $db_test_entry->get_test_entry_ranked_word_list() 
        as $db_test_entry_ranked_word )
      {
        if( is_null( $db_test_entry_ranked_word->selection ) && 
            is_null( $db_test_entry_ranked_word->word_id ) )
        {
          $intrusion_list[] = $db_test_entry_ranked_word;
        }
        else
        {
          $db_test_entry_ranked_word->selection = NULL;
          $db_test_entry_ranked_word->word_candidate = NULL;
          $db_test_entry_ranked_word->save();       
        }
      }  
      foreach( $intrusion_list as $db_test_entry_ranked_word )
      {
        $args = array();
        $args['id'] = $db_test_entry_ranked_word->id;
        $operation = lib::create( 'ui\push\test_entry_ranked_word_delete', $args );
        $operation->process();
      }
    }
    else if( $test_type_name == 'confirmation' )
    {  
      $test_entry_confirmation_class_name = lib::get_class_name(
        'database\test_entry_confirmation' );      
      $db_test_entry_confirmation = 
        $test_entry_confirmation_class_name::get_unique_record(
          'test_entry_id', $db_test_entry->id );

      $db_test_entry_confirmation->confirmation = NULL;
      $db_test_entry_confirmation->save(); 
    }
    else if( $test_type_name == 'classification' )
    {
      $intrusion_list = array();
      $setting_manager = lib::create( 'business\setting_manager' );
      $rank = 1;
      $max_rank = $setting_manager->get_setting( 'interface', 'classification_max_rank' );
      foreach( $db_test_entry->get_test_entry_classification_list() as
        $db_test_entry_classification )
      {
        if( $rank++ <= $max_rank )
        {
          $db_test_entry_classification->word_id = NULL;
          $db_test_entry_classification->word_candidate = NULL;
          $db_test_entry_classification->save(); 
        }  
        else 
        {
          $intrusion_list[] = $db_test_entry_classification;
        }
      }
      foreach( $intrusion_list as $db_test_entry_classification )
      {
        $args = array();
        $args['id'] = $db_test_entry_classification->id;
        $operation = lib::create( 'ui\push\test_entry_classification_delete', $args );
        $operation->process();
      }
    }
    else if( $test_type_name == 'alpha_numeric' )
    {
      foreach( $db_test_entry->get_test_entry_alpha_numeric_list() as
        $db_test_entry_alpha_numeric )
      {
        $db_test_entry_alpha_numeric->word_id = NULL;
        $db_test_entry_alpha_numeric->save(); 
      }
    }
    else
    {
      throw lib::create( 'exception\runtime',
        'Assignment requires a valid test type, not ' . 
        $test_type_name, __METHOD__ );
    }
    $db_test_entry->completed = 0;
    $db_test_entry->save();
  }
}
