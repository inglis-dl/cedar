<?php
/**
 * dictionary_import_process.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: dictionary import words
 *
 * Import words into a dictionary.
 */
class dictionary_import_process extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'dictionary_import', 'process', $args );
  }

  /**
   * This method executes the operation's purpose.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();
    
    $db_dictionary = lib::create( 'database\dictionary', $this->get_argument( 'dictionary_id' ) );
    $word_list = $this->get_argument( 'word_list' );
    foreach( $word_list as $word => $language )
    {
      $db_new_word = lib::create( 'database\word' );
      $db_new_word->dictionary_id = $db_dictionary->id;
      $db_new_word->word = $word;
      $db_new_word->language = $language;    
      $db_new_word->save();
    }
    $this->get_record()->processed = true;
    $this->get_record()->save();
  }
}
