<?php
/**
 * dictionary_delete.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: dictionary delete
 *
 * Delete a dictionary.
 */
class dictionary_delete extends \cenozo\ui\push\base_delete
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'dictionary', $args );
  }

  /**
   * Validate the operation.  If validation fails this method will throw a notice exception.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    $test_class_name = lib::get_class_name( 'database\test' );

    $db_dictionary = $this->get_record();
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'dictionary_id', '=', $db_dictionary->id );
    $modifier->or_where( 'variant_dictionary_id', '=', $db_dictionary->id );
    $modifier->or_where( 'intrusion_dictionary_id', '=', $db_dictionary->id );
    $modifier->or_where( 'mispelled_dictionary_id', '=', $db_dictionary->id );
    if( 0 < $test_class_name::count( $modifier ) )
      throw lib::create( 'exception\notice',
        'One or more tests currently use this dictionary. Unassign this dictionary '.
        'from any tests before attempting to delete it.', __METHOD__ );
    if( 0 < $db_dictionary->get_usage_count() )
      throw lib::create( 'exception\notice',
        'Dictionary cannot be deleted: one or more assignments use words from this dictionary.',
        __METHOD__ );
  }

  /**
   * Delete dictionary_import and word records associated with the dictionary.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    $dictionary_import_class_name = lib::get_class_name( 'database\dictionary_import' );
    $word_class_name = lib::get_class_name( 'database\word' );

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'dictionary_id', '=', $this->get_record()->id );
    $db_dictionary_import = current( $dictionary_import_class_name::select( $modifier ) );
    if( false !== $db_dictionary_import )
      $db_dictionary_import->delete();

    foreach( $word_class_name::select( $modifier ) as $db_word )
      $db_word->delete();
  }
}
