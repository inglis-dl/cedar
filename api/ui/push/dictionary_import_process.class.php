<?php
/**
 * dictionary_import_process.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: dictionary import words
 *
 * Import words into a dictionary.
 */
class dictionary_import_process extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'dictionary_import', 'process', $args );
  }

  /**
   * This method executes the operation's purpose.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $language_class_name = lib::get_class_name( 'database\language' );

    $db_dictionary = lib::create( 'database\dictionary', $this->get_argument( 'dictionary_id' ) );
    $db_dictionary_import = lib::create( 'database\dictionary_import', $this->get_argument( 'id' ) );

    $word_list = util::json_decode( $db_dictionary_import->serialization );

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'active', '=', true );
    $languages = NULL;
    foreach( $language_class_name::select( $modifier ) as $db_language )
    {
      $languages[ $db_language->code ] = $db_language->id;
    }

    foreach( $word_list as $key => $value )
    {
      $word = trim( $value[0] );
      $code = trim( $value[1] );

      if( !array_key_exists( $code, $languages ) )
        throw lib::create( 'exception\notice',
          'The language code "' . $code . '" for word "' . $word .
          '" is not a valid code for the active languages used by this service.',
          __METHOD__ );

      $db_new_word = lib::create( 'database\word' );
      $db_new_word->dictionary_id = $db_dictionary->id;
      $db_new_word->word = $word;

      $db_new_word->language_id = $languages[ $code ];
      $db_new_word->save();
    }

    $db_dictionary_import->processed = true;
    $db_dictionary_import->save();
  }
}
