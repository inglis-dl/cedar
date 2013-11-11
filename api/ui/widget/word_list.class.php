<?php
/**
 * word_list.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace curry\ui\widget;
use cenozo\lib, cenozo\log, curry\util;

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
    
    $this->add_column( 'word', 'string', 'Word', false );
    $this->add_column( 'language', 'string', 'Language', false );
    $this->add_column( 'dictionary', 'string', 'Dictionary', false );
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
      // get the dictionary
      $db_dictionary = lib::create( 'database\dictionary', $record->dictionary_id );

      $this->add_row( $record->id,
        array( 'word' => $record->word,
               'language' => $record->language,
               'dictionary' => $db_dictionary->name ) );
    }
  }
}
