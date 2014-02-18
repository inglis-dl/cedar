<?php
/**
 * self_menu.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget self_menu
 */
class self_menu extends \cenozo\ui\widget\self_menu
{
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
    
    $this->exclude_list( array( 
      'event_type', 
      'cohort',
      'participant', 
      'ranked_word_set', 
      'service', 
      'site',
      'test_entry',
      'word' ) );
  }
}
