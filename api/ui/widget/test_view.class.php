<?php
/**
 * test_view.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget test view
 */
class test_view extends \cenozo\ui\widget\base_view
{
  /**
   * Constructor
   *
   * Defines all variables which need to be set for the associated template.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test', 'view', $args );
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

    $db_test = $this->get_record();

    $this->add_item( 'name', 'string', 'Name' );
    $this->add_item( 'rank', 'number', 'Test Order' );
    $this->add_item( 'strict', 'constant', 'Strict' );
    $this->add_item( 'rank_words', 'constant', 'Rank Words' );
    $this->add_item( 'dictionary_id', 'enum', 'Primary Dictionary' );
    $this->add_item( 'recording_name', 'string', 'Recording Name' );

    if( !$db_test->strict )
    {
      $this->add_item( 'variant_dictionary_id', 'enum', 'Variant Dictionary' );
      $this->add_item( 'intrusion_dictionary_id', 'enum', 'Intrusion Dictionary' );
      $this->add_item( 'mispelled_dictionary_id', 'enum', 'Mispelled Dictionary' );
    }

    if( $db_test->rank_words )
    {
      $this->add_item( 'words', 'constant', 'Number of ranked word sets' );

      // create the ranked_word_list sub-list widget
      $this->ranked_word_set_list = lib::create( 'ui\widget\ranked_word_set_list', $this->arguments );
      $this->ranked_word_set_list->set_parent( $this );
      $this->ranked_word_set_list->set_heading( 'Ranked Word Sets' );
    }
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

    $dictionary_class_name = lib::get_class_name( 'database\dictionary' );

    $db_test = $this->get_record();
    $this->set_variable( 'test_id', $db_test->id );

    // set the view's items
    $this->set_item( 'name', $db_test->name, true );
    $this->set_item( 'rank', $db_test->rank, true );
    $this->set_item( 'strict',
      $db_test->strict ? "yes: variants and intrusions are ignored" :
                        "no: variants and intrusions are recorded", true );

    $this->set_item( 'rank_words',
      $db_test->rank_words ? "yes: primary dictionary words must be ranked" :
                            "no: primary dictionary words are not ranked", true );

    $dictionary_list = array();
    foreach( $dictionary_class_name::select() as $db_dictionary )
       $dictionary_list[$db_dictionary->id] = $db_dictionary->name;

    $this->set_item( 'dictionary_id', $db_test->dictionary_id, false, $dictionary_list );
    $this->set_variable( 'dictionary_id', $db_test->dictionary_id );
    if( !$db_test->strict )
    {
      $this->set_item( 'variant_dictionary_id',
        $db_test->variant_dictionary_id, false, $dictionary_list );
      $this->set_item( 'intrusion_dictionary_id',
        $db_test->intrusion_dictionary_id, false, $dictionary_list );
      $this->set_item( 'mispelled_dictionary_id',
        $db_test->mispelled_dictionary_id, false, $dictionary_list );
    }

    if( $db_test->rank_words )
    {
      $this->set_item( 'words', $db_test->get_ranked_word_set_count() );
      try
      {
        $this->ranked_word_set_list->process();
        $this->set_variable( 'ranked_word_set_list', $this->ranked_word_set_list->get_variables() );
      }
      catch( \cenozo\exception\permission $e ) {}
    }

    $this->set_item( 'recording_name', $db_test->recording_name, true );
  }

  /**
   * The ranked_word_set list widget.
   * @var ranked_word_set_list
   * @access protected
   */
  protected $ranked_word_set_list = NULL;
}
