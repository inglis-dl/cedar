#!/usr/bin/php
<?php
/**
 * This is a special script used when upgrading to version 1.1.2
 * This script should be run once and only once after running patch_database.sql
 * It finds test_entry_has_language entries and corrects them for bilingual classification
 * type tests and the single language only ranked_word type tests
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

    out( 'Determining classification type test languages ...' );

    // only classification type tests can have multiple languages
    $sql =
      'CREATE TEMPORARY TABLE tmp AS '.
      'SELECT '.
      'x.participant_id, '.
      'x.site_id, '.
      't1.id AS t1_id, t2.id AS t2_id, t3.id AS t3_id, '.
      't1.test_id,  '.
      'thl1.language_id AS l1_id, '.
      'thl2.language_id AS l2_id, '.
      'thl3.language_id AS l3_id '.
      'FROM ( '.
        'SELECT '.
        'a.participant_id, a.id AS a1_id, tmp1.assignment_id AS a2_id, a.site_id '.
        'FROM assignment a '.
        'LEFT JOIN ( '.
          'SELECT '.
          'participant_id, id AS assignment_id  '.
          'FROM assignment '.
          'GROUP BY participant_id '.
          'HAVING COUNT(*) = 2 '.
        ') AS tmp1 ON tmp1.participant_id = a.participant_id '.
        'WHERE IFNULL( tmp1.assignment_id, 0 ) != a.id '.
      ') AS x '.
      'JOIN test_entry t1 ON t1.assignment_id = x.a1_id '.
      'LEFT JOIN test_entry t2 ON t2.assignment_id = x.a2_id '.
      'AND t2.test_id = t1.test_id '.
      'LEFT JOIN test_entry t3 ON t3.participant_id = x.participant_id '.
      'AND t3.test_id = t1.test_id '.
      'JOIN test_entry_has_language thl1 ON thl1.test_entry_id = t1.id '.
      'LEFT JOIN test_entry_has_language thl2 ON thl2.test_entry_id = t2.id '.
      'LEFT JOIN test_entry_has_language thl3 ON thl3.test_entry_id = t3.id '.
      'WHERE t1.test_id IN ( '.
        'SELECT id FROM test '.
        'WHERE test_type_id = ( '.
          'SELECT id FROM test_type '.
          'WHERE name = "classification" '.
        ') '.
      ') '.
      'ORDER BY participant_id, test_id, l1_id, l2_id, l3_id';

    patch::my_execute( $db, $sql );

    $sql =
      'ALTER TABLE tmp ADD COLUMN id INT AUTO_INCREMENT UNIQUE FIRST';

    patch::my_execute( $db, $sql );

    $sql =
      'ALTER TABLE tmp ADD INDEX (participant_id)';

    patch::my_execute( $db, $sql );

    $sql =
      'SET @en_id = (SELECT id FROM qa_cenozo.language WHERE code = "en")';

    patch::my_execute( $db, $sql );

    $sql =
      'SET @fr_id = (SELECT id FROM qa_cenozo.language WHERE code = "fr")';

    patch::my_execute( $db, $sql );

    $sql =
      'SET @en_site_id = ( '.
        'SELECT id  '.
        'FROM ' . $cenozo . '.site '.
        'WHERE name = "McMaster" '.
        'AND service_id = ( '.
          'SELECT id  '.
          'FROM '. $cenozo . '.service '.
          'WHERE name = "cedar" ) '.
      ')';

    patch::my_execute( $db, $sql );

    $sql =
      'SET @en_site_id = ( '.
        'SELECT id  '.
        'FROM ' . $cenozo . '.site '.
        'WHERE name = "Sherbrooke" '.
        'AND service_id = ( '.
          'SELECT id  '.
          'FROM '. $cenozo . '.service '.
          'WHERE name = "cedar" ) '.
      ')';

    patch::my_execute( $db, $sql );

    $sql =
      'CREATE TEMPORARY TABLE tmp2 AS SELECT * FROM tmp';

    patch::my_execute( $db, $sql );

    // use site specific region language settings to determine test_entry languages
    $sql =
      'UPDATE tmp '.
      'JOIN tmp2 ON tmp2.id = tmp.id '.
      'SET '.
      'tmp.l1_id = IF( tmp2.site_id = @en_site_id, '.
                  'IF( tmp2.l1_id != @en_id, @en_id, tmp2.l1_id ), '.
                  'IF( tmp2.l1_id != @fr_id, @fr_id, tmp2.l1_id ) ), '.
      'tmp.l2_id = IF( tmp2.site_id = @en_site_id, '.
                  'IF( tmp2.l2_id != @en_id, @en_id, tmp2.l2_id ), '.
                  'IF( tmp2.l2_id != @fr_id, @fr_id, tmp2.l2_id ) ), '.
      'tmp.l3_id = IF( tmp2.site_id = @en_site_id, '.
                  'IF( tmp2.l3_id != @en_id, @en_id, tmp2.l3_id ), '.
                  'IF( tmp2.l3_id != @fr_id, @fr_id, tmp2.l3_id ) )';

    patch::my_execute( $db, $sql );

    $sql =
      'DROP TABLE tmp2';

    patch::my_execute( $db, $sql );
    $sql =
      'ALTER TABLE tmp DROP COLUMN id';

    patch::my_execute( $db, $sql );

    $sql =
      'CREATE TEMPORARY TABLE tmp2 AS '.
      'SELECT participant_id, COUNT(*) AS c '.
      'FROM tmp '.
      'GROUP BY participant_id';

    patch::my_execute( $db, $sql );

    $sql =
      'ALTER TABLE tmp2 ADD INDEX (participant_id)';

    patch::my_execute( $db, $sql );

    $sql =
      'CREATE TEMPORARY TABLE tmp3 AS '.
      'SELECT tmp.*, tmp2.c FROM tmp '.
      'JOIN tmp2 ON tmp2.participant_id = tmp.participant_id';

    patch::my_execute( $db, $sql );

    $sql =
      'ALTER TABLE tmp3 ADD INDEX (participant_id)';

    patch::my_execute( $db, $sql );

    $sql =
      'DROP TABLE tmp';

    patch::my_execute( $db, $sql );

    $sql =
      'DROP TABLE tmp2';

    patch::my_execute( $db, $sql );

    $sql =
      'CREATE TEMPORARY TABLE tmp4 AS '.
      'SELECT '.
      't1_id, t2_id, t3_id, '.
      'IF( l1_id = @en_id OR IFNULL( l2_id, 0 ) = @en_id OR IFNULL( l3_id, 0 ) = @en_id, '.
      ' @en_id, NULL ) AS l1_id, '.
      'IF( l1_id = @fr_id OR IFNULL( l2_id, 0 ) = @fr_id OR IFNULL( l3_id, 0 ) = @fr_id, '.
      '@fr_id, NULL ) AS l2_id, '.
      'c '.
      'FROM tmp3';

    patch::my_execute( $db, $sql );

    $sql =
      'DROP TABLE tmp3';

    patch::my_execute( $db, $sql );

    $sql =
      'DELETE tmp4.* FROM tmp4 '.
      'WHERE l1_id IS NULL OR l2_id IS NULL '.
      'AND c > 1';

    patch::my_execute( $db, $sql );

    $sql =
      'ALTER IGNORE TABLE tmp4 DROP COLUMN c';

    patch::my_execute( $db, $sql );

    $sql =
      'ALTER IGNORE TABLE tmp4 ADD UNIQUE INDEX uq_index ( t1_id, t2_id, t3_id, l1_id, l2_id )';

    patch::my_execute( $db, $sql );

    out( 'Updating classification type test_entry_has_language records ...' );

    $sql =
      'INSERT IGNORE INTO test_entry_has_language '.
      '(test_entry_id, language_id) '.
      'SELECT t1_id, l1_id '.
      'FROM tmp4';

    patch::my_execute( $db, $sql );

    $sql =
      'INSERT IGNORE INTO test_entry_has_language '.
      '(test_entry_id, language_id) '.
      'SELECT t1_id, l2_id '.
      'FROM tmp4 '.
      'WHERE l2_id IS NOT NULL';

    patch::my_execute( $db, $sql );

    $sql =
      'INSERT IGNORE INTO test_entry_has_language '.
      '(test_entry_id, language_id) '.
      'SELECT t2_id, l1_id '.
      'FROM tmp4 '.
      'WHERE t2_id IS NOT NULL';

    patch::my_execute( $db, $sql );

    $sql =
      'INSERT IGNORE INTO test_entry_has_language '.
      '(test_entry_id, language_id) '.
      'SELECT t2_id, l2_id '.
      'FROM tmp4 '.
      'WHERE t2_id IS NOT NULL '.
      'AND l2_id IS NOT NULL';

    patch::my_execute( $db, $sql );

    $sql =
      'INSERT IGNORE INTO test_entry_has_language '.
      '(test_entry_id, language_id) '.
      'SELECT t3_id, l1_id '.
      'FROM tmp4 '.
      'WHERE t3_id IS NOT NULL';

    patch::my_execute( $db, $sql );

    $sql =
      'INSERT IGNORE INTO test_entry_has_language '.
      '(test_entry_id, language_id) '.
      'SELECT t3_id, l2_id '.
      'FROM tmp4 '.
      'WHERE t3_id IS NOT NULL '.
      'AND l2_id IS NOT NULL';

    patch::my_execute( $db, $sql );

    $sql =
      'DROP TABLE tmp4';

    patch::my_execute( $db, $sql );

    out( 'Restricting ranked_word type tests to one language ...' );

    $sql =
      'CREATE TEMPORARY TABLE tmp AS '.
      'SELECT '.
      'x.participant_id, '.
      'x.site_id, '.
      't1.id AS t1_id, t2.id AS t2_id, t3.id AS t3_id, '.
      't1.test_id, '.
      'thl1.language_id AS l1_id, '.
      'thl2.language_id AS l2_id, '.
      'thl3.language_id AS l3_id '.
      'FROM ( '.
        'SELECT '.
        'a.participant_id, a.id AS a1_id, tmp1.assignment_id AS a2_id, a.site_id '.
        'FROM assignment a '.
        'LEFT JOIN ( '.
          'SELECT '.
          'participant_id, id AS assignment_id '.
          'FROM assignment '.
          'GROUP BY participant_id '.
          'HAVING COUNT(*) = 2 '.
        ') AS tmp1 ON tmp1.participant_id = a.participant_id '.
        'WHERE IFNULL( tmp1.assignment_id, 0 ) != a.id '.
      ') AS x '.
      'JOIN test_entry t1 ON t1.assignment_id = x.a1_id '.
      'LEFT JOIN test_entry t2 ON t2.assignment_id = x.a2_id '.
      'AND t2.test_id=t1.test_id '.
      'LEFT JOIN test_entry t3 ON t3.participant_id = x.participant_id '.
      'AND t3.test_id = t1.test_id '.
      'JOIN test_entry_has_language thl1 ON thl1.test_entry_id = t1.id '.
      'LEFT JOIN test_entry_has_language thl2 ON thl2.test_entry_id = t2.id '.
      'LEFT JOIN test_entry_has_language thl3 ON thl3.test_entry_id = t3.id '.
      'WHERE t1.test_id IN ( '.
        'SELECT id FROM test '.
        'WHERE test_type_id = ( '.
        'SELECT id FROM test_type  '.
        'WHERE name = "ranked_word" '.
        ') '.
      ') '.
      'ORDER BY participant_id, test_id, l1_id, l2_id, l3_id';

    patch::my_execute( $db, $sql );

    $sql =
      'ALTER TABLE tmp ADD COLUMN id INT AUTO_INCREMENT UNIQUE FIRST';

    patch::my_execute( $db, $sql );

    $sql =
      'ALTER TABLE tmp ADD INDEX (participant_id)';

    patch::my_execute( $db, $sql );

    $sql =
      'CREATE TEMPORARY TABLE tmp2 AS SELECT * FROM tmp';

    patch::my_execute( $db, $sql );

    // use site specific region language settings to determine test_entry language
    $sql =
      'UPDATE tmp '.
      'JOIN tmp2 ON tmp2.id = tmp.id '.
      'SET '.
      'tmp.l1_id = IF( tmp2.site_id = @en_site_id, '.
                  'IF( tmp2.l1_id != @en_id, @en_id, tmp2.l1_id ), '.
                  'IF( tmp2.l1_id != @fr_id, @fr_id, tmp2.l1_id ) ), '.
      'tmp.l2_id = IF( tmp2.site_id = @en_site_id, '.
                  'IF( tmp2.l2_id != @en_id, @en_id, tmp2.l2_id ), '.
                  'IF( tmp2.l2_id != @fr_id, @fr_id, tmp2.l2_id ) ), '.
      'tmp.l3_id = IF( tmp2.site_id = @en_site_id, '.
                  'IF( tmp2.l3_id != @en_id, @en_id, tmp2.l3_id ), '.
                  'IF( tmp2.l3_id != @fr_id, @fr_id, tmp2.l3_id ) )';

    patch::my_execute( $db, $sql );

    $sql =
      'DROP TABLE tmp2';

    patch::my_execute( $db, $sql );

    $sql =
      'ALTER TABLE tmp DROP COLUMN id';

    patch::my_execute( $db, $sql );

    $sql =
      'CREATE TEMPORARY TABLE tmp2 AS '.
      'SELECT participant_id, COUNT(*) AS c '.
      'FROM tmp '.
      'GROUP BY participant_id';

    patch::my_execute( $db, $sql );

    $sql =
      'ALTER TABLE tmp2 ADD INDEX (participant_id)';

    patch::my_execute( $db, $sql );

    $sql =
      'CREATE TEMPORARY TABLE tmp3 AS '.
      'SELECT tmp.*, tmp2.c FROM tmp '.
      'JOIN tmp2 ON tmp2.participant_id = tmp.participant_id';

    patch::my_execute( $db, $sql );

    $sql =
      'ALTER TABLE tmp3 ADD INDEX (participant_id)';

    patch::my_execute( $db, $sql );

    $sql =
      'DROP TABLE tmp';

    patch::my_execute( $db, $sql );

    $sql =
      'DROP TABLE tmp2';

    patch::my_execute( $db, $sql );

    // distill test_entry ids and languages into single row per participant
    $sql =
      'CREATE TEMPORARY TABLE tmp4 AS '.
      'SELECT '.
      't1_id, t2_id, t3_id, '.
      'IF( IFNULL( l1_id, 0 ) = @en_id OR IFNULL( l2_id, 0 ) = @en_id OR '.
      'IFNULL( l3_id, 0 ) = @en_id, @en_id, '.
      'IF( IFNULL( l1_id, 0 ) = @fr_id OR IFNULL( l2_id, 0 ) = @fr_id OR '.
      'IFNULL( l3_id, 0 ) = @fr_id, @fr_id, NULL ) ) AS l_id '.
      'FROM tmp3';

    patch::my_execute( $db, $sql );

    $sql =
      'DROP TABLE tmp3';

    patch::my_execute( $db, $sql );

    $sql =
      'ALTER IGNORE TABLE tmp4 ADD UNIQUE INDEX uq_index (t1_id,t2_id,t3_id,l_id)';

    patch::my_execute( $db, $sql );

    // check if a test_entry had an intrusion / variant
    // and if its language is different from that of the participant
    $sql =
      'CREATE TEMPORARY TABLE tmp5 AS '.
      'SELECT '.
      'DISTINCT '.
      'tmp4.t1_id, '.
      'w1.word AS w1, '.
      'w1.id AS w1_id, '.
      'w1.language_id AS l1_id, '.
      'tmp4.t2_id, '.
      'w2.word AS w2, '.
      'w2.id AS w2_id, '.
      'w2.language_id AS l2_id, '.
      'tmp4.t3_id, '.
      'w3.word AS w3, '.
      'w3.id AS w3_id, '.
      'w3.language_id AS l3_id, '.
      'tmp4.l_id '.
      'FROM tmp4 '.
      'LEFT JOIN test_entry_ranked_word terw1 ON terw1.test_entry_id = tmp4.t1_id '.
      'LEFT JOIN test_entry_ranked_word terw2 ON terw2.test_entry_id = tmp4.t2_id '.
      'LEFT JOIN test_entry_ranked_word terw3 ON terw3.test_entry_id = tmp4.t3_id '.
      'LEFT JOIN word w1 ON w1.id=terw1.word_id '.
      'LEFT JOIN word w2 ON w2.id=terw2.word_id '.
      'LEFT JOIN word w3 ON w3.id=terw3.word_id';

    patch::my_execute( $db, $sql );

    // delete rows with no language information from intrusions / variants
    $sql =
      'DELETE tmp5.* '.
      'FROM tmp5 '.
      'WHERE w1_id IS NULL AND w2_id IS NULL AND w3_id IS NULL';

    patch::my_execute( $db, $sql );

    // delete rows wherein all language ids match the target test_entry language id in tmp4
    $sql =
      'DELETE tmp5.* '.
      'FROM tmp5 '.
      'WHERE IFNULL( l1_id, l_id ) = l_id '.
      'AND IFNULL( l2_id, l_id ) = l_id '.
      'AND IFNULL( l3_id, l_id ) = l_id';

    patch::my_execute( $db, $sql );

    // where an adjudicate is meant to be NULL the progenitors'
    // cannot be used to override the test_entry language, so remove them
    $sql =
      'DELETE tmp5.* '.
      'FROM tmp5 '.
      'WHERE t3_id IS NOT NULL '.
      'AND w3 IS NULL';

    patch::my_execute( $db, $sql );

    $sql =
      'ALTER TABLE tmp5 ADD COLUMN id INT AUTO_INCREMENT UNIQUE FIRST';

    patch::my_execute( $db, $sql );

    // set the final words used in the adjudicate column
    // since ultimately the adjudicate is the correct response
    $sql =
      'CREATE TEMPORARY TABLE tmp6 AS SELECT * FROM tmp5';

    patch::my_execute( $db, $sql );

    $sql =
      'UPDATE tmp5 '.
      'JOIN tmp6 ON tmp6.id = tmp5.id '.
      'SET '.
        'tmp5.w3 = IFNULL( tmp6.w1, tmp6.w2 ), '.
        'tmp5.w3_id = IFNULL( tmp6.w1_id, tmp6.w2_id ), '.
        'tmp5.l3_id = IFNULL( tmp6.l1_id, tmp6.l2_id ) '.
      'WHERE tmp5.w3 IS NULL '.
      'AND ( tmp6.w1_id IS NULL OR tmp6.w2_id IS NULL )';

    patch::my_execute( $db, $sql );

    $sql =
      'DROP TABLE tmp6';

    patch::my_execute( $db, $sql );

    // for cognate words, set the adjudicate to be the word having the same language as the participant
    $sql =
      'CREATE TEMPORARY TABLE tmp6 AS '.
      'SELECT * FROM tmp5 '.
      'WHERE w1 = w2  '.
      'AND w1_id != w2_id '.
      'AND w3 IS NULL';

    patch::my_execute( $db, $sql );

    $sql =
      'UPDATE tmp5 '.
      'JOIN tmp6 ON tmp6.id = tmp5.id '.
      'SET '.
      'tmp5.w3 = IF( tmp6.l1_id = tmp6.l_id, tmp6.w1, tmp6.w2 ), '.
      'tmp5.w3_id = IF( tmp6.l1_id = tmp6.l_id, tmp6.w1_id, tmp6.w2_id ), '.
      'tmp5.l3_id = IF( tmp6.l1_id = tmp6.l_id, tmp6.l1_id, tmp6.l2_id )';

    patch::my_execute( $db, $sql );

    $sql =
      'DROP TABLE tmp6';

    patch::my_execute( $db, $sql );

    // handle all other non-cognate cases to set the adjudicate language
    // as the final decider

    $sql =
      'CREATE TEMPORARY TABLE tmp6 AS '.
      'SELECT * FROM tmp5 '.
      'WHERE w3 IS NULL';

    patch::my_execute( $db, $sql );

    // when there are two words of the same language AND w3 IS NULL, set
    // the language based ON those two words
    $sql =
      'UPDATE tmp5 '.
      'JOIN tmp6 ON tmp6.id=tmp5.id '.
      'SET '.
        'tmp5.w3 = tmp6.w1, '.
        'tmp5.w3_id = tmp6.w1_id, '.
        'tmp5.l3_id = tmp6.l1_id '.
      'WHERE tmp6.w1_id = tmp6.w2_id '.
      'AND tmp6.l1_id != tmp6.l_id';

    patch::my_execute( $db, $sql );

    $sql =
      'DROP TABLE tmp6';

    patch::my_execute( $db, $sql );

    // remove the remaining unset adjudicate rows
    // since we cannot make a decision on disparate progenitory language ids
    $sql =
      'DELETE tmp5.* '.
      'FROM tmp5 '.
      'WHERE w3 IS NULL';

    patch::my_execute( $db, $sql );

    $sql =
      'ALTER TABLE tmp5 DROP COLUMN id';

    patch::my_execute( $db, $sql );

    // collapse identical rows
    $sql =
      'ALTER IGNORE TABLE tmp5 ADD UNIQUE INDEX '.
      'uq_index(t1_id, w1, w1_id, l1_id, t2_id, w2, w2_id, l2_id, t3_id, w3, w3_id, l3_id, l_id)';

    patch::my_execute( $db, $sql );

    // update tmp4 from tmp5
    // collapse on t3_id, w3_id, determining most frequent language used
    // when multiple intrusions / variants exist
    $sql =
      'CREATE TEMPORARY TABLE tmp6 AS '.
      'SELECT x.t1_id, x.t2_id, x.t3_id, x.l_id, '.
      'IF( SUM(m) < 0.5 * SUM(t), 1, 0 ) AS do_swap '.
      'FROM ( '.
        'SELECT u.*, SUM( IF( l3_id = l_id, 1, 0 ) ) AS m, COUNT(*) AS t '.
        'FROM ( '.
          'SELECT tmp5.* '.
          'FROM tmp5 '.
          'WHERE t3_id IS NOT NULL '.
          'GROUP BY t3_id, w3_id '.
        ') AS u '.
        'GROUP BY t3_id, l3_id '.
      ') AS x '.
      'GROUP BY t3_id';

    patch::my_execute( $db, $sql );

    $sql =
      'UPDATE tmp5 '.
      'JOIN tmp6 ON tmp6.t1_id = tmp5.t1_id '.
      'AND tmp6.t2_id = tmp5.t2_id '.
      'AND tmp6.t3_id = tmp5.t3_id '.
      'SET tmp5.l_id = IF( tmp6.do_swap = 1, IF( tmp5.l_id = @en_id, @fr_id, @en_id ), tmp5.l_id )';

    patch::my_execute( $db, $sql );

    $sql =
      'DROP TABLE tmp6';

    patch::my_execute( $db, $sql );

    $sql =
      'CREATE TEMPORARY TABLE tmp6 AS '.
      'SELECT x.t1_id, x.t2_id, x.t3_id, x.l_id, '.
      'IF( SUM(m) < 0.5 * SUM(t), 1, 0 ) AS do_swap '.
      'FROM ( '.
        'SELECT u.*, SUM( IF( l3_id = l_id, 1, 0 ) ) AS m, COUNT(*) AS t '.
        'FROM ( '.
          'SELECT tmp5.* '.
          'FROM tmp5  '.
          'WHERE t3_id IS NULL '.
          'GROUP BY t1_id, w3_id '.
        ') AS u '.
        'GROUP BY t1_id, l3_id '.
      ') AS x '.
      'GROUP BY t1_id';

    patch::my_execute( $db, $sql );

    $sql =
      'UPDATE tmp5 '.
      'JOIN tmp6 ON tmp6.t1_id = tmp5.t1_id '.
      'AND tmp6.t2_id = tmp5.t2_id '.
      'AND tmp6.t3_id = tmp5.t3_id '.
      'SET tmp5.l_id = IF( tmp6.do_swap = 1, IF( tmp5.l_id = @en_id, @fr_id, @en_id ), tmp5.l_id )';

    patch::my_execute( $db, $sql );

    $sql =
      'DROP TABLE tmp6';

    patch::my_execute( $db, $sql );

    $sql =
      'UPDATE tmp4 '.
      'JOIN tmp5 ON tmp5.t1_id = tmp4.t1_id '.
      'AND tmp5.t2_id = tmp4.t2_id '.
      'SET tmp4.l_id = tmp5.l_id';

    patch::my_execute( $db, $sql );

    $sql =
      'DROP TABLE tmp5';

    patch::my_execute( $db, $sql );

    out( 'Updating ranked_word type test_entry_has_language records ...' );

    $sql =
      'INSERT IGNORE INTO test_entry_has_language '.
      '(test_entry_id, language_id) '.
      'SELECT t1_id, l_id '.
      'FROM tmp4';

    patch::my_execute( $db, $sql );

    $sql =
      'INSERT IGNORE INTO test_entry_has_language '.
      '(test_entry_id, language_id) '.
      'SELECT t2_id, l_id '.
      'FROM tmp4 '.
      'WHERE t2_id IS NOT NULL';

    patch::my_execute( $db, $sql );

    $sql =
      'INSERT IGNORE INTO test_entry_has_language '.
      '(test_entry_id, language_id) '.
      'SELECT t3_id, l_id '.
      'FROM tmp4 '.
      'WHERE t3_id IS NOT NULL';

    patch::my_execute( $db, $sql );

    out( 'Finished' );
  }
}

$patch = new patch();
$patch->execute();
