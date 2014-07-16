<?php
/**
 * ranked_word_set_list.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget ranked_word_set list
 */
class ranked_word_set_list extends \cenozo\ui\widget\base_list
{
  /**
   * Constructor
   *
   * Defines all variables required by the ranked_word_set list.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'ranked_word_set', $args );
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

    $language_class_name = lib::get_class_name( 'database\language' );
    $ranked_word_set_has_language_class_name =
      lib::get_class_name( 'database\ranked_word_set_has_language' );

    $this->add_column( 'rank', 'string', 'Rank', true );
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'id', 'IN',
      $ranked_word_set_has_language_class_name::get_distinct_values( 'language_id' ) );
    $this->language_list = $language_class_name::select( $modifier );
    foreach( $this->language_list as $db_language )
    {
      $this->add_column(
        'word_'. $db_language->code, 'string',
        'Word( '. $db_language->name . ' )', false );
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

    foreach( $this->get_record_list() as $db_ranked_word_set )
    {
      $row_array[ 'rank' ] = $db_ranked_word_set->rank;
      foreach( $this->language_list as $db_language )
      {
        $db_word = $db_ranked_word_set->get_word( $db_language );
        $row_array[ 'word_'. $db_language->code ] = $db_word->word;
      }
      $this->add_row( $db_ranked_word_set->id, $row_array );
    }
  }

  /**
   * The languages.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected $language_list = NULL;
}
