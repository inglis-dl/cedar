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
    $this->add_item( 'words', 'constant', 'Number of words' );
    $this->add_item( 'description', 'text', 'Description' );

    // create the word sub-list widget
    $this->word_list = lib::create( 'ui\widget\word_list', $this->arguments );
    $this->word_list->set_parent( $this );
    $this->word_list->set_heading( 'Dictionary words' );

    $operation_class_name = lib::get_class_name( 'database\operation' );
    $db_operation = $operation_class_name::get_operation( 'push', 'dictionary', 'import' );
    if( lib::create( 'business\session' )->is_allowed( $db_operation ) ) 
    {   
      $this->add_action( 'import_words', 'Import', $db_operation,
        'Import words from a CSV file into the dictionary' );
      $this->set_variable( 'import_words', true );
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

    // create enum arrays
    $dictionarys = array();
    foreach( $dictionary_class_name::select() as $db_dictionary )
      if( $db_dictionary->id != $this->get_record()->id )
        $dictionarys[$db_dictionary->id] = $db_dictionary->name;

    // set the view's items
    $this->set_item( 'name', $this->get_record()->name, true );
    $this->set_item( 'words', $this->get_record()->get_word_count() );
    $this->set_item( 'description', $this->get_record()->description );

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
