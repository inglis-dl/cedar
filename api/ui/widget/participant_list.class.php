<?php
/**
 * participant_list.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget participant list
 */
class participant_list extends \cenozo\ui\widget\base_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the participant list.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', $args );
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

    $this->add_column( 'uid', 'string', 'UID', true );
  }
  
  /**
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $participant_class_name = lib::get_class_name( 'database\participant' );
    $session = lib::create( 'business\session' );

    foreach( $this->get_record_list() as $record )
    {
      $columns = array(
        'uid' => $record->uid ? $record->uid : '(none)' );

      $this->add_row( $record->id, $columns );
    }
  }

  /**
   * Overrides the parent class method to restrict participant list based on user's role
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_record_count( $modifier = NULL )
  {
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $db_role = lib::create( 'business\session' )->get_role();
    if( 'typist' == $db_role->name )    
    { 
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    }
    else if( 'administrator' == $db_role->name )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    }
    log::debug( parent::determine_record_count( $modifier ) );
    return parent::determine_record_count( $modifier );
  }
  
  /**
   * Overrides the parent class method to restrict participant list based on user's role
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
    // if typist == current users role
    // setup a record_list array (deferrals)
    // else if admin, some other array of participant (adjudicates)
  public function determine_record_list( $modifier = NULL )
  {
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $db_role = lib::create( 'business\session' )->get_role();
    if( 'typist' == $db_role->name )    
    { 
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    }
    else if( 'administrator' == $db_role->name )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    }

    return parent::determine_record_list( $modifier );
  }
}
