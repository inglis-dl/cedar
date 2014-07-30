<?php
/**
 * dictionary_transfer_word.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget dictionary transfer_word
 */
class dictionary_transfer_word extends base_transfer_list
{
  /**
   * Constructor
   *
   * Defines all variables which need to be set for the associated template.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param string $name The name of the word.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'dictionary', 'word', $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $test_class_name = lib::get_class_name( 'database\test' );
    $this->sources = array();
    $this->targets = array();

    $db_dictionary = $this->get_record();
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'dictionary_id', '=', $db_dictionary->id );
    $modifier->or_where( 'intrusion_dictionary_id', '=', $db_dictionary->id );
    $modifier->or_where( 'variant_dictionary_id', '=', $db_dictionary->id );
    $modifier->or_where( 'mispelled_dictionary_id', '=', $db_dictionary->id );
    $modifier->limit( 1 );

    foreach( $test_class_name::select( $modifier ) as $db_test )
    {
      $db_dictionary = $db_test->get_dictionary();
      if( is_null( $db_dictionary ) ) continue;
      $current_sources = array();
      $current_sources[$db_dictionary->id] = $db_dictionary->name;

      $db_intrusion_dictionary = $db_test->get_intrusion_dictionary();
      if( !is_null( $db_intrusion_dictionary ) )
        $current_sources[$db_intrusion_dictionary->id] = $db_intrusion_dictionary->name;

      $db_variant_dictionary = $db_test->get_variant_dictionary();
      if( !is_null( $db_variant_dictionary ) )
        $current_sources[$db_variant_dictionary->id] = $db_variant_dictionary->name;

      $db_mispelled_dictionary = $db_test->get_mispelled_dictionary();
      if( !is_null( $db_mispelled_dictionary ) )
        $current_sources[$db_mispelled_dictionary->id] = $db_mispelled_dictionary->name;

      $keys = array_keys( $current_sources );
      $key_count = count( $keys );
      for( $i = 0; $i < $key_count; $i++ )
      {
        $k = $keys[$i];
        $tmp = array();
        $tmp[] = 'delete';
        for( $j = 0; $j < $key_count; $j++ )
        {
          if( $i != $j ) $tmp[ $keys[$j] ] = $current_sources[ $keys[$j] ];
        }
        $this->sources[$k]=$current_sources[$k];
        $this->targets[$k]=$tmp;
      }
    }
  }

  /** 
   * Finish setting the variables in a widget.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $db_dictionary = $this->get_record();
    if( !is_null( $db_dictionary ) )
    {
      $this->set_variable( 'id_target', 0 );
      $this->set_variable( 'current_targets', $this->targets[ $db_dictionary->id ] );
    }
  }
}
