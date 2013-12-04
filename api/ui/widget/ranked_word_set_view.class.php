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
    
    // view items to the view
    $this->add_item( 'rank', 'enum', 'Rank' );

    $word_class_name = lib::get_class_name( 'database\word' );
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
   * @throws exception\notice
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $record = $this->get_record();
    $db_test = $record->get_test();
    $db_dictionary = $db_test->get_dictionary();
    $word_class_name = lib::get_class_name( 'database\word' );
    $words = array();
    $dictionary_word_count = $db_dictionary->get_word_count();
    if( $dictionary_word_count > ( count( $this->languages ) -1 ) )
    {
      foreach( $this->languages as $language )
      {
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'word.dictionary_id', '=', $db_dictionary->id );
        $modifier->where( 'word.language', '=', $language );
        foreach( $word_class_name::select( $modifier ) as $db_word )
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

    $num_ranked_word_sets = $db_test->get_ranked_word_set_count();
    $ranks = array();
    for( $rank = 1; $rank <= $num_ranked_word_sets; $rank++ ) $ranks[] = $rank;
    $ranks = array_combine( $ranks, $ranks );
     
    // set the view's items
    $this->set_item( 'rank', $record->rank, true, $ranks );

    foreach( $this->languages as $language )
    {
      $word_list = $words[$language]; 
      $this->set_item( 'word_' . $language . '_id', '', false, $word_list );
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
