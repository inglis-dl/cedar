#!/usr/bin/php
<?php
/**
 * This is a special script used when upgrading to version 1.1.2 for the CLSA
 * This script should be run once and only once after running patch_database.sql
 * It overrides test_entry_has_language entries where participant language is null
 * and uses the assignment site dominant language instead of the service default languag
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

    // for joining to cenozo tables
    $sql =
      'SELECT unique_constraint_schema '.
      'FROM information_schema.referential_constraints '.
      'WHERE constraint_schema = DATABASE() '.
      'AND constraint_name = "fk_role_has_operation_role_id"';
    $cenozo = patch::my_get_one( $db, $sql );


    $sql =
      'SET @service_id = (SELECT id FROM ' . $cenozo . '.service WHERE name = "cedar")';
    patch::my_execute( $db, $sql );

    $sql =
      'SET @en_id = (SELECT id FROM ' . $cenozo . '.language WHERE code = "en")';
    patch::my_execute( $db, $sql );

    $sql =
      'SET @fr_id = (SELECT id FROM ' . $cenozo . '.language WHERE code = "fr")';
    patch::my_execute( $db, $sql );

    $sql =
      'SET @en_site_id = ('.
      'SELECT id '.
      'FROM ' . $cenozo . '.site '.
      'WHERE name = "McMaster" '.
      'AND service_id = @service_id )';
    patch::my_execute( $db, $sql );

    $sql =
      'SET @fr_site_id = ('.
      'SELECT id '.
      'FROM ' . $cenozo . '.site '.
      'WHERE name = "Sherbrooke"'.
      'AND service_id = @service_id )';
    patch::my_execute( $db, $sql );


    $sql =
      'INSERT IGNORE INTO test_entry_has_language '.
      '(test_entry_id, language_id) '.
      'SELECT t.id, IFNULL( p.language_id, '.
        'IF( a.site_id = @en_site_id, @en_id, @fr_id ) ) AS language_id '.
      'FROM test_entry t '.
      'JOIN assignment a ON a.id = t.assignment_id '.
      'JOIN ' . $cenozo . '.participant p ON p.id = a.participant_id';
    patch::my_execute( $db, $sql );

    $sql =
      'INSERT IGNORE INTO test_entry_has_language '.
      '(test_entry_id, language_id) '.
      'SELECT t.id, IFNULL( p.language_id, '.
        'IF( IFNULL( ps.site_id, @en_site_id ) = @en_site_id, @en_id, @fr_id ) ) AS language_id '.
      'FROM test_entry t '.
      'JOIN ' . $cenozo . '.participant p ON p.id = t.participant_id '.
      'LEFT JOIN ' . $cenozo . '.participant_site ps ON ps.participant_id = p.id '.
      'AND ps.service_id = @service_id';
    patch::my_execute( $db, $sql );

    $sql =
      'INSERT IGNORE INTO test_entry_has_language '.
      '(test_entry_id, language_id) '.
      'SELECT DISTINCT tec.test_entry_id, w.language_id '.
      'FROM test_entry_classification tec '.
      'JOIN word w ON w.id = tec.word_id ';
    patch::my_execute( $db, $sql );

    out( 'Finished' );
  }
}

$patch = new patch();
$patch->execute();
