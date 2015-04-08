#!/usr/bin/php
<?php
/**
 * This is a special script used when upgrading to version 1.1.3 for the CLSA
 * This script should be run once and only once BEFORE running patch_database.sql
 * It enforces state variable consistency in the test_entry and assignment tables
 * in preparation for the patch test_entry.sql
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

    // REPAIR OF INADMISSIBLE STATES

    // test entries cannot have audio_status='unavailable' or 'unusuable'
    // or have participant_status='refused' with completed=0

    $sql =
      'SELECT COUNT(*) FROM test_entry t '.
      'WHERE completed=0 '.
      'AND ( audio_status IN ("unavailable","unusable") '.
      'OR participant_status="refused" )';
    $update_num = intval( patch::my_get_one( $db, $sql ) );

    if( 0 < $update_num )
    {
      out( $update_num . ' test_entry records: enforce completed on audio and participant status' );
      $sql =
        'UPDATE test_entry t '.
        'SET completed=1 '.
        'WHERE completed=0 '.
        'AND ( audio_status IN ("unavailable","unusable") '.
        'OR participant_status="refused" )';
      patch::my_execute( $db, $sql );
    }

    // single progenitors' assignments cannot be finished
    $sql =
      'SELECT COUNT(*) FROM '.
      '( '.
      '  SELECT id FROM assignment '.
      '  WHERE end_datetime IS NOT NULL '.
      '  GROUP BY participant_id '.
      '  HAVING COUNT(*)=1 ) AS x';
    $update_num = intval( patch::my_get_one( $db, $sql ) );

    if( 0 < $update_num )
    {
      out( $update_num .
       ' assignment records: enforce single progenitor\'s to have unfinished assignments' );
      $sql =
        'UPDATE assignment a '.
        'JOIN ( '.
        '  SELECT id FROM assignment '.
        '  GROUP BY participant_id '.
        '  HAVING COUNT(*)=1 ) AS x ON x.id=a.id '.
      'SET end_datetime=NULL';
      patch::my_execute( $db, $sql );
    }

    // single progenitors cannot have non-null adjudicate status
    $sql =
      'SELECT COUNT(*) FROM test_entry t '.
      'JOIN ( '.
      '  SELECT id FROM assignment '.
      '  GROUP BY participant_id '.
      '  HAVING COUNT(*)=1 ) AS x ON x.id=t.assignment_id '.
      'WHERE adjudicate IS NOT NULL';
    $update_num = intval( patch::my_get_one( $db, $sql ) );

    if( 0 < $update_num )
    {
      out( $update_num .
        ' test_entry records: enforce single progenitors null adjudicate status' );
      $sql =
        'UPDATE test_entry t '.
        'JOIN ( '.
        '  SELECT id FROM assignment '.
        '  GROUP BY participant_id '.
        '  HAVING COUNT(*)=1 ) AS x ON x.id=t.assignment_id '.
        'SET adjudicate=NULL';
      patch::my_execute( $db, $sql );
    }

    // test entries cannot have completed=0 and end_datetime not null (finished assignment)
    $sql =
      'SELECT COUNT(*) FROM test_entry t '.
      'JOIN assignment a ON a.id=t.assignment_id '.
      'WHERE completed=0 '.
      'AND a.end_datetime IS NOT NULL';
    $update_num = intval( patch::my_get_one( $db, $sql ) );

    if( 0 < $update_num )
    {
      out( $update_num .
        ' test_entry records: enforce completed on progenitors with finished assignments' );
      $sql =
        'UPDATE test_entry t '.
        'JOIN assignment a ON a.id=t.assignment_id '.
        'SET completed=1 '.
        'WHERE completed=0 '.
        'AND a.end_datetime IS NOT NULL';
      patch::my_execute( $db, $sql );
    }

    // test_entries cannot have a deferred status of pending or requested
    // if completed=1 and end_datetime not null (finished assignment)
    $sql =
      'SELECT COUNT(*) FROM test_entry t '.
      'JOIN assignment a ON a.id=t.assignment_id '.
      'WHERE deferred IN ("pending","requested") '.
      'AND completed=1 '.
      'AND a.end_datetime IS NOT NULL';
    $update_num = intval( patch::my_get_one( $db, $sql ) );

    if( 0 < $update_num )
    {
      out( $update_num .
        ' test_entry records: enforce deferred state on completed progenitors' );
      $sql =
        'UPDATE test_entry t '.
        'JOIN assignment a ON a.id=t.assignment_id '.
        'SET deferred="resolved" '.
        'WHERE deferred IN ("pending","requested") '.
        'AND completed=1 '.
        'AND a.end_datetime IS NOT NULL';
      patch::my_execute( $db, $sql );
    }

    // revert use of adjudicate as status of completion
    // since technically an adjudication cannot itself be adjudicated
    $sql =
      'SELECT COUNT(*) FROM test_entry t '.
      'JOIN ' . $cenozo . '.participant p ON p.id=t.participant_id '.
      'WHERE adjudicate IS NOT NULL';
    $update_num = intval( patch::my_get_one( $db, $sql ) );

    if( 0 < $update_num )
    {
      out( $update_num .
        ' test_entry records: enforce adjudications to have null adjudicate state' );
      $sql =
        'UPDATE test_entry t '.
        'JOIN ' . $cenozo . '.participant p ON p.id=t.participant_id '.
        'SET adjudicate=NULL';
      patch::my_execute( $db, $sql );
    }

    // get all the adjudications
    $sql =
      'CREATE TEMPORARY TABLE tmp_adj AS '.
      'SELECT '.
      't.participant_id, '.
      'a.id as assignment_id, '.
      't.audio_status, '.
      't.participant_status, '.
      't.id, '.
      't.test_id, '.
      't.completed, '.
      'IF( a.end_datetime IS NULL, 0, 1 ) AS final_complete '.
      'FROM test_entry t '.
      'JOIN ' . $cenozo . '.participant p ON p.id=t.participant_id '.
      'JOIN assignment a ON a.participant_id=p.id '.
      'ORDER BY participant_id, assignment_id, t.id';
    patch::my_execute( $db, $sql );

    $sql =
      'ALTER TABLE tmp_adj ADD UNIQUE uq_index_1 (id, participant_id, assignment_id)';
    patch::my_execute( $db, $sql );

    $sql =
      'ALTER TABLE tmp_adj ADD UNIQUE uq_index_2 (test_id, assignment_id)';
    patch::my_execute( $db, $sql );

    $sql =
      'ALTER TABLE tmp_adj ADD INDEX index_3 (test_id, participant_id)';
    patch::my_execute( $db, $sql );

    // check adjudications where completed=0 and the assignment is finished
    $sql =
      'SELECT COUNT(*) FROM tmp_adj '.
      'WHERE completed=0 '.
      'AND final_complete=1 '.
      'GROUP BY id';
    $update_num = intval( patch::my_get_one( $db, $sql ) );

    if( 0 < $update_num )
    {
      out( $update_num .
        ' test_enry records: enforce completed adjudications with finished assignments' );

      $sql =
        'SELECT tmp_adj.id, '.
        'test_id, '.
        'audio_status, '.
        'participant_status, '.
        'participant_id, '.
        'tt.name AS type '.
        'FROM tmp_adj '.
        'JOIN test t ON t.id=tmp_adj.test_id '.
        'JOIN test_type tt ON tt.id=t.test_type_id '.
        'WHERE completed=0 '.
        'AND final_complete=1 '.
        'GROUP BY id';

      $data = patch::my_get_all( $db, $sql );
      $count = 0;
      $type_count = array_combine(
        array( 'confirmation','classification','ranked_word','alpha_numeric'),
        array(0,0,0,0));
      $sql =
        'SELECT MAX(rank) FROM ranked_word_set';
      $max_rank = patch::my_get_one( $db, $sql );
      foreach( $data as $data_set )
      {
        $test_id = $data_set['test_id'];
        $participant_id = $data_set['participant_id'];
        $type = $data_set['type'];
        $test_entry_id= $data_set['id'];
        $table_name = 'test_entry_' . $type;
        $audio_status = $data_set['audio_status'];
        $participant_status = $data_set['participant_status'];

        // verify that the completion requirements for the type are met
        // previously IN this script, completion based ON audio_status
        // and participant_status was already validated
        $sql = '';
        $do_update = true;
        if( 'confirmation' == $type )
        {
          $sql =
            'SELECT COUNT(*) FROM ' . $table_name . ' '.
            'WHERE confirmation IS NOT NULL '.
            'AND test_entry_id=' . $test_entry_id;
          $do_update = 0 < intval( patch::my_get_one( $db, $sql ) );
        }
        else if( 'ranked_word' == $type )
        {
           $sql =
            'SELECT '.
            '( '. $max_rank . ' - '.
              '( '.
                'SELECT COUNT(*) FROM ' . $table_name . ' '.
                'WHERE ranked_word_set_id IS NOT NULL '.
                'AND selection IS NOT NULL '.
                'AND IF( selection="variant", IF( word_id IS NULL, 0, 1 ), 1 ) '.
                'AND test_entry_id=' . $test_entry_id .
              ') '.
            ')';
          $do_update = 0 === intval( patch::my_get_one( $db, $sql ) );
        }
        else if( 'classification' == $type || 'alpha_numeric' == $type )
        {
          $sql =
            'SELECT COUNT(*) FROM ' . $table_name . ' '.
            'WHERE word_id IS NOT NULL '.
            'AND test_entry_id=' . $test_entry_id;
          $do_update = 0 < intval( patch::my_get_one( $db, $sql ) );
        }
        if( $do_update )
        {
          $sql =
            'UPDATE test_entry t '.
            'SET completed=1 '.
            'WHERE id=' . $test_entry_id;
          patch::my_execute( $db, $sql );
          $sql =
            'UPDATE tmp_adj '.
            'SET completed=1 '.
            'WHERE id=' . $test_entry_id;
          patch::my_execute( $db, $sql );
        }
        else
        {
          // check that the adjudication was required by comparing the progenitors and delete as required
          $sql =
            'SELECT '.
            't.id, '.
            't.audio_status, '.
            't.participant_status '.
            'FROM test_entry t '.
            'JOIN assignment a ON a.id=t.assignment_id '.
            'JOIN ' .  $cenozo . '.participant p ON p.id=a.participant_id '.
            'WHERE p.id=' . $participant_id . ' '.
            'AND t.test_id=' . $test_id;
          $progenitor_data = patch::my_get_all( $db, $sql );
          if( 2 != count( $progenitor_data ) )
          {
            out( 'error: missing paired progenitors for adjudicate id '. $test_entry_id );
            die();
          }

          // repair the adjudication if its audio or participant status is not set properly
          // when a difference in status exists between progenitors and a status indicating
          // completion is required
          $complete_states = array( 'unusable','unavailable','refused' );

          $is_repaired = false;
          if( $progenitor_data[0]['audio_status'] !=
              $progenitor_data[1]['audio_status'] )
          {
            $new_audio_status =
              in_array( $progenitor_data[0]['audio_status'], $complete_states ) ?
              $progenitor_data[0]['audio_status'] :
              ( in_array( $progenitor_data[1]['audio_status'], $complete_states ) ?
              $progenitor_data[1]['audio_status'] : $audio_status );
            if( $new_audio_status != $audio_status &&
                in_array( $new_audio_status, $complete_states ) )
            {
              $sql =
                'UPDATE test_entry '.
                'SET completed=1, audio_status="' . $new_audio_status . '" '.
                'WHERE id=' . $test_entry_id;
              patch::my_execute( $db, $sql );
              $sql =
                'UPDATE tmp_adj '.
                'SET completed=1, audio_status="' . $new_audio_status . '" '.
                'WHERE id=' . $test_entry_id;
              patch::my_execute( $db, $sql );
              $is_repaired = true;
            }
          }
          if( $progenitor_data[0]['participant_status'] !=
              $progenitor_data[1]['participant_status'] && !$is_repaired )
          {
            $new_participant_status =
              in_array( $progenitor_data[0]['participant_status'], $complete_states ) ?
              $progenitor_data[0]['participant_status'] :
              ( in_array( $progenitor_data[1]['participant_status'], $complete_states ) ?
              $progenitor_data[1]['participant_status'] : $participant_status );
            if( $new_participant_status != $participant_status &&
                in_array( $new_participant_status, $complete_states ) )
            {
              $sql =
                'UPDATE test_entry '.
                'SET completed=1, participant_status="' . $new_participant_status . '" '.
                'WHERE id=' . $test_entry_id;
              patch::my_execute( $db, $sql );
              $sql =
                'UPDATE tmp_adj '.
                'SET completed=1, participant_status="' . $new_participant_status . '" '.
                'WHERE id=' . $test_entry_id;
              patch::my_execute( $db, $sql );
              $is_repaired = true;
            }
          }

          if( $is_repaired )
          {
            $sql =
             'DELETE te.* FROM ' . $table_name . ' te WHERE test_entry_id=' . $test_entry_id;
            patch::my_execute( $db, $sql );
          }
          else
          {
            $count++;
            out( 'warning (' . $count .
              '):  '. $type .' adjudication fails complete requirements ' . $test_entry_id );
            $type_count[$type]++;
          }
        }
      }

      if( 0 < $count )
      {
        out( 'adjudication counts by test type that fail completion requirements: ' );
        var_dump( $type_count );
      }
    }

    // get the progenitors
    $sql =
      'CREATE TEMPORARY TABLE tmp_prog AS '.
      'SELECT '.
      'a.participant_id, '.
      't.assignment_id, '.
      't.id, '.
      't.test_id, '.
      't.completed, '.
      't.adjudicate, '.
      't.deferred, '.
      't.participant_status, '.
      't.audio_status, '.
      'IF( a.end_datetime IS NULL, 0, 1 ) AS final_complete '.
      'FROM test_entry t '.
      'JOIN assignment a ON a.id=t.assignment_id '.
      'ORDER BY participant_id, assignment_id, t.id';
    patch::my_execute( $db, $sql );

    $sql =
      'ALTER TABLE tmp_prog ADD UNIQUE uq_index_1 (id, participant_id, assignment_id)';
    patch::my_execute( $db, $sql );

   // fix progenitors flagged as adjudicated where assignments are finished
   $sql =
     'SELECT COUNT(*) FROM ( '.
     '  SELECT DISTINCT '.
     '  id, '.
     '  participant_id, '.
     '  test_id '.
     '  FROM tmp_prog '.
     '  WHERE final_complete=1 '.
     '  AND adjudicate IS NOT NULL '.
     '  AND completed=1 ) AS x';
    $update_num = intval( patch::my_get_one( $db, $sql ) );

    if( 0 < $update_num )
    {
      out( $update_num .
        ' test_entry records: enforce adjudicate state on progenitors with finished assignments' );

      $sql =
        'SELECT DISTINCT '.
        'id, '.
        'participant_id, '.
        'test_id '.
        'FROM tmp_prog '.
        'WHERE final_complete=1 '.
        'AND adjudicate IS NOT NULL '.
        'AND completed=1';

      $data = patch::my_get_all( $db, $sql );
      $data_count = floatval(count( $data ));
      $percent_count = 0;
      $previous_percent = 0;
      foreach( $data as $data_set )
      {
        $id = $data_set['id'];
        $test_id = $data_set['test_id'];
        $participant_id = $data_set['participant_id'];
        // is there an adjudication for the participant's test?
        $sql =
          'SELECT COUNT(*) '.
          'FROM tmp_adj '.
          'WHERE test_id='. $test_id. ' '.
          'AND participant_id=' . $participant_id;
        $adj_update = 0 === intval( patch::my_get_one( $db, $sql ) ) ?  'null' : 0;

        // if no adjudication, set the adjudication flag to default null
        // otherwise, since the assignment is complete and the adjudication exists, mark the test
        // as having a completed adjudication
        $sql =
          'UPDATE test_entry '.
          'SET adjudicate=' . $adj_update . ' '.
          'WHERE id=' . $id;
        patch::my_execute( $db, $sql );
        $sql =
          'UPDATE tmp_prog '.
          'SET adjudicate=' . $adj_update . ' '.
          'WHERE id=' . $id;
        patch::my_execute( $db, $sql );

        $percent_done = round( (100.0 * $percent_count++)/$data_count );
        if( $percent_done != $previous_percent )
        {
          $previous_percent = $percent_done;
          if( 0 == $percent_done % 10 )
            out( $percent_done . ' % done fixing progenitor adjudication state' );
        }
      }
    }

    // fix completion states of assignments
    $sql =
      'CREATE TEMPORARY TABLE tmp_assgn AS '.
      'SELECT '.
      'a.id, '.
      'a.participant_id, '.
      'COUNT(*) AS test_count, '.
      'SUM(completed) AS complete_count, '.
      'SUM( IF( deferred IN ( "pending","requested" ), 1, 0 ) ) AS deferred_count, '.
      'SUM( IFNULL( adjudicate, 0 ) ) AS adjudicate_count, '.
      'IF( a.end_datetime IS NULL, 0, 1) AS final_complete, '.
      '0 AS test_complete_status, '.
      '0 AS has_sibling, '.
      'MAX( t.update_timestamp ) AS ut1, '.
      'MAX( a.update_timestamp ) AS last_update_time '.
      'FROM test_entry t '.
      'JOIN assignment a ON a.id=t.assignment_id '.
      'GROUP BY assignment_id';
    patch::my_execute( $db, $sql );

    $sql =
      'ALTER TABLE tmp_assgn ADD PRIMARY KEY (id)';
    patch::my_execute( $db, $sql );
    $sql =
      'ALTER TABLE tmp_assgn ADD INDEX (participant_id)';
    patch::my_execute( $db, $sql );

    $sql =
      'UPDATE tmp_assgn SET test_complete_status=( IF( complete_count=test_count, 1, 0 ) )';
    patch::my_execute( $db, $sql );

    $sql =
      'UPDATE tmp_assgn '.
      'JOIN ( '.
      'SELECT participant_id FROM assignment '.
      'GROUP BY participant_id HAVING COUNT(*)=2 ) AS x ON x.participant_id=tmp_assgn.participant_id '.
      'SET has_sibling=1';
    patch::my_execute( $db, $sql );

    $sql =
      'UPDATE tmp_assgn SET last_update_time=ut1 WHERE ut1 > last_update_time';
    patch::my_execute( $db, $sql );
    $sql =
      'ALTER TABLE tmp_assgn DROP COLUMN ut1';
    patch::my_execute( $db, $sql );

    $sql =
      'CREATE TEMPORARY TABLE tmp_comp AS '.
      'SELECT '.
      'participant_id, '.
      'IF( SUM(complete_count) = SUM(test_count), 1, 0 ) AS can_complete, '.
      'IF( SUM(final_complete) > 1, 1, 0 ) AS final_complete, '.
      'MAX( last_update_time ) AS last_update_time '.
      'FROM tmp_assgn '.
      'WHERE deferred_count=0 '.
      'AND adjudicate_count=0 '.
      'AND has_sibling=1 '.
      'GROUP BY participant_id '.
      'HAVING COUNT(*)=2';
    patch::my_execute( $db, $sql );

    // test where either the participants' assignments are finished
    // but there are incomplete tests
    // OR
    // the participants' assignments are not finished
    // but all the tests are complete
    // IN both cases, there are no adjudications nor deferrals
    $sql =
      'SELECT COUNT(*) FROM tmp_comp '.
      'WHERE can_complete!=final_complete';
    $update_num = intval( patch::my_get_one( $db, $sql ) );

    if( 0 < $update_num )
    {
      out( $update_num .
        ' assignment records: enforce completed all tests state on end_datetime ' );

      $sql =
        'SELECT * FROM tmp_comp WHERE can_complete!=final_complete';
      $data = patch::my_get_all( $db, $sql );

      $close_count = 0;
      if( 0 < count($data) )
      {
        foreach( $data as $data_set )
        {
          $participant_id = $data_set['participant_id'];
          $can_complete = $data_set['can_complete'];
          $final_complete = $data_set['final_complete'];
          $last_update_time = $data_set['last_update_time'];
          $sql =
            'SELECT DISTINCT uid '.
            'FROM ' . $cenozo . '.participant '.
            'WHERE id=' . $participant_id;
          $uid = patch::my_get_one( $db, $sql );
          if( 0 == $final_complete && 1 == $can_complete )
          {
            $sql =
              'UPDATE assignment '.
              'SET end_datetime="' . $last_update_time . '" '.
              'WHERE participant_id=' . $participant_id;
            patch::my_execute( $db, $sql );

            out( 'closed assignments for UID ' . $uid );
          }
          else if( 1 == $final_complete && 0 == $can_complete )
          {
            $sql =
              'UPDATE assignment '.
              'SET end_datetime=null '.
              'WHERE participant_id=' . $participant_id;
            patch::my_execute( $db, $sql );

            out( 'reopened unfinished assignments for UID ' . $uid );
          }
        }
      }
    }

    // apply the patch to the test_entry table if required
    $sql =
      'SELECT COUNT(*) '.
      'FROM information_schema.COLUMNS '.
      'WHERE TABLE_SCHEMA = ( SELECT DATABASE() ) '.
      'AND TABLE_NAME = "test_entry" '.
      'AND COLUMN_NAME = "completed" '.
      'AND COLUMN_TYPE = "TINYINT(1)"';
    $do_upgrade = 1 === intval( patch::my_get_one( $db, $sql ) );

    if( $do_upgrade )
    {
      out( 'Upgrading test_entry table: changing completed to an enum' );

      $sql =
        'ALTER TABLE test_entry '.
        'ADD COLUMN completed_temp '.
        'ENUM("incomplete","complete","submitted") NOT NULL DEFAULT "incomplete"';
      patch::my_execute( $db, $sql );

      $sql =
        'UPDATE test_entry '.
        'SET completed_temp="incomplete" '.
        'WHERE completed=0';
      patch::my_execute( $db, $sql );

      $sql =
        'UPDATE test_entry '.
        'SET completed_temp="complete" '.
        'WHERE completed=1';
      patch::my_execute( $db, $sql );

      // change complete to submitted where an adjudicate is part of a finished assignment
      $sql =
        'UPDATE test_entry t '.
        'JOIN assignment a ON a.participant_id=t.participant_id '.
        'SET completed_temp="submitted" '.
        'WHERE completed=1 '.
        'AND t.participant_id IS NOT NULL '.
        'AND a.end_datetime IS NOT NULL';
      patch::my_execute( $db, $sql );

      // change complete to submitted where a progenitor is part of a finished assignment
      $sql =
        'UPDATE test_entry t '.
        'JOIN assignment a ON a.id=t.assignment_id '.
        'SET completed_temp="submitted" '.
        'WHERE completed=1 '.
        'AND a.end_datetime IS NOT NULL';
      patch::my_execute( $db, $sql );

      // change complete to submitted where a progenitor is in a non-deferred state
      // but the assignment is not finished
      $sql =
        'UPDATE test_entry t '.
        'JOIN assignment a ON a.id=t.assignment_id '.
        'SET completed_temp="submitted" '.
        'WHERE completed=1 '.
        'AND deferred NOT IN ("pending","requested") '.
        'AND a.end_datetime IS NULL';
      patch::my_execute( $db, $sql );

      $sql =
        'ALTER TABLE test_entry DROP COLUMN completed';
      patch::my_execute( $db, $sql );

      $sql =
        'ALTER TABLE test_entry '.
        'CHANGE completed_temp '.
        'completed ENUM("incomplete","complete","submitted") NOT NULL DEFAULT "incomplete"';
      patch::my_execute( $db, $sql );
    }

    out( 'Finished' );
  }
}

$patch = new patch();
$patch->execute();
