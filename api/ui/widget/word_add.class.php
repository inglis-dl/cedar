<?php
/**
 * word_add.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget word add
 */
class word_add extends \cenozo\ui\widget\base_view
{
  /**
   * Constructor
   *
   * Defines all variables which need to be set for the associated template.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'word', 'add', $args );
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

    // add items to the view
    $this->add_item( 'dictionary_id', 'hidden' );
    $this->add_item( 'language_id', 'enum', 'Language' );
    $this->add_item( 'word', 'string', 'Word' );
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

    $language_class_name = lib::get_class_name( 'database\language' );

    // this widget must have a parent, and it's subject must be a dictionary
    if( is_null( $this->parent ) || 'dictionary' != $this->parent->get_subject() )
      throw lib::create( 'exception\runtime',
        'Word widget must have a parent with dictionary as the subject.', __METHOD__ );

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'active', '=', true );
    $language_list = array();
    foreach( $language_class_name::select( $modifier ) as $db_language )
      $language_list[ $db_language->id ] = $db_language->name;

    // set the view's items
    $this->set_item( 'dictionary_id', $this->parent->get_record()->id );
    $this->set_item( 'language_id', '', false, $language_list );
    $this->set_item( 'word', '', true );
  }
}
