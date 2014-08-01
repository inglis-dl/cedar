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
    if( false == $result )
    {
      error( 'Unable to connect, quiting' );
      die();
    }

    $total = $db->GetOne(
      'SELECT COUNT(*) FROM assignment_total atot ' .
      'JOIN assignment a ON atot.assignment_id=a.id '.
      'WHERE atot.deferred=0 '.
      'AND atot.completed=6'
      );

    out( sprintf( 'Processing %d assignments', $total ) );
    $base = 0;
    $increment = 1000;
    $assignment_id_cache = array();
    $adjudicate_delete_count = 0;
    $assignment_open_count = 0;
    $assignment_closed_count = 0;
    $test_entry_modify_count = 0;

    while( $base < $total )
    {
      $rows = $db->GetAll( sprintf(
      'SELECT assignment_id AS id, IF( IFNULL( a.end_datetime, 0 ) != 0, 1, 0) AS closed '.
      'FROM assignment_total atot ' .
      'JOIN assignment a ON a.id=atot.assignment_id '.
      'WHERE atot.deferred = 0 '.
      'AND atot.completed = 6 '.
      'ORDER BY id LIMIT %d, %d', $base, $increment ) );

      foreach( $rows as $index => $row )
      {
        $a1_id = $row['id'];
        if( in_array( $a1_id, $assignment_id_cache ) ) continue;

        // get the sibling assignment
        $p_id = $db->GetOne( sprintf(
         'SELECT participant_id FROM assignment '.
         'WHERE id = %d', $a1_id ) );

        if( $p_id != 2181 ) continue; 
        $a2_id = $db->GetOne( sprintf(
         'SELECT id FROM assignment '.
         'WHERE id != %d '.
         'AND participant_id=%d', $a1_id, $p_id ) );

        if( is_null( $a2_id) || in_array( $a2_id, $assignment_id_cache ) ) continue;

        $a2_closed = $db->GetOne( sprintf(
         'SELECT IF( IFNULL( end_datetime, 0 ) != 0, 1, 0) FROM assignment '.
         'WHERE id = %d ', $a2_id ) );
        $a1_closed = $row['closed'];

        // get all the test entries
        $tests = $db->GetAll( sprintf(
          'SELECT te.id, te.test_id, te.assignment_id, tt.name, '.
          'IFNULL(te.adjudicate, -1) AS adjudicate, '.
          'IFNULL( te.audio_status, "NULL") AS audio_status, '.
          'IFNULL(te.participant_status, "NULL") AS participant_status '.
          'FROM test_entry te '.
          'JOIN test t ON t.id = te.test_id '.
          'JOIN test_type tt ON tt.id = t.test_type_id '.
          'WHERE assignment_id IN ( %d, %d ) '.
          'ORDER BY te.test_id, te.assignment_id', $a1_id, $a2_id ) );
        if( count($tests) != 12 )
        {
          error( sprintf( 'Not enough tests for assignment ids %d %d', $a1_id, $a2_id ) );
          die();
        }

        // loop over the paired test_entries
        $i = 0;
        $close_assignment = true;
        $adjudicate_assignment = false;
        do
        {
          $t1 = $tests[$i++];
          $t2 = $tests[$i++];
          $type = $t1['name'];
          $test_id = $t1['test_id'];

          // get any adjudication entries for the test and participant
          $adjudicates = $db->GetAll( sprintf(
            'SELECT id, completed, update_timestamp, IFNULL(adjudicate, -1) AS adjudicate, '.
            'IFNULL( audio_status, "NULL") AS audio_status, '.
            'IFNULL(participant_status, "NULL") AS participant_status '.
            'FROM test_entry '.
            'WHERE participant_id = %d '.
            'AND test_id = %d ORDER BY update_timestamp DESC', $p_id, $test_id ) );
          $adjudicate_num = count( $adjudicates );
          if( $adjudicate_num > 1 )
          {
            out( sprintf( 'found multiple (%d) adjudicates for test %d of type %s',
              $adjudicate_num, $test_id, $type ) );
            for( $j = 1; $j < $adjudicate_num; $j++ )
            {
              $db->Execute( sprintf(
                'DELETE FROM test_entry_%s WHERE test_entry_id=%d', $type, $adjudicates[$j]['id'] ) );
              $db->Execute( sprintf(
                'DELETE FROM test_entry WHERE id=%d', $adjudicates[$j]['id'] ) );
              $adjudicate_delete_count++;
            }
          }

          $adj_id = 0 < $adjudicate_num ? $adjudicates[0]['id'] : NULL;
          $adj_adjudicate = 0 < $adjudicate_num ? $adjudicates[0]['adjudicate'] : NULL;
          $adj_completed = 0 < $adjudicate_num ? $adjudicates[0]['completed'] : NULL;

          // check if the audio or participant status are different
          $match = true;
          if( $t1['audio_status'] != $t2['audio_status'] || $t1['participant_status'] != $t2['participant_status'] )
          {
            $match = false;            
          }
          else
          {
            // check if the entries for the current test are different
            if( $type == 'classification' || $type == 'alpha_numeric' )
            {
              $sql_match = sprintf(
              'SELECT COUNT(*) FROM ( '.
              'SELECT rank, word_id FROM ( '.
              'SELECT t1.rank, t1.word_id FROM test_entry_%s t1 '.
              'WHERE t1.test_entry_id = %d '.
              'UNION ALL '.
              'SELECT t2.rank, t2.word_id FROM test_entry_%s t2 '.
              'WHERE t2.test_entry_id = %d ) AS tmp '.
              'GROUP BY rank, word_id HAVING COUNT(*) = 1 ) AS tmp', $type, $t1['id'], $type, $t2['id'] );
              $match = 0 == $db->GetOne( $sql_match );
            }
            else if( $type == 'confirmation' )
            {
              $sql_match = sprintf(
              'SELECT COUNT(*) FROM ( '.
              'SELECT confirmation FROM ( '.
              'SELECT t1.confirmation FROM test_entry_confirmation t1 '.
              'WHERE t1.test_entry_id = %d '.
              'UNION ALL '.
              'SELECT t2.confirmation FROM test_entry_confirmation t2 '.
              'WHERE t2.test_entry_id = %d ) AS tmp '.
              'GROUP BY confirmation HAVING COUNT(*) = 1 ) AS tmp', $t1['id'], $t2['id'] );
              $match = 0 == $db->GetOne( $sql_match );
            }
            else if( $type == 'ranked_word' )
            {
              $sql_match = sprintf(
              'SELECT COUNT(*) FROM ( '.
              'SELECT ranked_word_set_id, word_id, selection FROM ( '.
              'SELECT t1.ranked_word_set_id, t1.word_id, t1.selection FROM test_entry_ranked_word t1 '.
              'WHERE t1.test_entry_id = %d '.
              'AND t1.selection IS NOT NULL '.
              'UNION ALL '.
              'SELECT t2.ranked_word_set_id, t2.word_id, t2.selection FROM test_entry_ranked_word t2 '.
              'WHERE t2.test_entry_id = %d '.
              'AND t2.selection IS NOT NULL ) AS tmp '.
              'GROUP BY ranked_word_set_id, word_id, selection HAVING COUNT(*) = 1 ) AS tmp', $t1['id'], $t2['id'] );
              $match = 0 == $db->GetOne( $sql_match );
              if( $match )
              {
                $sql_match = sprintf(
                'SELECT COUNT(*) FROM ( '.
                'SELECT word_id FROM ( '.
                'SELECT t1.word_id FROM test_entry_ranked_word t1 '.
                'WHERE t1.test_entry_id = %d '.
                'AND t1.ranked_word_set_id IS NULL '.
                'UNION ALL '.
                'SELECT t2.word_id FROM test_entry_ranked_word t2 '.
                'WHERE t2.test_entry_id = %d '.
                'AND t2.ranked_word_set_id IS NULL ) AS tmp '.
                'GROUP BY word_id HAVING COUNT(*) = 1 ) AS tmp', $t1['id'], $t2['id'] );
                $match = 0 == $db->GetOne( $sql_match );
              }
            }
          }

          if( $match )
          {
            // remove erroneous adjudicate entries
            if( !is_null( $adj_id ) )
            {
              $db->Execute( sprintf(
                'DELETE FROM test_entry_%s WHERE test_entry_id = %d', $type, $adj_id ) );
              $db->Execute( sprintf(
                'DELETE FROM test_entry WHERE id = %d', $adj_id ) );
              $adjudicate_delete_count++;
            }
            // ensure the adjudicate status is correctly set
            if( -1 != $t1['adjudicate'] )
            {
              $db->Execute( sprintf(
                'UPDATE test_entry SET adjudicate = NULL WHERE id = %d', $t1['id'] ) );
              $test_entry_modify_count++;
            }
            if( -1 != $t2['adjudicate'] )
            {
              $db->Execute( sprintf(
                'UPDATE test_entry SET adjudicate = NULL WHERE id = %d', $t2['id'] ) );
              $test_entry_modify_count++;
            }
          }
          else
          {
            $close_assignment = false;
            if( is_null( $adj_id ) || 0 == $adj_completed )
            {
              // set the adjudicate status of the test_entries to 1
              if( 1 != $t1['adjudicate'] )
              {
                $db->Execute( sprintf(
                  'UPDATE test_entry SET adjudicate = 1 WHERE id = %d', $t1['id'] ) );
                $test_entry_modify_count++;
              }
              if( 1 != $t2['adjudicate'] )
              {
                $db->Execute( sprintf(
                  'UPDATE test_entry SET adjudicate = 1 WHERE id = %d', $t2['id'] ) );
                $test_entry_modify_count++;
              }
              $adjudicate_assignment = true;
            }
            else
            {
              if( !is_null( $adj_id ) &&  1 == $adj_completed )
              {              
                // set the adjudicate status of the test_entries to 0
                if( 0 != $t1['adjudicate'] )
                {
                  $db->Execute( sprintf(
                    'UPDATE test_entry SET adjudicate = 0 WHERE id = %d', $t1['id'] ) );
                  $test_entry_modify_count++;
                }
                if( 0 != $t2['adjudicate'] )
                {
                  $db->Execute( sprintf(
                    'UPDATE test_entry SET adjudicate = 0 WHERE id = %d', $t2['id'] ) );
                  $test_entry_modify_count++;
                }
              }
            }
          }

        } while( 2 <= count( $tests ) - $i );

        // close assignments with matching test_entry sub records
        if( $close_assignment )
        {
          if( !$a1_closed )
          {
            $result = $db->GetAll( sprintf(
              'SELECT update_timestamp FROM test_entry '.
              'WHERE assignment_id = %d OR participant_id = %d '.
              'ORDER BY update_timestamp DESC LIMIT 1', $a1_id, $p_id ) );
            $end_datetime = $result[0]['update_timestamp'];  
 
            $db->Execute( sprintf(
              'UPDATE assignment SET end_datetime = "%s" WHERE id = %d', $end_datetime, $a1_id ) );
            $assignment_closed_count++;
          }
          if( !$a2_closed )
          {
            $result = $db->GetAll( sprintf(
              'SELECT update_timestamp FROM test_entry '.
              'WHERE assignment_id = %d OR participant_id = %d '.
              'ORDER BY update_timestamp DESC LIMIT 1', $a2_id, $p_id ) );
            $end_datetime = $result[0]['update_timestamp'];  

            $db->Execute( sprintf(
              'UPDATE assignment SET end_datetime = "%s" WHERE id = %d', $end_datetime, $a2_id ) );
            $assignment_closed_count++;
          }
        }
        else
        {
          // open assignments if there is a mismatch but no adjudicate test_entry
          if( $adjudicate_assignment )
          {
            $db->Execute( sprintf(
              'UPDATE assignment SET end_datetime = NULL '.
              'WHERE id IN (%d, %d)', $a1_id, $a2_id ) );
            if( $a1_closed ) $assignment_open_count++;
            if( $a2_closed ) $assignment_open_count++;
          }
          else
          {
            // there is an adjudicate, close the assignments if necessary
            if( !$a1_closed )
            {
              $result = $db->GetAll( sprintf(
                'SELECT update_timestamp FROM test_entry '.
                'WHERE assignment_id = %d OR participant_id = %d '.
                'ORDER BY update_timestamp DESC LIMIT 1', $a1_id, $p_id ) );
              $end_datetime = $result[0]['update_timestamp'];  

              $db->Execute( sprintf(
                'UPDATE assignment SET end_datetime = "%s" WHERE id = %d', $end_datetime, $a1_id ) );
              $assignment_closed_count++;
            }
            if( !$a2_closed )
            {
              $result = $db->GetAll( sprintf(
                'SELECT update_timestamp FROM test_entry '.
                'WHERE assignment_id = %d OR participant_id = %d '.
                'ORDER BY update_timestamp DESC LIMIT 1', $a2_id, $p_id ) );
              $end_datetime = $result[0]['update_timestamp'];

              $db->Execute( sprintf(
                'UPDATE assignment SET end_datetime = "%s" WHERE id = %d', $end_datetime, $a2_id ) );
              $assignment_closed_count++;
            }
          }
        }

        $assignment_id_cache[] = $a1_id;
        $assignment_id_cache[] = $a2_id;
      }
      out( sprintf( 'Finished %d of %d assignments [opened: %d, closed: %d, deleted: %d, modified: %d]',
        $base + count( $rows ), $total, $assignment_open_count, $assignment_closed_count,
        $adjudicate_delete_count, $test_entry_modify_count ) );

      $base += $increment;
    }
  }
}

$patch = new patch();
$patch->execute();
