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
class test_entry_alpha_numeric_adjudicate extends \cenozo\ui\widget
{
  /** 
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry_alpha_numeric', 'adjudicate', $args );
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

    // parent must be a test_entry_adjudicate widget
    if( is_null( $this->parent ) ) 
      throw lib::create( 'exception\runtime', 'This class must have a parent', __METHOD__ );
   
    $db_test_entry = $this->parent->get_record();

    $db_test = $db_test_entry->get_test();
    $heading = $db_test->name . ' test adjudicate form';

    //TODO put this somewhere else
    if( $db_test_entry->deferred )
      $heading = $heading . ' NOTE: this test is currently deferred';

    $this->set_heading( $heading );
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
    while( !is_null( key( $a ) ) && !is_null( key ( $b ) ) )
    {
      $a_obj = current( $a );
      $b_obj = current( $b );
      if( !(is_null( $a_obj->word_id ) && is_null( $b_obj->word_id )) )
      {
        $adjudicate = $a_obj->word_id != $b_obj->word_id ? true : false;
              
        $db_word_1 = is_null( $a_obj->word_id ) ? null :
          lib::create( 'database\word', $a_obj->word_id );
        $db_word_2 = is_null( $b_obj->word_id ) ? null :
          lib::create( 'database\word', $b_obj->word_id );

        $entry_data[] = array(
                 'id_1' => $a_obj->id,
                 'id_2' => $b_obj->id,
                 'rank' => $a_obj->rank,
                 'word_id_1' => is_null( $db_word_1 ) ? '' :  $db_word_1->id,
                 'word_1' => is_null( $db_word_1 ) ? '' :  $db_word_1->word,
                 'word_id_2' => is_null( $db_word_2 ) ? '' :  $db_word_2->id,
                 'word_2' => is_null( $db_word_2 ) ? '' :  $db_word_2->word,
                 'adjudicate' => $adjudicate );
      }
      next( $a );
      next( $b );
    }
    
    $this->set_variable( 'entry_data', $entry_data );
  }
}
