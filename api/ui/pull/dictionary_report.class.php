<?php
/**
 * dictionary_report.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace curry\ui\pull;
use cenozo\lib, cenozo\log, curry\util;

/**
 * Mailout required report data.
 * 
 * @abstract
 */
class dictionary_report extends \cenozo\ui\pull\base_report
{
  /**
   * Constructor
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'dictionary', $args );
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

    // check to see if a dictionary-specific template exists for this report
    $db_dictionary = lib::create( 'database\dictionary', $this->get_argument( 'id' ) );
    $filename = sprintf( '%s/report/%s_%s.xls',
                         DOC_PATH,
                         $this->get_full_name(),
                         $db_dictionary->name );
    if( file_exists( $filename ) ) $this->report = lib::create( 'business\report', $filename );
  }

  /**
   * Builds the report.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $this->report->set_orientation( 'landscape' );

    $dictionary_class_name = lib::get_class_name( 'database\dictionary' );
    $word_class_name = lib::get_class_name( 'database\word' );

    $db_dictionary = lib::create( 'database\dictionary', $this->get_argument( 'id' ) );
    $word_count = $db_dictionary->get_word_count();

    // loop through all the words
    $word_mod = lib::create( 'database\modifier' );
    $word_mod->where( 'word.dictionary_id', '=', $db_dictionary->id );
    $word_mod->order( 'word.language' );
    $contents = array();
    foreach( $word_class_name::select( $word_mod ) as $db_word )
    {
      $contents[] = array( $db_word->word, $db_word->language );
    }

    $this->add_title( strtoupper($db_dictionary->name) . 
                      ' Dictionary ( ' . $word_count . ' entries )' );

    // create the content and header arrays using the data
    $header = array( 'Word', 'Language'  );
    $this->add_table( NULL, $header, $contents );
  }
}
