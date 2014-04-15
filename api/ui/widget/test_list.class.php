<?php
/**
 * test_list.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget test list
 */
class test_list extends \cenozo\ui\widget\base_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the test list.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test', $args );
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

    $test_class_name = lib::get_class_name( 'database\test');
    $modifier = lib::create('database\modifier');
    $modifier->where( 'dictionary_id', '=', NULL );
    $allow_primary_sort = 
      $test_class_name::count( $modifier ) != $test_class_name::count();

    $this->add_column( 'rank', 'constant', 'Order', true );
    $this->add_column( 'name', 'string', 'Name', true );
    $this->add_column( 'strict', 'constant', 'Strict', true );
    $this->add_column( 'rank_words', 'constant', 'Words Ranked', true );
    $this->add_column( 'dictionary_id', 'string', 'Primary Dictionary', $allow_primary_sort );
    $this->add_column( 'variant_dictionary_id', 'string', 'Variant Dictionary', true );
    $this->add_column( 'intrusion_dictionary_id', 'string', 'Intrusion Dictionary', true );

    $this->set_variable( 'sort_column', 'rank' );
    $this->set_variable( 'sort_desc', false );
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
    
    $dictionary_class_name = lib::get_class_name( 'database\dictionary' );

    $dictionary_list = array();
    foreach( $dictionary_class_name::select() as $db_dictionary )
       $dictionary_list[$db_dictionary->id] = $db_dictionary->name;

    foreach( $this->get_record_list() as $record )
    {
      // assemble the row for this record
      $this->add_row( $record->id,
        array( 
          'rank' => $record->rank,
          'name' => $record->name,
          'strict' => ( $record->strict ? 'yes' : 'no' ),
          'rank_words' => ( $record->rank_words ? 'yes' : 'no' ),
          'dictionary_id' =>  ( is_null( $record->dictionary_id ) ? '(none)' :
             $dictionary_list[ $record->dictionary_id ] ),
           'variant_dictionary_id' => ( is_null( $record->variant_dictionary_id ) ? 
             ( $record->strict ? 'N/A' : '(none)' ) :
             $dictionary_list[ $record->variant_dictionary_id ] ),
           'intrusion_dictionary_id' => ( is_null( $record->intrusion_dictionary_id ) ?
             ( $record->strict ? 'N/A' : '(none)' ) :
             $dictionary_list[ $record->intrusion_dictionary_id ] ) )
        );
    }
  }
}
