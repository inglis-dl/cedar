  <?php
  /**
   * word_edit.class.php
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @filesource
   */

  namespace curry\ui\push;
  use cenozo\lib, cenozo\log, curry\util;

  /**
   * push: word edit
   *
   * Edit a word.
   */
  class word_edit extends \cenozo\ui\push\base_edit
  {
    /**
     * Constructor.
     * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'word', $args );
  }
}
