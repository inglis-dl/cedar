#!/usr/bin/php
<?php
/**
 * This is a special script used when upgrading to version 1.0.1
 * This script should be run once and only once after running patch_database.sql
 * It finds misadjudications and corrects them
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 */

ini_set( 'display_errors', '1' );
error_reporting( E_ALL | E_STRICT );
ini_set( 'date.timezone', 'US/Eastern' );

// utility functions
function out( $msg ) { printf( '%s: %s'."\n", date( 'Y-m-d H:i:s' ), $msg ); }
function error( $msg ) { out( sprintf( 'ERROR! %s', $msg ) ); }

class patch
{
  public function add_settings( $settings, $replace = false )
  {
    if( $replace )
    {
      $this->settings = $settings;
    }
    else
    {
      foreach( $settings as $category => $setting )
      {
        if( !array_key_exists( $category, $this->settings ) )
        {
          $this->settings[$category] = $setting;
        }
        else
        {
          foreach( $setting as $key => $value )
            if( !array_key_exists( $key, $this->settings[$category] ) )
              $this->settings[$category][$key] = $value;
        }
      }
    }
  }

  private static function my_execute( $connection, $sql )
  {
    $result = $connection->Execute( $sql );
    if( false === $result )
    {
      out( $connection->ErrorMsg() );
      out( $sql );
      die();
    }
  }

  private static function my_get_one( $connection, $sql )
  {
    $result = $connection->GetOne( $sql );
    if( false === $result )
    {
      out( $connection->ErrorMsg() );
      out( $sql );
      die();
    }
    return $result;
  }

  private static function my_get_all( $connection, $sql )
  {
    $result = $connection->GetAll( $sql );
    if( false === $result )
    {
      out( $connection->ErrorMsg() );
      out( $sql );
      die();
    }
    return $result;
  }

