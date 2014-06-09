<?php
/**
 * word_list.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget word list
 */
class word_list extends \cenozo\ui\widget\base_list
{
  /**
   * Constructor
   *
   * Defines all variables required by the word list.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'word', $args );
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

    $this->add_column( 'word', 'string', 'Word', true );
    $this->add_column( 'language', 'string', 'Language', true );

    if( is_null( $this->parent ) )
      throw lib::create( 'exception\runtime',
        'Word list requires a dictionary view as parent', __METHOD__ );

    $dictionary_id = $this->parent->get_variable( 'dictionary_id' );
    $test_class_name = lib::get_class_name( 'database\test' );
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'dictionary_id', '=', $dictionary_id );
    $modifier->or_where( 'variant_dictionary_id', '=', $dictionary_id );
    $modifier->or_where( 'intrusion_dictionary_id', '=', $dictionary_id );
    $modifier->or_where( 'mispelled_dictionary_id', '=', $dictionary_id );
    $db_test = current( $test_class_name::select( $modifier ) );
    $this->word_total_column = $db_test->get_test_type()->name . '_word_total.total';

    $this->add_column( $this->word_total_column, 'number', 'Usage', true );
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

    foreach( $this->get_record_list() as $record )
    {
      $this->add_row( $record->id,
        array( 'word' => $record->word,
               'language' => $record->language,
               $this->word_total_column => $record->get_usage_count() ) );
    }
  }

  private $word_total_column = '';
}
