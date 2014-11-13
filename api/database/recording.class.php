<?php
/**
 * recording.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\database;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * recording: record
 */
class recording extends \cenozo\database\record
{
  /**
   * Gets the file associated with this recording
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_filename()
  {
    $padded_visit = str_pad( $this->visit, 3, '0', STR_PAD_LEFT );
    $filename = sprintf( '%s/%s/%s.wav',
                         $padded_visit,
                         $this->get_participant()->uid,
                         $this->get_test()->recording_name );

    return $filename;
  }

  /**
   * Builds the recording list based on recording files found in the COMP_RECORDING path (if set)
   *
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   */
  public static function update_recording_list()
  {
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $test_class_name = lib::get_class_name( 'database\test' );

    // make sure that all recordings on disk have a corresponding database record
    if( is_dir( COMP_RECORDINGS_PATH ) )
    {
      // create new recording record based on this interview
      $db_recording = lib::create( 'database\recording' );

      $glob_search = sprintf( '%s/*/*/*.wav', COMP_RECORDINGS_PATH );

      $values = '';
      $values_array = array();
      $first = true;
      $values_count = 0;
      $values_limit = 200;
      foreach( glob( $glob_search ) as $filename )
      {
        // get the path components from the filename
        $parts = array_reverse( preg_split( '#/#', $filename ) );
        if( 3 <= count( $parts ) )
        {
          $name = trim( str_replace( '.wav', '', $parts[0] ) );
          $uid = trim( $parts[1] );
          $visit = intval( ltrim( $parts[2], '0' ) );

          $db_test = $test_class_name::get_unique_record( 'recording_name', $name );
          if( is_null( $db_test ) ) continue;

          $modifier = lib::create( 'database\modifier' );
          $modifier->where( 'uid', '=', $uid );
          $modifier->limit( 1 );
          $db_participant = current( $participant_class_name::select( $modifier ) );
          if( false !== $db_participant )
          {
            $values .= sprintf( '%s( %d, %d, %d )',
                                $first ? '' : ', ',
                                $db_participant->id,
                                $db_test->id,
                                $visit );
            $first = false;
            $values_count++;
            if( $values_count++ >= $values_limit )
            {
              $values_count = 0;
              $first = true;
              $values = '';
              $values_array[] = $values;
            }
          }
        }
      }

      if( $values_count < $values_limit && '' !== $values )
        $values_array[] = $values;

      foreach( $values_array as $values )
      {
        static::db()->execute( sprintf(
          'INSERT IGNORE INTO recording ( participant_id, test_id, visit ) '.
          'VALUES %s', $values ) );
      }
    }
  }
}
