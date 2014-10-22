<?php
/**
 * word_new.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: word new
 *
 * Create a new word.
 */
class word_new extends \cenozo\ui\push\base_new
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'word', $args );
  }

  /**
   * Validate the operation.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    $columns = $this->get_argument( 'columns' );

    // if there is a word, validate it
    if( array_key_exists( 'word', $columns ) )
      $word = strtolower( $columns['word'] );

    // does this dictionary belong to a test?
    if( array_key_exists( 'dictionary_id', $columns ) &&
        array_key_exists( 'language_id', $columns ) )
    {
      $test_class_name = lib::get_class_name( 'database\test' );
      $dictionary_id = $columns['dictionary_id'];
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'dictionary_id', '=', $dictionary_id );
      $modifier->or_where( 'variant_dictionary_id', '=', $dictionary_id );
      $modifier->or_where( 'intrusion_dictionary_id', '=', $dictionary_id );
      $modifier->or_where( 'mispelled_dictionary_id', '=', $dictionary_id );
      $db_test = current( $test_class_name::select( $modifier ) );
      if( !is_null( $db_test ) )
      {
        // is the word + language present in any of the other dictionaries?
        $types = array(
          'dictionary_id',
          'variant_dictionary_id',
          'intrusion_dictionary_id',
          'mispelled_dictionary_id' );
        $id_list = array();
        foreach( $types as $type )
        {
          if( !is_null( $db_test->$type ) && $dictionary_id != $db_test->$type )
            $id_list[] = $db_test->$type;
        }
        if( 0 < count( $id_list ) )
        {
          $word_class_name = lib::get_class_name( 'database\word' );
          $modifier = lib::create( 'database\modifier' );
          $modifier->where( 'dictionary_id','IN', $id_list );
          $modifier->where( 'word', '=', $word );
          $modifier->where( 'language_id', '=', $columns['language_id'] );
          $db_word_list = $word_class_name::select( $modifier );
          if( 0 < count( $db_word_list ) )
          {
            $message =
              'The word "' . $word . '" is not unique among the following '.
              $db_test->name . ' test dictionarys: ';
            $dictionary_names = array();
            foreach( $db_word_list as $db_word )
              $dictionary_names[] = $db_word->get_dictionary()->name;
            $message .= implode( ', ', $dictionary_names ) . '.';
            throw lib::create( 'exception\notice',
              $message, __METHOD__ );
          }
        }
      }
    }
  }
}
