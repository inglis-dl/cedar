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

  /**
   * Finish setting the variables in a widget.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $operation_class_name = lib::get_class_name( 'database\operation' );
    $utilities = $this->get_variable( 'utilities' );

    // insert the word transfer widget into the utilities
    $db_operation = $operation_class_name::get_operation( 'widget', 'dictionary', 'transfer_word' );
    if( lib::create( 'business\session' )->is_allowed( $db_operation ) )
      $utilities[] = array( 'heading' => 'Word Transfer',
                            'type' => 'widget',
                            'subject' => 'dictionary',
                            'name' => 'transfer_word' );

    $this->set_variable( 'utilities', $utilities );
  }
}