  public function execute()
  {
    $error_count = 0;
    $file_count = 0;

    out( 'Reading configuration parameters' );
    // fake server parameters
    $_SERVER['HTTPS'] = false;
    $_SERVER['HTTP_HOST'] = 'localhost';

    require_once '../../../web/settings.ini.php';
    require_once '../../../web/settings.local.ini.php';

    // include the application's initialization settings
    global $SETTINGS;
    $this->add_settings( $SETTINGS, true );
    unset( $SETTINGS );

    // include the framework's initialization settings
    require_once $this->settings['path']['CENOZO'].'/app/settings.local.ini.php';
    $this->add_settings( $settings );
    require_once $this->settings['path']['CENOZO'].'/app/settings.ini.php';
    $this->add_settings( $settings );

    if( !array_key_exists( 'general', $this->settings ) ||
        !array_key_exists( 'application_name', $this->settings['general'] ) )
      die( 'Error, application name not set!' );

    define( 'APPNAME', $this->settings['general']['application_name'] );
    define( 'SERVICENAME', $this->settings['general']['service_name'] );
    $this->settings['path']['CENOZO_API'] = $this->settings['path']['CENOZO'].'/api';
    $this->settings['path']['CENOZO_TPL'] = $this->settings['path']['CENOZO'].'/tpl';

    $this->settings['path']['API'] = $this->settings['path']['APPLICATION'].'/api';
    $this->settings['path']['DOC'] = $this->settings['path']['APPLICATION'].'/doc';
    $this->settings['path']['TPL'] = $this->settings['path']['APPLICATION'].'/tpl';

    // the web directory cannot be extended
    $this->settings['path']['WEB'] = $this->settings['path']['CENOZO'].'/web';

    foreach( $this->settings['path'] as $path_name => $path_value )
      define( $path_name.'_PATH', $path_value );
    foreach( $this->settings['url'] as $path_name => $path_value )
      define( $path_name.'_URL', $path_value );

    // open connection to the database
    out( 'Connecting to database' );
    require_once $this->settings['path']['ADODB'].'/adodb.inc.php';
    $db = ADONewConnection( $this->settings['db']['driver'] );
    $db->SetFetchMode( ADODB_FETCH_ASSOC );
    $database = sprintf( '%s%s',
                         $this->settings['db']['database_prefix'],
                         $this->settings['general']['application_name'] );

    $result = $db->Connect( $this->settings['db']['server'],
                            $this->settings['db']['username'],
                            $this->settings['db']['password'],
                            $database );
    if( false === $result )
    {
      error( 'Unable to connect, quiting' );
      die();
    }

    // the current wave of the study
    $visit = 1;
    $padded_visit = str_pad( $visit, 3, '0', STR_PAD_LEFT );

    // for joining to cenozo tables
    $sql =
      'SELECT unique_constraint_schema '.
      'FROM information_schema.referential_constraints '.
      'WHERE constraint_schema = DATABASE() '.
      'AND constraint_name = "fk_role_has_operation_role_id"';
    $cenozo = patch::my_get_one( $db, $sql );

    // get all the current recordings in the table
    // get the valid recording_names and test ids from the test table
    out( 'Getting current recording table data' );

    $sql =
      'SELECT DISTINCT id, recording_name '.
      'FROM test';
    $data_keys = patch::my_get_all( $db, $sql );
    $data_values = $data_keys;
    //convert to associative array
    array_walk( $data_keys, function( &$item ){ $item=$item['id'];});
    array_walk( $data_values, function( &$item ){ $item=$item['recording_name'];});
    $test_recording_names = array_combine( $data_keys, $data_values );

    // get all the participants currently in the recording table
    $sql =
      'SELECT DISTINCT p.uid '.
      'FROM '. $cenozo . '.participant p '.
      'JOIN recording r ON r.participant_id=p.id';

    $uid_recordings = patch::my_get_all( $db, $sql );
    array_walk( $uid_recordings, function( &$item ){ $item=$item['uid']; } );

    // get all the participant uid's from the comp-recordings visit 1 dir
    $glob_dir = COMP_RECORDINGS_PATH . '/' . $padded_visit . '/*';

    $dirs = array_filter( glob( $glob_dir ), 'is_dir' );
    $uid_dirs = array_map( function( $item ) { return substr($item,-7); }, $dirs );

    // only insert data that is needed
    $uids_insert = array_diff( $uid_dirs, $uid_recordings );

    // create an array of participant id, uid values
    out( 'Creating participant uid to id map' );

    $sql =
      'SELECT DISTINCT p.uid, p.id '.
      'FROM '. $cenozo . '.participant p '.
      'JOIN '. $cenozo . '.event e1 ON e1.participant_id = p.id '.
      'JOIN '. $cenozo . '.event_type et1 ON et1.id = e1.event_type_id '.
      'JOIN '. $cenozo . '.event e2 ON e2.participant_id = p.id '.
      'JOIN '. $cenozo . '.event_type et2 ON et2.id = e2.event_type_id '.
      'JOIN '. $cenozo . '.cohort c ON c.id = p.cohort_id '.
      'WHERE et1.name = "completed (Baseline Home)" '.
      'AND et2.name = "completed (Baseline Site)" '.
      'AND p.active = true '.
      'AND c.name = "comprehensive" '.
      'ORDER BY uid';

    $data_keys = patch::my_get_all( $db, $sql );
    $data_values = $data_keys;
    //convert to associative array
    array_walk( $data_values, function( &$item ){ $item=$item['id'];});
    array_walk( $data_keys, function( &$item ){ $item=$item['uid'];});
    $participant_map = array_combine( $data_keys, $data_values );

    // uncomment if file is required
    /*
    $my_file = fopen( '/tmp/cedar_comp_uid_list.txt', 'w' );
    foreach( $data_keys as $uid )
      fwrite( $my_file, $uid . '\n' );
    fclose( $my_file );
    */

    $values_count = 0;
    $values_limit = 200;
    $first = true;
    $values_array = array();
    $values = '';
    $total_count = 0;

    out( 'Generating ' . count( $test_recording_names )*count( $uids_insert ) .
         ' possible recording table rows'  );

    foreach( $uids_insert as $uid )
    {
      if( array_key_exists( $uid, $participant_map ) )
      {
        $participant_id = $participant_map[$uid];
        foreach( $test_recording_names as $test_id => $recording_name )
        {
          $filename = COMP_RECORDINGS_PATH . '/' . $padded_visit . '/' .
                      $uid . '/' . $recording_name . '.wav';

          if( file_exists( $filename ) )
          {
            $values .= sprintf( '%s( %d, %d, %d )',
                                $first ? '' : ', ',
                                $participant_id,
                                $test_id,
                                $visit );
            $first = false;
            $values_count++;
            $total_count++;
            if( $values_count++ >= $values_limit )
            {
              $values_array[] = $values;
              $values_count = 0;
              $first = true;
              $values = '';
            }
          }
        }
      }
    }

    if( $values_count < $values_limit && '' !== $values )
      $values_array[] = $values;

    out( 'Inserting ' . $total_count . ' recording table rows' );

    foreach( $values_array as $values )
    {
      $sql = sprintf(
        'INSERT IGNORE INTO recording ( participant_id, test_id, visit ) '.
        'VALUES %s', $values );
      patch::my_execute( $db, $sql );
    }

    out( 'Finished' );
  }
}

$patch = new patch();
$patch->execute();
