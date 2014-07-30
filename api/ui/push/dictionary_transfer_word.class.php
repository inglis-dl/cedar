<?php
/**
 * dictionary_transfer_word.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: dictionary transfer_word
 */
class dictionary_transfer_word extends \cenozo\ui\push\base_record
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @word public
   */
  public function __construct( $args )
  {
    parent::__construct( 'dictionary', 'transfer_word', $args );
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

    $word_class_name = lib::get_class_name( 'database\user_time' );

    $id_list = $this->get_argument( 'id_list', array() );
    if( 0 == count( $id_list ) )
      throw lib::create( 'exception\notice',
        'No word ids have been provided to initiate a dictionary word transfer', __METHOD__ );

    $db_dictionary = $this->get_record();

    // if the destination is the null dictionary (delete)
    // the usage count for each word must be 0
    $id = $this->get_argument( 'id_destination', NULL );
    $db_destination_dictionary =
      is_null( $id ) || 0 == $id ? NULL : lib::create( 'database\dictionary', $id );

    log::debug( 'destination dictionary: ');
    log::debug( $db_destination_dictionary );

     if( is_null( $db_destination_dictionary ) )
     {
       $db_test = $db_dictionary->get_owner_test();
       $word_total_view_name = is_null( $db_test ) ? NULL :
         $db_test->get_test_type()->name . '_word_total';
       foreach( $id_list as $id )
       {
         log::debug( 'word it in id_list: '. $id );
         $db_word = lib::create( 'database\word', $id );
         if( 0 < $db_word->get_usage_count( $word_total_view_name ) )
           throw lib::create( 'exception\notice',
             'The word "'. $db_word->word . '" is in use and cannot be deleted', __METHOD__ );
       }
     }
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

    $id = $this->get_argument( 'id_destination', NULL );
    $db_destination_dictionary =
      is_null( $id ) || 0 == $id ? NULL : lib::create( 'database\dictionary', $id );

    $this->get_record()->transfer_word(
      $this->get_argument( 'id_list' ), $db_destination_dictionary  );
  }
}
