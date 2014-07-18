<?php
/**
 * dictionary_new_word.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: dictionary new_word
 */
class dictionary_new_word extends \cenozo\ui\push\base_record
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @word public
   */
  public function __construct( $args )
  {
    parent::__construct( 'dictionary', 'transfer_word', $args );
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

    $this->get_record()->transfer_word(
      $this->get_argument( 'id_list' ),
      $this->get_argument( 'destination_id' ) );
  }
}
