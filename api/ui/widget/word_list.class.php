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
    if( is_null( $this->parent ) )
      throw lib::create( 'exception\runtime',
        'Word list requires a dictionary view as parent', __METHOD__ );

    parent::prepare();

    $test_class_name = lib::get_class_name( 'database\test' );

    $this->add_column( 'word', 'string', 'Word', true );
    $this->add_column( 'language.name', 'string', 'Language', true );

    $dictionary_id = $this->parent->get_variable( 'id' );
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'dictionary_id', '=', $dictionary_id );
    $modifier->or_where( 'variant_dictionary_id', '=', $dictionary_id );
    $modifier->or_where( 'intrusion_dictionary_id', '=', $dictionary_id );
    $modifier->or_where( 'mispelled_dictionary_id', '=', $dictionary_id );
    $modifier->limit( 1 );
    $db_test = current( $test_class_name::select( $modifier ) );

    if( false !== $db_test )
    {
      $test_type_name = $db_test->get_test_type()->name;

      if( $test_type_name != 'confirmation' &&
          !($test_type_name == 'ranked_word' && $db_test->dictionary_id == $dictionary_id) )
      {
        $this->word_total_view_name = $test_type_name . '_word_total';
        $this->word_total_column = $this->word_total_view_name . '.total';
        $this->add_column( $this->word_total_column, 'number', 'Usage', true );
      }
    }
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

    if( !is_null( $this->word_total_column ) && !is_null( $this->word_total_view_name ) )
    {
      foreach( $this->get_record_list() as $db_word )
      {
        $this->add_row( $db_word->id, array(
          'word' => $db_word->word,
          'language.name' => $db_word->get_language()->name,
          $this->word_total_column => $db_word->get_usage_count( $this->word_total_view_name ) ) );
      }
    }
    else
    {
      foreach( $this->get_record_list() as $db_word )
      {
        $this->add_row( $db_word->id, array(
          'word' => $db_word->word,
          'language.name' => $db_word->get_language()->name ) );
      }
    }
  }

  /**
   * Name of the word usage column in the word list based on test type
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access private
   */
  private $word_total_column = NULL;

  /**
   * Name of the word count view based on test type
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access private
   */
  private $word_total_view_name = NULL;
}
