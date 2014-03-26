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
    
    $word_class_name = lib::get_class_name( 'database\word' );

    $this->add_column( 'rank', 'string', 'Rank', true );

    $this->languages = $word_class_name::get_enum_values( 'language' );
    foreach( $this->languages as $language )
    {   
      $this->add_column( 'word_' . $language, 'string', 'Word (' . 
        ($language == "en" ? 'English' : 'French')  . ')', false );
    }

    //TODO consider disabling the add button at the bottom of the list
    // once all the words in the primary dictionary have been used up
    // or do this in the parent "test" class
    //$this->set_addable()
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
      $row_array[ 'rank' ] = $record->rank;
      foreach( $this->languages as $language )
      {
        $word_id = 'word_' . $language . '_id';
        $db_word = lib::create( 'database\word', $record->$word_id );
        $row_array[ 'word_' . $language ] = $db_word ? $db_word->word : '';
      }

      $this->add_row( $record->id, $row_array );
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
