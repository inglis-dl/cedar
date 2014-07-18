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
class dictionary_transfer_word extends \ui\widget\base_transfer_list
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

//TODO: override prepare and other parent/grand parent methods as required
// so that the possible destination records are defined

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
