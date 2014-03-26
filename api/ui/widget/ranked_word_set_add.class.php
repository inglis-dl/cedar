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

    $word_class_name = lib::get_class_name( 'database\word' );
    
    // add items to the view
    $this->add_item( 'test_id', 'hidden' );
    $this->add_item( 'rank', 'enum', 'Rank' );

    $this->languages = $word_class_name::get_enum_values( 'language' );
    foreach( $this->languages as $language )
    {
      $description = 'Unknown';
      if( $language == 'en' )
        $description = 'English';
      elseif ( $language == 'fr' )
        $description = 'French';

      $this->add_item( 'word_' . $language . '_id', 'enum', 'Word (' . $description  . ')' );
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
    
    $word_class_name = lib::get_class_name( 'database\word' );
    $ranked_word_set_class_name = lib::get_class_name( 'database\ranked_word_set' );

    // this widget must have a parent, and it's subject must be a test
    if( is_null( $this->parent ) || 'test' != $this->parent->get_subject() )
      throw lib::create( 'exception\runtime',
        'Ranked Word Set widget must have a parent with test as the subject.', __METHOD__ );

    $db_test = $this->parent->get_record();
    $db_dictionary = $db_test->get_dictionary();

    if( is_null( $db_dictionary ) )
      throw lib::create( 'exception\notice',
      'The primary dictionary selection cannot be left blank.', __METHOD__ );
    
    $words = array();
    $dictionary_word_count = $db_dictionary->get_word_count();
    if( 0 < $dictionary_word_count && 
        0 == ( $dictionary_word_count % count( $this->languages ) ) )
    {
      foreach( $this->languages as $language )
      {
        // get word ids from all ranked word sets that have this test id
        $ranked_mod =  lib::create( 'database\modifier' );
        $ranked_mod->where( 'ranked_word_set.test_id', '=', $db_test->id );
        $word_id = 'word_' . $language . '_id';
        $word_ids_exclude = array();
        foreach( $ranked_word_set_class_name::select( $ranked_mod ) as $db_ranked_word_set )
        {
          $word_ids_exclude[] = $db_ranked_word_set->$word_id;
        }

        // get the words that can be selected from
        $word_mod = lib::create( 'database\modifier' );
        $word_mod->where( 'word.dictionary_id', '=', $db_dictionary->id );
        $word_mod->where( 'word.language', '=', $language );
        if( !empty( $word_ids_exclude ) )
        { 
          $word_mod->where( 'word.id', 'NOT IN', $word_ids_exclude );
        }  
        foreach( $word_class_name::select( $word_mod ) as $db_word )
        {
          $words[$language][$db_word->id] = $db_word->word;
        }
      }
    }
    else
    {     
      throw lib::create( 'exception\notice', 
        'The primary dictionary must contain at least one word of each language.',
        __METHOD__ );
    }

    //TODO remove this exception once the list class turns off adding
    if( empty( $words ) )
    {
      throw lib::create( 'exception\notice', 
        'There are no words left in the dictionary to create a ranked word set.',
        __METHOD__ );
    }

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

    foreach( $this->languages as $language )
    {
      $word_list = $words[$language];       
      $word_id = 'word_' . $language . '_id';
      $this->set_item( $word_id, key( $word_list ), true, $word_list, true );
    }
  }

  /** 
   * The languages.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected $languages = null;
}
