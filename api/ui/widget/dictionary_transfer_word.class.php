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
   * Overrides the word list widget's method.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function prepare()
  {
    parent::prepare();

    $test_class_name = lib::get_class_name( 'database\test' );
    $this->sources = array();
    $this->targets = array();

    foreach( $test_class_name::select() as $db_test )
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

  public function setup()
  {
    $this->set_record(
      lib::create( 'database\dictionary', current( array_keys( $this->sources ) ) ) );

    parent::setup();

    $db_dictionary = $this->get_record();
    if( !is_null( $db_dictionary ) )
    {
      $this->set_variable( 'id_target', 0 );
      $this->set_variable( 'current_targets', $this->targets[ $db_dictionary->id ] );
    }
  }

  /**
   * Overrides the word list widget's method.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_word_count( $modifier = NULL )
  {
    $word_class_name = lib::get_class_name( 'database\word' );

    $existing_word_ids = array();
    foreach( $this->get_record()->get_word_list() as $db_word )
      $existing_word_ids[] = $db_word->id;

    if( 0 < count( $existing_word_ids ) )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'id', 'NOT IN', $existing_word_ids );
    }

    return $word_class_name::count( $modifier );
  }

  /**
   * Overrides the word list widget's method.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_word_list( $modifier = NULL )
  {
    $word_class_name = lib::get_class_name( 'database\word' );

    $existing_word_ids = array();
    foreach( $this->get_record()->get_word_list() as $db_word )
      $existing_word_ids[] = $db_word->id;

    if( 0 < count( $existing_word_ids ) )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'id', 'NOT IN', $existing_word_ids );
    }

    return $word_class_name::select( $modifier );
  }
}
