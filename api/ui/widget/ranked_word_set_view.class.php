<?php
/**
 * ranked_word_set_view.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget ranked_word_set view
 */
class ranked_word_set_view extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'ranked_word_set', 'view', $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $language_class_name = lib::get_class_name( 'database\language' );

    // view items to the view
    $this->add_item( 'rank', 'enum', 'Rank' );

    $language_mod = lib::create( 'database\modifier' );
    $language_mod->where( 'active', '=', true );
    $this->language_list = $language_class_name::select( $language_mod );
    foreach( $this->language_list as $db_language )
    {
      $this->add_item( 'word_' . $db_language->code, 'enum', 'Word (' . $db_language->name . ')' );
    }
  }

  /**
   * Finish setting the variables in a widget.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $word_class_name = lib::get_class_name( 'database\word' );

    $db_ranked_word_set = $this->get_record();
    $db_test = $db_ranked_word_set->get_test();
    $db_dictionary = $db_test->get_dictionary();
    $word_list = array();
    $dictionary_word_count = $db_dictionary->get_word_count();
    if( 0 < $dictionary_word_count &&
        0 == ( $dictionary_word_count % count( $this->language_list ) ) )
    {
      foreach( $this->language_list as $db_language )
      {
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'dictionary_id', '=', $db_dictionary->id );
        $modifier->where( 'language_id', '=', $db_language->id );
        foreach( $word_class_name::select( $modifier ) as $db_word )
        {
          $word_list[ $db_language->id ][ $db_word->id ] = $db_word->word;
        }
      }
    }
    else
      throw lib::create( 'exception\notice',
        'The primary dictionary must contain at least one word of each language.',
         __METHOD__ );

    $num_ranks = $db_test->get_ranked_word_set_count();
    $ranks = array();
    for( $rank = 1; $rank <= ( $num_ranks + 1 ); $rank++ ) $ranks[] = $rank;
    $ranks = array_combine( $ranks, $ranks );

    // set the view's items
    $this->set_item( 'rank', $db_ranked_word_set->rank, true, $ranks );

    foreach( $this->language_list as $db_language )
    {
      $word_list = $word_list[ $db_language->id ];
      $db_word = $db_ranked_word_set->get_word( $db_language );
      $this->set_item( 'word_' . $db_language->code,
        is_null( $db_word ) ? '' : $db_word->id, true, $word_list );
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
