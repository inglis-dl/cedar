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
    
    $this->add_column( 'rank', 'number', 'Rank', true );

    $word_class_name = lib::get_class_name( 'database\word' );
    $languages = $word_class_name::get_enum_values( 'language' );
    foreach( $languages as $language )
    {   
      $this->add_column( 'word_' . $language, 'string', 'Word (' . 
        ($language == "en" ? 'English' : 'French')  . ')', false );
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
    
    $word_class_name = lib::get_class_name( 'database\word' );
    
    foreach( $this->get_record_list() as $record )
    {
      // assemble the row for this record
      $db_word_en = lib::create( 'database\word', $record->word_en_id );
      $db_word_fr = lib::create( 'database\word', $record->word_fr_id );

      $this->add_row( $record->id,
        array( 'rank' => $record->rank,
               'word_en' => $db_word_en ? $db_word_en->word : '',
               'word_fr' => $db_word_fr ? $db_word_fr->word : '') );
    }
  }
}
