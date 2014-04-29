<?php
/**
 * word_view.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

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
    $this->add_item( 'word', 'string', 'Word' );
    $this->add_item( 'language', 'enum', 'Language' );
    $this->add_item( 'dictionary', 'string', 'Dictionary' );
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
    $db_word = $this->get_record();

    // set the view's items
    $this->set_item( 'word', $db_word->word, true );
    $this->set_item( 'language', $db_word->language, false, $languages );
    $this->set_item( 'dictionary', $db_word->get_dictionary()->name, true );
  }
}
