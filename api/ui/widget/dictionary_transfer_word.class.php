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

    $dictionary_class_name = lib::get_class_name( 'database\dictionary' );
    $test_class_name = lib::get_class_name( 'database\test' );

    $this->sources = array();
    $this->targets = array();

    $db_test = $this->get_record()->get_owner_test();

    $current_sources = array();

    if( !is_null( $db_test ) )
    {
      $db_dictionary = $db_test->get_dictionary();

      // disallow word movement to or from the primary dictionary of ranked_word type tests
      if( !is_null( $db_dictionary ) && !$db_test->rank_words )
      {
        $current_sources[$db_dictionary->id] = $db_dictionary->name;
      }

      $db_intrusion_dictionary = $db_test->get_intrusion_dictionary();
      if( !is_null( $db_intrusion_dictionary ) )
        $current_sources[$db_intrusion_dictionary->id] = $db_intrusion_dictionary->name;

      $db_variant_dictionary = $db_test->get_variant_dictionary();
      if( !is_null( $db_variant_dictionary ) )
        $current_sources[$db_variant_dictionary->id] = $db_variant_dictionary->name;

      $db_mispelled_dictionary = $db_test->get_mispelled_dictionary();
      if( !is_null( $db_mispelled_dictionary ) )
        $current_sources[$db_mispelled_dictionary->id] = $db_mispelled_dictionary->name;
    }
    else
    {
      // if a dictionary is not assigned to a test
      // words can either be deleted or transferred to any other dictionary except
      // for the primary dictionary of ranked_word type tests
      //
      // Note that words cannot be transferred from a dictionary associated with
      // a test to one that is not
      $db_dictionary = $this->get_record();
      $current_sources[$db_dictionary->id] = $db_dictionary->name;

      // add in all the other dictionaries after the current dictionary
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'id', '!=', $db_dictionary->id );
      foreach( $dictionary_class_name::select( $modifier ) as $db_dictionary )
      {
        $db_test = $db_dictionary->get_owner_test();

        if( !is_null( $db_test ) && $db_dictionary->id == $db_test->dictionary_id
          && $db_test->rank_words ) continue;

        $current_sources[$db_dictionary->id] = $db_dictionary->name;
      }
    }

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
