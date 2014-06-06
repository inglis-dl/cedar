<?php
/**
 * ranked_word_set_add.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget ranked_word_set add
 */
class ranked_word_set_add extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'ranked_word_set', 'add', $args );
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

    // add items to the view
    $this->add_item( 'test_id', 'hidden' );
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
   * @throws exception\runtime
   * @throws exception\notice
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $ranked_word_set_class_name = lib::get_class_name( 'database\ranked_word_set' );
    $word_class_name = lib::get_class_name( 'database\word' );

    // this widget must have a parent, and it's subject must be a test
    if( is_null( $this->parent ) || 'test' != $this->parent->get_subject() )
      throw lib::create( 'exception\runtime',
        'Ranked Word Set widget must have a parent with test as the subject.', __METHOD__ );

    $db_test = $this->parent->get_record();
    $db_dictionary = $db_test->get_dictionary();

    if( is_null( $db_dictionary ) )
      throw lib::create( 'exception\notice',
        'The primary dictionary must be set for the ' . $db_test->name . ' test.', __METHOD__ );

    $word_list = array();
    $dictionary_word_count = $db_dictionary->get_word_count();
    if( 0 < $dictionary_word_count &&
        0 == ( $dictionary_word_count % count( $this->language_list ) ) )
    {
      foreach( $this->language_list as $db_language )
      {
        // get word ids from all ranked word sets that have this test id
        $modifier =  lib::create( 'database\modifier' );
        $modifier->where( 'test_id', '=', $db_test->id );
        $word_ids_exclude = array();
        foreach( $ranked_word_set_class_name::select( $modifier ) as $db_ranked_word_set )
        {
          $db_word = $db_ranked_word_set->get_word( $db_language );
          if( !is_null( $db_word ) )
            $word_ids_exclude[] = $db_word->id;
        }

        // get the primary dictionary's words that can be selected from
        $word_mod = lib::create( 'database\modifier' );
        $word_mod->where( 'dictionary_id', '=', $db_dictionary->id );
        $word_mod->where( 'language_id', '=', $db_language->id );

        if( 0 < count( $word_ids_exclude ) )
          $word_mod->where( 'id', 'NOT IN', $word_ids_exclude );

        foreach( $word_class_name::select( $word_mod ) as $db_word )
          $word_list[ $db_language->id ][ $db_word->id ] = $db_word->word;
      }
    }
    else
      throw lib::create( 'exception\notice',
        'The primary dictionary must contain at least one word of each language.',
        __METHOD__ );

    if( 0 == count( $word_list ) )
      throw lib::create( 'exception\notice',
        'There are no words left in the dictionary to create a ranked word set.',
        __METHOD__ );

    $num_ranks = $db_test->get_ranked_word_set_count();
    $ranks = array();
    for( $rank = 1; $rank <= ( $num_ranks + 1 ); $rank++ ) $ranks[] = $rank;
    $ranks = array_combine( $ranks, $ranks );
    end( $ranks );
    $last_rank_key = key( $ranks );
    reset( $ranks );

    // set the view's items
    $this->set_item( 'test_id', $db_test->id );
    $this->set_item( 'rank', $last_rank_key, true, $ranks );

    foreach( $this->language_list as $db_language )
    {
      $word_list = $word_list[ $db_language->id ];
      $this->set_item( 'word_' . $db_language->code, key( $word_list ), true, $word_list, true );
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
