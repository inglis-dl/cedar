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

    // allow words to be moved within dictionaries that are referenced by a test
    $this->add_item( 'dictionary_id', 'enum', 'Dictionary' );
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

    $test_class_name = lib::get_class_name( 'database\test' );
    $word_class_name = lib::get_class_name( 'database\word' );

    $languages = $word_class_name::get_enum_values( 'language' );
    $languages = array_combine( $languages, $languages );
    $db_word = $this->get_record();

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'dictionary_id', '=', $db_word->dictionary_id );
    $modifier->or_where( 'variant_dictionary_id', '=', $db_word->dictionary_id );
    $modifier->or_where( 'intrusion_dictionary_id', '=', $db_word->dictionary_id );
    $modifier->or_where( 'mispelled_dictionary_id', '=', $db_word->dictionary_id );
    $db_test = current( $test_class_name::select( $modifier ) );

    // get the dictionaries that reference this test
    $dictionaries = array();
    $db_primary_dictionary = $db_test->get_dictionary();
    $db_variant_dictionary = $db_test->get_variant_dictionary();
    $db_intrusion_dictionary = $db_test->get_intrusion_dictionary();
    $db_mispelled_dictionary = $db_test->get_mispelled_dictionary();
    if( !is_null( $db_primary_dictionary ) )
      $dictionaries[$db_primary_dictionary->id] = $db_primary_dictionary->name;
    if( !is_null( $db_variant_dictionary ) )
      $dictionaries[$db_variant_dictionary->id] = $db_variant_dictionary->name;
    if( !is_null( $db_intrusion_dictionary ) )
      $dictionaries[$db_intrusion_dictionary->id] = $db_intrusion_dictionary->name;
    if( !is_null( $db_mispelled_dictionary ) )
      $dictionaries[$db_mispelled_dictionary->id] = $db_mispelled_dictionary->name;

    // set the view's items
    $this->set_item( 'word', $db_word->word, true );
    $this->set_item( 'language', $db_word->language, false, $languages );
    $this->set_item( 'dictionary_id', $db_word->get_dictionary()->id, true, $dictionaries );
  }
}
