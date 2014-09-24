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

    // first do a clean up of empty records
    // get all the participant's assignments
    $sql =
      'SELECT unique_constraint_schema '.
      'FROM information_schema.referential_constraints '.
      'WHERE constraint_schema = DATABASE() '.
      'AND constraint_name = "fk_role_has_operation_role_id"';
    $cenozo = patch::my_get_one( $db, $sql );

    // remove all records associated with participants having > 2 assignments

    $sql = 'SET @rank = 0';
    patch::my_execute( $db, $sql );

    $sql =
      'CREATE TEMPORARY TABLE tmp AS '.
      'SELECT id, participant_id FROM ('.
      'SELECT a.id, a.start_datetime, '.
      '@rank:= IF( @current_id=a.participant_id, @rank + 1, 1 ) AS rank, '.
      '@current_id:= a.participant_id AS participant_id '.
      'FROM assignment a '.
      'JOIN ( '.
      'SELECT p.id AS participant_id '.
      'FROM assignment a '.
      'JOIN '. $cenozo . '.participant p ON p.id=a.participant_id '.
      'GROUP BY p.id '.
      'HAVING COUNT(p.id) > 2 ) AS x '.
      'ON x.participant_id=a.participant_id '.
      'ORDER BY a.participant_id, a.start_datetime ) AS y '.
      'WHERE y.rank > 2';
    patch::my_execute( $db, $sql );

    $type_names = array( 'ranked_word', 'confirmation', 'alpha_numeric', 'classification' );
    foreach( $type_names as $type_name )
    {
      $table_name = 'test_entry_' . $type_name;

      $sql =
        'DELETE te.* FROM '. $table_name . ' te '.
        'JOIN test_entry t ON t.id=te.test_entry_id '.
        'JOIN tmp ON tmp.id=t.assignment_id';
      patch::my_execute( $db, $sql );
    }

    $sql =
      'DELETE tn.* FROM test_entry_note tn '.
      'JOIN test_entry t ON t.id=tn.test_entry_id '.
      'JOIN tmp ON tmp.id=t.assignment_id';
    patch::my_execute( $db, $sql );

    $sql =
      'DELETE t.* FROM test_entry t '.
      'JOIN tmp ON tmp.id=t.assignment_id';
    patch::my_execute( $db, $sql );

    $sql =
      'DELETE a.* FROM assignment a '.
      'JOIN tmp ON tmp.id=a.id';

    $pruned_count = patch::my_get_one( $db, str_replace('DELETE a.*','SELECT COUNT(*)', $sql ) );

    patch::my_execute( $db, $sql );

    if( $pruned_count ) out( 'pruned ' . $pruned_count . ' extra assignments (rank > 2)' );

    $sql = 'DROP TABLE tmp';
    patch::my_execute( $db, $sql );

    $sql =
      'CREATE TEMPORARY TABLE assign1 AS '.
      'SELECT a.id AS assignment_id, p.id AS participant_id '.
      'FROM ' .  $cenozo . '.participant p '.
      'JOIN assignment a ON a.participant_id=p.id '.
      'GROUP BY p.id '.
      'HAVING COUNT(*)=2';
    patch::my_execute( $db, $sql );
    $sql = 'ALTER TABLE assign1 ADD INDEX (assignment_id)';
    patch::my_execute( $db, $sql );

    $sql =
      'CREATE TEMPORARY TABLE assign2 AS '.
      'SELECT a.id AS assignment_id, a.participant_id AS participant_id '.
      'FROM assignment a '.
      'JOIN assign1 ON assign1.participant_id=a.participant_id '.
      'WHERE assign1.assignment_id!=a.id';
    patch::my_execute( $db, $sql );
    $sql = 'ALTER TABLE assign2 ADD INDEX (assignment_id)';
    patch::my_execute( $db, $sql );

    $sql =
      'INSERT INTO assign1 (assignment_id, participant_id) '.
      'SELECT assignment_id, participant_id '.
      'FROM assign2';
    patch::my_execute( $db, $sql );
    $sql = 'DROP TABLE assign2';
    patch::my_execute( $db, $sql );

    $type_names = array( 'alpha_numeric', 'classification' );
    foreach( $type_names as $type_name )
    {
      // locate empty progenitor entries by rank
      $table_name = 'test_entry_' . $type_name;
      out( 'working on ' . $table_name . ' records' );
      $sql =
        'CREATE TEMPORARY TABLE tmp1 AS '.
        'SELECT '.
        'MAX( if(word_id IS NULL, 0 ,te.rank )) AS last_rank, '.
        'COUNT(te.id) AS max_rank, '.
        'te.test_entry_id, '.
        'a.participant_id '.
        'FROM ' . $table_name . ' te '.
        'JOIN test_entry t ON t.id=te.test_entry_id '.
        'JOIN assign1 a ON a.assignment_id=t.assignment_id '.
        'WHERE completed=1 '.
        'GROUP BY t.id';
      patch::my_execute( $db, $sql );
      $sql = 'ALTER TABLE tmp1 ADD INDEX (participant_id)';
      patch::my_execute( $db, $sql );
      $sql = 'UPDATE tmp1 SET last_rank=max_rank WHERE last_rank=0';
      patch::my_execute( $db, $sql );

      // locate empty adjudicate entries by rank
      $sql =
        'CREATE TEMPORARY TABLE tmp2 AS '.
        'SELECT '.
        'MAX( if(word_id IS NULL, 0, te.rank)) AS last_rank, '.
        'COUNT(te.id) AS max_rank, '.
        'te.test_entry_id, '.
        't.participant_id '.
        'FROM ' . $table_name . ' te '.
        'JOIN test_entry t ON t.id=te.test_entry_id '.
        'JOIN (SELECT distinct participant_id FROM tmp1 ) x '.
        'ON x.participant_id=t.participant_id '.
        'GROUP BY t.id';
      patch::my_execute( $db, $sql );
      $sql = 'ALTER TABLE tmp2 ADD INDEX (participant_id)';
      patch::my_execute( $db, $sql );
      $sql = 'UPDATE tmp2 SET last_rank=max_rank WHERE last_rank=0';
      patch::my_execute( $db, $sql );
      $sql =
        'INSERT INTO tmp1 (last_rank, max_rank, test_entry_id, participant_id) '.
        'SELECT last_rank, max_rank, test_entry_id, participant_id FROM tmp2';
      patch::my_execute( $db, $sql );
      $sql = 'DROP TABLE tmp2';
      patch::my_execute( $db, $sql );

      $sql =
        'DELETE te.* '.
        'FROM ' . $table_name . ' AS te '.
        'JOIN tmp1 ON tmp1.test_entry_id=te.test_entry_id '.
        'WHERE te.rank > tmp1.last_rank';

      $pruned_count =
        patch::my_get_one( $db, str_replace( 'DELETE te.*','SELECT COUNT(*)', $sql ) );

      patch::my_execute( $db, $sql );

      // trim records with audio_status ={unavailable, unusable} or participant_status={refused}
      // leave 1 empty entry in case the record is deferred
      $sql_pre =
        'DELETE te.* '.
        'FROM ' . $table_name . ' AS te '.
        'JOIN tmp1 ON tmp1.test_entry_id=te.test_entry_id '.
        'JOIN test_entry t ON t.id=tmp1.test_entry_id '.
        'WHERE te.rank>1 ';

      $sql = $sql_pre .
        'AND t.audio_status IN ("unavailable","unusable")';

      $pruned_count +=
        patch::my_get_one( $db, str_replace( 'DELETE te.*','SELECT COUNT(*)', $sql ) );

      patch::my_execute( $db, $sql );

      $sql = $sql_pre .
        'AND t.participant_status IN ("refused")';

      $pruned_count +=
        patch::my_get_one( $db, str_replace( 'DELETE te.*','SELECT COUNT(*)', $sql ) );

      patch::my_execute( $db, $sql );

      if( $pruned_count ) out( 'pruned ' .  $pruned_count . ' ' . $table_name . ' records' );

      $sql = 'DROP TABLE tmp1';
      patch::my_execute( $db, $sql );
    }

    out( 'working on test_entry_ranked_word records' );

    // test_entry_ranked_word entries require different handling: no rank column
    $sql =
      'CREATE TEMPORARY TABLE tmp1 AS '.
      'SELECT '.
      'MIN(te.id) AS first_id, '.
      'MAX(te.id) AS last_id, '.
      'te.test_entry_id, '.
      'a.participant_id '.
      'FROM test_entry_ranked_word te '.
      'JOIN test_entry t ON t.id=te.test_entry_id '.
      'JOIN assign1 a ON a.assignment_id=t.assignment_id '.
      'WHERE completed=1 '.
      'AND word_id IS NULL '.
      'AND ranked_word_set_id IS NULL '.
      'GROUP BY t.id';
    patch::my_execute( $db, $sql );
    $sql = 'ALTER TABLE tmp1 ADD INDEX (participant_id)';
    patch::my_execute( $db, $sql );

    // delete empty intrusions
    $sql =
      'DELETE te.* '.
      'FROM test_entry_ranked_word AS te '.
      'JOIN tmp1 ON tmp1.test_entry_id=te.test_entry_id '.
      'WHERE te.id BETWEEN tmp1.first_id AND tmp1.last_id '.
      'AND te.test_entry_id=tmp1.test_entry_id ';
    patch::my_execute( $db, $sql );

    $sql =
      'CREATE TEMPORARY TABLE tmp2 AS '.
      'SELECT '.
      'MAX(x.id_count) AS min_count, x.participant_id FROM ( '.
      'SELECT '.
      'COUNT(te.id) AS id_count, '.
      'a.assignment_id, '.
      'a.participant_id '.
      'FROM test_entry_ranked_word te '.
      'JOIN test_entry t ON t.id=te.test_entry_id '.
      'JOIN assign1 a ON a.assignment_id=t.assignment_id '.
      'WHERE completed=1 '.
      'GROUP BY t.id ) AS x '.
      'GROUP BY x.participant_id';
    patch::my_execute( $db, $sql );
    $sql =  'ALTER TABLE tmp2 ADD INDEX (participant_id)';
    patch::my_execute( $db, $sql );

    $sql =
      'CREATE TEMPORARY TABLE tmp3 AS '.
      'SELECT '.
      'COUNT(te.id) - tmp2.min_count AS count, '.
      'te.test_entry_id, '.
      'tmp2.participant_id '.
      'FROM test_entry_ranked_word te '.
      'JOIN test_entry t ON t.id=te.test_entry_id '.
      'JOIN tmp2 ON tmp2.participant_id=t.participant_id '.
      'WHERE completed=1 '.
      'GROUP BY t.id';
    patch::my_execute( $db, $sql );
    $sql = 'ALTER TABLE tmp3 ADD INDEX (test_entry_id)';
    patch::my_execute( $db, $sql );

    $rows = $db->GetAll(
      'SELECT id, te.test_entry_id, count '.
      'FROM test_entry_ranked_word te '.
      'JOIN tmp3 ON tmp3.test_entry_id=te.test_entry_id '.
      'WHERE count>0 '.
      'ORDER BY test_entry_id DESC, id DESC' );

    $pruned_count = 0;
    if( count( $rows ) > 0 )
    {
      $current_count = 0;
      $current_test_entry_id = 0;
      $sql =
        'DELETE FROM test_entry_ranked_word WHERE id IN ( ';
      foreach( $rows as $index => $row )
      {
        $id = $row['id'];
        $test_entry_id = $row['test_entry_id'];
        $count = $row['count'];

        if( $current_test_entry_id != $test_entry_id )
        {
           $current_test_entry_id = $test_entry_id;
           $current_count=0;
        }
        if( $current_count != $count )
        {
           $sql = $sql . sprintf( '%d, ', $id );
           $current_count++;
           $pruned_count++;
        }
      }
      $sql = substr( $sql, 0, strrpos( $sql, ',' ) );
      $sql = $sql . ' )';
      patch::my_execute( $db, $sql );
    }

    $sql_pre =
      'DELETE te.* '.
      'FROM test_entry_ranked_word AS te '.
      'JOIN tmp1 ON tmp1.test_entry_id=te.test_entry_id '.
      'JOIN test_entry t ON t.id=tmp1.test_entry_id '.
      'WHERE te.ranked_word_set_id IS NULL ';
    $sql = $sql_pre .
      'AND audio_status IN ("unavailable","unusable")';

    $pruned_count +=
      patch::my_get_one( $db, str_replace( 'DELETE te.*','SELECT COUNT(*)', $sql ) );

    patch::my_execute( $db, $sql );

    $sql = $sql_pre .
      'AND participant_status IN ("refused")';

    $pruned_count +=
      patch::my_get_one( $db, str_replace( 'DELETE te.*','SELECT COUNT(*)', $sql ) );

    patch::my_execute( $db, $sql );

    if( $pruned_count ) out( 'pruned ' .  $pruned_count . ' test_entry_ranked_word records' );

    $sql = 'DROP TABLE tmp3';
    patch::my_execute( $db, $sql );
    $sql = 'DROP TABLE tmp2';
    patch::my_execute( $db, $sql );
    $sql = 'DROP TABLE tmp1';
    patch::my_execute( $db, $sql );
    $sql = 'DROP TABLE assign1';
    patch::my_execute( $db, $sql );

    out( 'finished cleaning' );

    // process assignments

    $sql =
      'SELECT COUNT(*) FROM assignment_total atot ' .
      'JOIN assignment a ON atot.assignment_id=a.id '.
      'WHERE atot.deferred=0 '.
      'AND atot.completed=6';
    $total = patch::my_get_one( $db, $sql );

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
      $sql = sprintf(
        'SELECT assignment_id AS id, IF( IFNULL( a.end_datetime, 0 ) != 0, 1, 0) AS closed '.
        'FROM assignment_total atot ' .
        'JOIN assignment a ON a.id=atot.assignment_id '.
        'WHERE atot.deferred = 0 '.
        'AND atot.completed = 6 '.
        'ORDER BY id LIMIT %d, %d', $base, $increment );
      $rows = patch::my_get_all( $db, $sql );
      foreach( $rows as $index => $row )
      {
        $a1_id = $row['id'];
        if( in_array( $a1_id, $assignment_id_cache ) ) continue;

        // get the sibling assignment
        $sql = sprintf(
         'SELECT participant_id FROM assignment '.
         'WHERE id = %d', $a1_id );
        $p_id = patch::my_get_one( $db, $sql );

        $sql = sprintf(
         'SELECT id FROM assignment '.
         'WHERE id != %d '.
         'AND participant_id=%d', $a1_id, $p_id );
        $a2_id = patch::my_get_one( $db, $sql );

        if( is_null( $a2_id) || in_array( $a2_id, $assignment_id_cache ) ) continue;

        $sql = sprintf(
         'SELECT IF( IFNULL( end_datetime, 0 ) != 0, 1, 0) FROM assignment '.
         'WHERE id = %d ', $a2_id );
        $a2_closed = patch::my_get_one( $db, $sql );
        $a1_closed = $row['closed'];

        // get all the test entries
        $sql = sprintf(
          'SELECT te.id, te.test_id, te.assignment_id, tt.name, '.
          'IFNULL(te.adjudicate, -1) AS adjudicate, '.
          'IFNULL( te.audio_status, "NULL") AS audio_status, '.
          'IFNULL(te.participant_status, "NULL") AS participant_status '.
          'FROM test_entry te '.
          'JOIN test t ON t.id = te.test_id '.
          'JOIN test_type tt ON tt.id = t.test_type_id '.
          'WHERE assignment_id IN ( %d, %d ) '.
          'ORDER BY te.test_id, te.assignment_id', $a1_id, $a2_id );
        $tests = patch::my_get_all( $db, $sql );
        if( 12 != count($tests) )
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
          $sql = sprintf(
            'SELECT id, completed, update_timestamp, IFNULL(adjudicate, -1) AS adjudicate, '.
            'IFNULL( audio_status, "NULL") AS audio_status, '.
            'IFNULL(participant_status, "NULL") AS participant_status '.
            'FROM test_entry '.
            'WHERE participant_id = %d '.
            'AND test_id = %d ORDER BY update_timestamp DESC', $p_id, $test_id );
          $adjudicates = patch::my_get_all( $db, $sql );
          $adjudicate_num = count( $adjudicates );
          if( $adjudicate_num > 1 )
          {
            out( sprintf( 'found multiple (%d) adjudicates for test %d of type %s',
              $adjudicate_num, $test_id, $type ) );
            for( $j = 1; $j < $adjudicate_num; $j++ )
            {
              $sql = sprintf(
                'DELETE FROM test_entry_%s WHERE test_entry_id=%d', $type, $adjudicates[$j]['id'] );
              patch::my_execute( $db, $sql );
              $sql = sprintf(
                'DELETE FROM test_entry WHERE id=%d', $adjudicates[$j]['id'] );
              patch::my_execute( $db, $sql );
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
              $sql = sprintf(
                'SELECT COUNT(*) FROM ( '.
                'SELECT rank, word_id FROM ( '.
                'SELECT t1.rank, t1.word_id FROM test_entry_%s t1 '.
                'WHERE t1.test_entry_id = %d '.
                'UNION ALL '.
                'SELECT t2.rank, t2.word_id FROM test_entry_%s t2 '.
                'WHERE t2.test_entry_id = %d ) AS tmp '.
                'GROUP BY rank, word_id HAVING COUNT(*) = 1 ) AS tmp', $type, $t1['id'], $type, $t2['id'] );
              $match = 0 == patch::my_get_one( $db, $sql );
            }
            else if( $type == 'confirmation' )
            {
              $sql = sprintf(
                'SELECT COUNT(*) FROM ( '.
                'SELECT confirmation FROM ( '.
                'SELECT t1.confirmation FROM test_entry_confirmation t1 '.
                'WHERE t1.test_entry_id = %d '.
                'UNION ALL '.
                'SELECT t2.confirmation FROM test_entry_confirmation t2 '.
                'WHERE t2.test_entry_id = %d ) AS tmp '.
                'GROUP BY confirmation HAVING COUNT(*) = 1 ) AS tmp', $t1['id'], $t2['id'] );
              $match = 0 == patch::my_get_one( $db, $sql );
            }
            else if( $type == 'ranked_word' )
            {
              $sql = sprintf(
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
              $match = 0 == patch::my_get_one( $db, $sql );
              if( $match )
              {
                $sql = sprintf(
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
                $match = 0 == patch::my_get_one( $db, $sql );
              }
            }
          }

          if( $match )
          {
            // remove erroneous adjudicate entries
            if( !is_null( $adj_id ) )
            {
              $sql = sprintf(
                'DELETE FROM test_entry_%s WHERE test_entry_id = %d', $type, $adj_id );
              patch::my_execute( $db, $sql );
              $sql = sprintf(
                'DELETE FROM test_entry WHERE id = %d', $adj_id );
               patch::my_execute( $db, $sql );
              $adjudicate_delete_count++;
            }
            // ensure the adjudicate status is correctly set
            if( -1 != $t1['adjudicate'] )
            {
              $sql = sprintf(
                'UPDATE test_entry SET adjudicate = NULL WHERE id = %d', $t1['id'] );
              patch::my_execute( $db, $sql );
              $test_entry_modify_count++;
            }
            if( -1 != $t2['adjudicate'] )
            {
              $sql = sprintf(
                'UPDATE test_entry SET adjudicate = NULL WHERE id = %d', $t2['id'] );
              patch::my_execute( $db, $sql );
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
                $sql = sprintf(
                  'UPDATE test_entry SET adjudicate = 1 WHERE id = %d', $t1['id'] );
                patch::my_execute( $db, $sql );
                $test_entry_modify_count++;
              }
              if( 1 != $t2['adjudicate'] )
              {
                $sql = sprintf(
                  'UPDATE test_entry SET adjudicate = 1 WHERE id = %d', $t2['id'] );
                patch::my_execute( $db, $sql );
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
                  $sql = sprintf(
                    'UPDATE test_entry SET adjudicate = 0 WHERE id = %d', $t1['id'] );
                  patch::my_execute( $db, $sql );
                  $test_entry_modify_count++;
                }
                if( 0 != $t2['adjudicate'] )
                {
                  $sql = sprintf(
                    'UPDATE test_entry SET adjudicate = 0 WHERE id = %d', $t2['id'] );
                  patch::my_execute( $db, $sql );
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
            $sql = sprintf(
              'SELECT update_timestamp FROM test_entry '.
              'WHERE assignment_id = %d OR participant_id = %d '.
              'ORDER BY update_timestamp DESC LIMIT 1', $a1_id, $p_id );
            $result = patch::my_get_all( $db, $sql );
            $end_datetime = $result[0]['update_timestamp'];

            $sql = sprintf(
              'UPDATE assignment SET end_datetime = "%s" WHERE id = %d', $end_datetime, $a1_id );
            patch::my_execute( $db, $sql );
            $assignment_closed_count++;
          }
          if( !$a2_closed )
          {
            $sql = sprintf(
              'SELECT update_timestamp FROM test_entry '.
              'WHERE assignment_id = %d OR participant_id = %d '.
              'ORDER BY update_timestamp DESC LIMIT 1', $a2_id, $p_id );
            $result = patch::my_get_all( $db, $sql );
            $end_datetime = $result[0]['update_timestamp'];

            $sql = sprintf(
              'UPDATE assignment SET end_datetime = "%s" WHERE id = %d', $end_datetime, $a2_id );
            patch::my_execute( $db, $sql );
            $assignment_closed_count++;
          }
        }
        else
        {
          // open assignments if there is a mismatch but no adjudicate test_entry
          if( $adjudicate_assignment )
          {
            $sql = sprintf(
              'UPDATE assignment SET end_datetime = NULL '.
              'WHERE id IN (%d, %d)', $a1_id, $a2_id );
            patch::my_execute( $db, $sql );
            if( $a1_closed ) $assignment_open_count++;
            if( $a2_closed ) $assignment_open_count++;
          }
          else
          {
            // there is an adjudicate, close the assignments if necessary
            if( !$a1_closed )
            {
              $sql = sprintf(
                'SELECT update_timestamp FROM test_entry '.
                'WHERE assignment_id = %d OR participant_id = %d '.
                'ORDER BY update_timestamp DESC LIMIT 1', $a1_id, $p_id );
              $result = patch::my_get_all( $db, $sql );
              $end_datetime = $result[0]['update_timestamp'];

              $sql = sprintf(
                'UPDATE assignment SET end_datetime = "%s" WHERE id = %d', $end_datetime, $a1_id );
              patch::my_execute( $db, $sql );
              $assignment_closed_count++;
            }
            if( !$a2_closed )
            {
              $sql = sprintf(
                'SELECT update_timestamp FROM test_entry '.
                'WHERE assignment_id = %d OR participant_id = %d '.
                'ORDER BY update_timestamp DESC LIMIT 1', $a2_id, $p_id );
              $result = patch::my_get_all( $db, $sql );
              $end_datetime = $result[0]['update_timestamp'];

              $sql = sprintf(
                'UPDATE assignment SET end_datetime = "%s" WHERE id = %d', $end_datetime, $a2_id );
              patch::my_execute( $db, $sql );
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
