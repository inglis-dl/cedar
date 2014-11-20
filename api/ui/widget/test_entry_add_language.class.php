<?php
/**
 * test_entry_add_language.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log;

/**
 * widget test_entry add_language
 */
class test_entry_add_language extends \cenozo\ui\widget\base_add_list
{
  /**
   * Constructor
   *
   * Defines all variables which need to be set for the associated template.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param string $name The name of the language.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry', 'language', $args );
  }

  /**
   * Overrides the language list widget's method.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_language_count( $modifier = NULL )
  {
    $language_class_name = lib::get_class_name( 'database\language' );

    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'language.active', '=', true );

    $existing_language_ids = array();
    foreach( $this->get_record()->get_language_list() as $db_language )
      $existing_language_ids[] = $db_language->id;

    if( 0 < count( $existing_language_ids ) )
      $modifier->where( 'id', 'NOT IN', $existing_language_ids );

    return $language_class_name::count( $modifier );
  }

  /**
   * Overrides the language list widget's method.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_language_list( $modifier = NULL )
  {
    $language_class_name = lib::get_class_name( 'database\language' );

    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'language.active', '=', true );

    $existing_language_ids = array();
    foreach( $this->get_record()->get_language_list() as $db_language )
      $existing_language_ids[] = $db_language->id;

    if( 0 < count( $existing_language_ids ) )
      $modifier->where( 'id', 'NOT IN', $existing_language_ids );

    return $language_class_name::select( $modifier );
  }
}
