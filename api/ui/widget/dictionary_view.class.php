<?php
/**
 * dictionary_view.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

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

    $word_class_name = lib::get_class_name( 'database\word' );
    $this->languages = $word_class_name::get_enum_values( 'language' );
    
    foreach( $this->languages as $language )
    {
      $description = 'unknown language';
      if( $language == 'en' )
        $description = 'English';
      elseif ( $language == 'fr' )
        $description = 'French';

      $description = 'Number of ' . $description . ' words';
      $this->add_item( 'words_' . $language, 'constant', $description );
    }   
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

    // set the view's items
    $db_dictionary = $this->get_record();
    $this->set_item( 'name', $db_dictionary->name, true );

    foreach( $this->languages as $language )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'language','=', $language );
      $this->set_item( 'words_' . $language, $db_dictionary->get_word_count( $modifier ) );
    }

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
   * The word list widget.
   * @var word_list
   * @access protected
   */
  protected $word_list = NULL;

  /** 
   * The languages.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected $languages = null;

}
