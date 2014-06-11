<?php
/**
 * test_classify_word.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\pull;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * Class for word classify pull operations.
 *
 */
class test_classify_word extends \cenozo\ui\pull\base_record
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Pull arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test', 'classify_word', $args );
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

    $language_class_name = lib::get_class_name( 'database\language' );

    $db_test = $this->get_record();
    $word_candidate = $this->get_argument( 'word_candidate' );
    $language = $this->get_argument( 'language', NULL );
    $db_language = NULL;
    if( !is_null( $language ) )
    {
      $db_language = $language_class_name::get_unique_record( 'code', $language );
    }

    $data = $db_test->get_word_classification( $word_candidate, $db_language );
    $this->data = $data['classification'];
  }

  /**
   * Data returned in JSON format.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_data_type()
  {
    return "json";
  }
}
