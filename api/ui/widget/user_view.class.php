<?php
/**
 * user_view.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log;

/**
 * widget user view
 */
class user_view extends \cenozo\ui\widget\user_view
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

    // create the cohort sub-list widget
    $this->cohort_list = lib::create( 'ui\widget\cohort_list', $this->arguments );
    $this->cohort_list->set_parent( $this );
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

    try
    {
      $this->cohort_list->process();
      $this->set_variable( 'cohort_list', $this->cohort_list->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}
  }

  /**
   * Overrides the cohort list widget's method.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @cohort protected
   */
  public function determine_cohort_count( $modifier = NULL )
  {
    $cohort_class_name = lib::get_class_name( 'database\cohort' );
    $session = lib::create( 'business\session' );
    if( NULL == $modifier ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'user_has_cohort.user_id', '=', $this->get_record()->id );
    return $cohort_class_name::count( $modifier );
  }

  /**
   * Overrides the cohort list widget's method.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @cohort protected
   */
  public function determine_cohort_list( $modifier = NULL )
  {
    $cohort_class_name = lib::get_class_name( 'database\cohort' );
    $session = lib::create( 'business\session' );
    if( NULL == $modifier ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'user_has_cohort.user_id', '=', $this->get_record()->id );
    return $cohort_class_name::select( $modifier );
  }

  /**
   * The cohort list widget.
   * @var cohort_list
   * @access protected
   */
  protected $cohort_list = NULL;
}
