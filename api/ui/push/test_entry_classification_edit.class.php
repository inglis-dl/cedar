<?php
/**
 * test_entry_classification_edit.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\push;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * push: test_entry_classification edit
 *
 * Edit a classification test entry.
 */
class test_entry_classification_edit extends \cenozo\ui\push\base_edit
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry_classification', $args );
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

    if( array_key_exists( 'word_candidate', $columns ) )
    {
      // empty entries are permitted for adjudicates
      $word_candidate = $columns['word_candidate'];
      if( '' !== $word_candidate )
      {
        $word_class_name = lib::get_class_name( 'database\word' );
        if( !$word_class_name::is_valid_word( $word_candidate ) )
          throw lib::create( 'exception\notice',
            'The word "'. $word_candidate . '" is not a valid word entry.',
            __METHOD__ );
      }
    }
  }

  /**
   * This method executes the operation's purpose.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   * @throws exception\notice
   * @throws exception\runtime
   */
  protected function execute()
  {
    parent::execute();

    $assignment_class_name = lib::get_class_name( 'database\assignment' );
    $region_site_class_name = lib::get_class_name( 'database\region_site' );
    $word_class_name = lib::get_class_name( 'database\word' );
    $session = lib::create( 'business\session' );

    $db_test_entry_classification = $this->get_record();
    $db_test_entry = $db_test_entry_classification->get_test_entry();
    $columns = $this->get_argument( 'columns' );

    if( array_key_exists( 'word_candidate', $columns ) )
    {
      $word_candidate = $columns['word_candidate'];
      if( '' === $word_candidate )
      {
        $db_test_entry_classification->word_id = NULL;
      }
      else
      {
        // assign a language to the word based on the original transcribers' language restrictions
        $db_user = NULL;
        $db_assignment = $db_test_entry->get_assignment();
        if( is_null( $db_assignment ) )
        {
          $modifier = lib::create( 'database\modifier' );
          $modifier->where( 'participant_id', '=', $db_test_entry->get_participant()->id );
          $modifier->limit( 1 );
          $db_assignment = current( $assignment_class_name::select( $modifier ) );
        }
        $db_user = $db_assignment->get_user();

        $data = NULL;
        $db_test = $db_test_entry->get_test();
        $db_language = NULL;
        $language_list = $db_user->get_language_list();
        if( !is_array( $language_list ) || is_null( $language_list ) ) $language_list = array();
        if( 0 == count( $language_list ) )
        {
          $db_service = $session->get_service();
          $db_site = $session->get_site();

          $modifier = lib::create( 'database\modifier' );
          $modifier->where( 'service_id', '=', $db_service->id );
          $modifier->where( 'site_id', '=', $db_site->id );
          $modifier->group( 'language_id' );

          // get the languages the site can process
          foreach( $region_site_class_name::select( $modifier ) as $db_region_site )
           $language_list[] = $db_region_site->get_language();
          
          // if all else fails use the service language
          if( 0 == count( $language_list ) ) 
            $language_list[] = $session->get_service()->get_language();
        }
        foreach( $language_list as $db_user_language )
        {
          $db_language = $db_user_language;
          $data = $db_test->get_word_classification( $word_candidate, NULL, $db_language );
          if( 'candidate' !== $data['classification'] ) break;
        }

        $classification = $data['classification'];
        $word = $data['word'];
        $word_id = $data['word_id'];

        if( '' !== $word_id )
        {
          // check if mispelled and throw an exception
          if( 'mispelled' === $classification )
            throw lib::create( 'exception\notice',
              'The word "'. $word . '" is mispelled and cannot be accepted.',
              __METHOD__ );

          $db_test_entry_classification->word_id = $word_id;
        }
        else
        {
          // the word isnt in the primary, variant, intrusion, or mispelled dictionaries
          // determine which dictionary to add the candidate to
          $db_dictionary = NULL;

          $is_FAS = preg_match( '/FAS/', $db_test->name );
          if( $is_FAS )
          {
            $is_intrusion = false;
            // any words that do not begin with 'f' or 'ph' are intrusions
            if( preg_match( '/FAS (f words)/', $db_test->name ) &&
                !( 0 === strpos( 'f', $word ) ||
                   0 === strpos( 'ph', $word ) ) )
            {
              $is_intrusion = true;
            }
            // any words that do not begin with 'a' are intrusions
            else if( preg_match( '/FAS (a words)/', $db_test->name ) &&
                     !( 0 === strpos( 'a', $word ) ) )
            {
              $is_intrusion = true;
            }
            // any words that do not begin with 's' or 'c' are intrusions
            else if( preg_match( '/FAS (s words)/', $db_test->name ) &&
                     !( 0 === strpos( 's', $word ) ||
                        0 === strpos( 'c', $word ) ) )
            {
              $is_intrusion = true;
            }

            if( $is_intrusion )
            {
              // get the test's intrusion dictionary and add it as an intrusion
              $db_dictionary = $db_test->get_intrusion_dictionary();
              if( is_null( $db_dictionary ) )
                throw lib::create( 'exception\notice',
                  'Trying to add the word "'. $word . '" to a non-existant intrusion dictionary. '.
                  'Assign an intrusion dictionary for the '. $db_test->name . ' test.',
                  __METHOD__ );
            }
          }

          if( is_null( $db_dictionary ) )
          {
            // get the test's variant dictionary and add it as a variant
            $db_dictionary = $db_test->get_variant_dictionary();
            if( is_null( $db_dictionary ) )
              throw lib::create( 'exception\notice',
                'Trying to add the word "'. $word . '" to a non-existant variant dictionary. '.
                'Assign a variant dictionary for the '. $db_test->name . ' test.',
                __METHOD__ );
          }

          $session->acquire_semaphore();
          $db_new_word = lib::create( 'database\word' );
          $db_new_word->dictionary_id = $db_dictionary->id;
          $db_new_word->word = $word;
          if( is_null( $db_language ) )
          {
            $db_language = $session->get_service()->get_language();
          }
          $db_new_word->language_id = $db_language->id;
          $db_new_word->save();
          $db_test_entry_classification->word_id = $word_class_name::db()->insert_id();
          $session->release_semaphore();
        }
      }
      $db_test_entry_classification->save();
    }

    $assignment_manager = lib::create( 'business\assignment_manager' );
    $assignment_manager::complete_test_entry( $db_test_entry );
  }
}
