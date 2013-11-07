<?php
/**
 * user_add_cohort.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cenozo\ui\widget;
use cenozo\lib, cenozo\log;

/**
 * widget user add_cohort
 */
class user_add_cohort extends \cenozo\ui\widget\base_add_list
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param string $name The name of the cohort.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'user', 'cohort', $args );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   
  protected function setup()
  {
    parent::setup();

    $user_class_name = lib::get_class_name( 'database\user' );

    $db_service = lib::create( 'database\service', $this->get_argument( 'service_id' ) );
    $cohort_id_list = array();
    foreach( $db_service->get_cohort_list() as $db_cohort ) $cohort_id_list[] = $db_cohort->id;

    $this->set_variable( 'cohort_list', $cohort_list );
  }*/

  /**
   * Overrides the cohort list widget's method.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_cohort_count( $modifier = NULL )
  {
    $cohort_class_name = lib::get_class_name( 'database\cohort' );

    $existing_cohort_ids = array();
    foreach( $this->get_record()->get_cohort_list() as $db_cohort )
      $existing_cohort_ids[] = $db_cohort->id;

    if( 0 < count( $existing_cohort_ids ) )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'id', 'NOT IN', $existing_cohort_ids );
    }

    return $cohort_class_name::count( $modifier );
  }

  /**
   * Overrides the cohort list widget's method.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_cohort_list( $modifier = NULL )
  {
    $cohort_class_name = lib::get_class_name( 'database\cohort' );

    $existing_cohort_ids = array();
    foreach( $this->get_record()->get_cohort_list() as $db_cohort )
      $existing_cohort_ids[] = $db_cohort->id;

    if( 0 < count( $existing_cohort_ids ) )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'id', 'NOT IN', $existing_cohort_ids );
    }

    return $cohort_class_name::select( $modifier );
  }
}
