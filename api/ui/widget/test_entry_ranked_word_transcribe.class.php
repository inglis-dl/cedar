<?php
/**
 * test_entry_ranked_word_transcribe.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\ui\widget;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * widget test_entry_ranked_word transcribe
 */
class test_entry_ranked_word_transcribe extends base_transcribe
{
  /**
   * Constructor.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'test_entry_ranked_word', $args );
  }

  /**
   * Sets up the operation with any pre-execution instructions that may be necessary.
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $db_test_entry = $this->parent->get_record();
    $db_test = $db_test_entry->get_test();
    $db_participant = $db_test_entry->get_assignment()->get_participant();
    $region_site_class_name = lib::get_class_name( 'database\region_site' );

    $db_language = $db_participant->get_language();
    if( is_null( $db_language ) )
    {
      $session = lib::create( 'business\session' );
      $db_user = $session->get_user();
      $db_service = $session->get_service();
      $db_site = $session->get_site();

      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'service_id', '=', $db_service->id );
      $modifier->where( 'site_id', '=', $db_site->id );
      $modifier->group( 'language_id' );

      // get the languages the site can process
      $site_languages = array();
      foreach( $region_site_class_name::select( $modifier ) as $db_region_site )
        $site_languages[] = $db_region_site->get_language()->id;

      // get the languages the user can process
      $user_languages = array();
      foreach( $db_user->get_language_list() as $db_user_language )
      {
        if( in_array( $db_user_language->id, $site_languages ) )
          $user_languages[] = $db_user_language->id;
      }
      if( 0 == count( $user_languages ) )
        $db_language = lib::create( 'business\session' )->get_service()->get_language();
      else
        $db_language = lib::create( 'database\language', $user_languages[0] );
    }

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'ranked_word_set_id', '!=', NULL );
    $modifier->order( 'ranked_word_set.rank' );
    $entry_data = array();

    // get the primary word entries
    foreach( $db_test_entry->get_test_entry_ranked_word_list( $modifier ) as
             $db_test_entry_ranked_word )
    {
      $db_ranked_word_set = $db_test_entry_ranked_word->get_ranked_word_set();
      $selection = $db_test_entry_ranked_word->selection;
      $db_ranked_word_set_word = $db_ranked_word_set->get_word( $db_language );
      $db_word = $db_test_entry_ranked_word->get_word();
      $classification = '';

      if( !is_null( $db_word ) && 'variant' == $selection )
      {
        $classification = 'variant';
      }

      $entry_data[] =
        array(
          'id' => $db_test_entry_ranked_word->id,
          'ranked_word_set_id' => $db_ranked_word_set->id,
          'ranked_word_set_word' => $db_ranked_word_set_word->word,
          'word_id' => is_null( $db_word ) ? '' : $db_word->id,
          'word' => is_null( $db_word ) ? '' : $db_word->word,
          'selection' => is_null( $selection ) ? '' : $selection,
          'classification' => $classification );
    }

    // now get the intrusions
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'ranked_word_set_id', '=', NULL );
    foreach( $db_test_entry->get_test_entry_ranked_word_list( $modifier ) as
             $db_test_entry_ranked_word )
    {
      $db_word = $db_test_entry_ranked_word->get_word();

      $entry_data[] =
        array(
          'id' => $db_test_entry_ranked_word->id,
          'ranked_word_set_id' => '',
          'ranked_word_set_word' => '',
          'word_id' => is_null( $db_word ) ? '' : $db_word->id,
          'word' => is_null( $db_word ) ? '' : $db_word->word,
          'selection' => '',
          'classification' => is_null( $db_word ) ? '' : 'intrusion' );
    }

    $this->set_variable( 'entry_data', $entry_data );
  }
}
