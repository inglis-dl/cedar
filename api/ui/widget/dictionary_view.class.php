<?php
/**
 * dictionary_view.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace curry\ui\widget;
use cenozo\lib, cenozo\log, curry\util;

/**
 * widget dictionary view
 */
class dictionary_view extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'dictionary', 'view', $args );
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

    // create an associative array with everything we want to display about the dictionary
    $this->add_item( 'name', 'string', 'Name' );
    $this->add_item( 'words_en', 'constant', 'Number of English words' );
    $this->add_item( 'words_fr', 'constant', 'Number of French words' );
    $this->add_item( 'description', 'text', 'Description' );

    // create the word sub-list widget
    $this->word_list = lib::create( 'ui\widget\word_list', $this->arguments );
    $this->word_list->set_parent( $this );
    $this->word_list->set_heading( 'Dictionary words' );

    $operation_class_name = lib::get_class_name( 'database\operation' );
    $db_operation = $operation_class_name::get_operation( 'widget', 'dictionary', 'import' );
    if( lib::create( 'business\session' )->is_allowed( $db_operation ) ) 
    {   
      $this->add_action( 'import', 'Import', $db_operation,
        'Import words from a CSV file into the dictionary' );
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
    
    $db_dictionary = $this->get_record();
    $modifier_en = lib::create( 'database\modifier' );
    $modifier_en->where('language','=','en');
    $modifier_fr = lib::create( 'database\modifier' );
    $modifier_fr->where('language','=','fr');

    // set the view's items
    $this->set_item( 'name', $db_dictionary->name, true );
    $this->set_item( 'words_en', $db_dictionary->get_word_count( $modifier_en ) );
    $this->set_item( 'words_fr', $db_dictionary->get_word_count( $modifier_fr ) );
    $this->set_item( 'description', $db_dictionary->description );
    $this->set_variable( 'dictionary_id', $db_dictionary->id );

    try 
    {   
      $this->word_list->process();
      $this->set_variable( 'word_list', $this->word_list->get_variables() );
    }   
    catch( \cenozo\exception\permission $e ) {}
  }

  /** 
   * The dictionary list widget.
   * @var word_list
   * @access protected
   */
  protected $word_list = NULL;
}
