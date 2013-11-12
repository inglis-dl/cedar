<?php
/**
 * word_view.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace curry\ui\widget;
use cenozo\lib, cenozo\log, curry\util;

/**
 * widget word view
 */
class word_view extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'word', 'view', $args );
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
    $this->add_item( 'word', 'text', 'Word' );
    $this->add_item( 'language', 'string', 'Language' );
    $this->add_item( 'dictionary', 'text', 'Dictionary' );
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

    $word_class_name = lib::get_class_name( 'database\word' );
    $languages = $word_class_name::get_enum_values( 'language' );
    $languages = array_combine( $languages, $languages );

    // create enum arrays
    $num_words = $this->get_record()->get_dictionary()->get_word_count();

    // set the view's items
    $this->set_item( 'word', $this->get_record()->word, true );
    $this->set_item( 'language', $this->get_record()->language, false, $languages );
    $this->set_item( 'dictionary', $this->get_record()->get_dictionary()->name, true );
  }
}
