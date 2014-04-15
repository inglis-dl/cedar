<?php
/**
 * test_entry_alpha_numeric_adjudicate.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget test_entry_alpha_numeric adjudicate
 */
class test_entry_alpha_numeric_adjudicate extends base_adjudicate
{
  /** 
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry_alpha_numeric', $args );
  }

  /** 
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\runtime
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $db_test_entry = $this->parent->get_record();
    $db_test = $db_test_entry->get_test();
    $test_type_name = $db_test->get_test_type()->name;

    if( $test_type_name != 'alpha_numeric' )
      throw lib::create( 'exception\runtime',
              'Widget requires test type to be alpha numeric, not ' .
              $test_type_name, __METHOD__ );

    $db_test_entry_adjudicate = $db_test_entry->get_adjudicate_entry();

    $modifier = lib::create( 'database\modifier' );
    $modifier->order( 'rank' );
    $a = $db_test_entry->get_test_entry_alpha_numeric_list( clone $modifier );
    $b = $db_test_entry_adjudicate->get_test_entry_alpha_numeric_list( clone $modifier );

    $entry_data = array();
    while( !is_null( key( $a ) ) || !is_null( key ( $b ) ) )
    {
      $a_obj = current( $a );
      $b_obj = current( $b );

      $id_1 = ''; 
      $id_2 = ''; 
      $rank = ''; 
      $word_id_1 = ''; 
      $word_id_2 = ''; 
      $word_1 = ''; 
      $word_2 = ''; 
      $adjudicate = false;

      if( false === $a_obj )
      {   
        $id_2 = $b_obj->id;
        $rank = $b_obj->rank;
        $adjudicate = true;
        if( !is_null( $b_obj->word_id ) ) 
        {   
          $db_word = lib::create( 'database\word', $b_obj->word_id );
          $word_id_2 = $db_word->id;
          $word_2 = $db_word->word;
        }
      }   
      else if( false == $b_obj )
      {   
        $id_2 = $a_obj->id;
        $rank = $a_obj->rank;
        $adjudicate = true;
        if( !is_null( $a_obj->word_id ) ) 
        {   
          $db_word = lib::create( 'database\word', $a_obj->word_id );
          $word_id_2 = $db_word->id;
          $word_2 = $db_word->word;
        }
      }
      else 
      {
        $id_1 = $a_obj->id;
        $id_2 = $b_obj->id;
        $rank = $a_obj->rank;
        $adjudicate = $a_obj->word_id != $b_obj->word_id;
              
        if( !is_null( $a_obj->word_id ) )
        { 
          $db_word_1 = lib::create( 'database\word', $a_obj->word_id );
          $word_id_1 = $db_word_1->id;
          $word_1 = $db_word_1->word;
        }
        if( !is_null( $b_obj->word_id ) )
        { 
          $db_word_2 = lib::create( 'database\word', $b_obj->word_id );
          $word_id_2 = $db_word_2->id;
          $word_2 = $db_word_2->word;
        }
      }

      $entry_data[] = array(
               'id_1' => $id_1,
               'id_2' => $id_2,
               'rank' => $rank,
               'word_id_1' => $word_id_1,
               'word_1' => $word_1,
               'word_id_2' => $word_id_2,
               'word_2' => $word_2,
               'adjudicate' => $adjudicate );

      next( $a );
      next( $b );
    }
    
    $this->set_variable( 'entry_data', $entry_data );
  }
}
