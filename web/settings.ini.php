<?php
/**
 * settings.ini.php
 * 
 * Defines initialization settings for curry.
 * DO NOT edit this file, to override these settings use settings.local.ini.php instead.
 * Any changes in the local ini file will override the settings found here.
 */

global $SETTINGS;

// tagged version
$SETTINGS['general']['application_name'] = 'curry';
$SETTINGS['general']['service_name'] = $SETTINGS['general']['application_name'];
$SETTINGS['general']['version'] = '0.1.0';

// always leave as false when running as production server
$SETTINGS['general']['development_mode'] = false;

// the name of the cohort associated with this application
$SETTINGS['general']['cohort'] = 'default';

// the location of curry internal path
$SETTINGS['path']['APPLICATION'] = '/usr/local/lib/curry';

// additional javascript libraries
$SETTINGS['url']['JQUERY'] = '/jquery';
$SETTINGS['url']['JQUERY_PLUGINS'] = $SETTINGS['url']['JQUERY'].'/plugins';
$SETTINGS['url']['JQUERY_JSTREE_JS'] = $SETTINGS['url']['JQUERY_PLUGINS'].'/jsTree.js';
$SETTINGS['url']['JQUERY_TIMERS_JS'] = $SETTINGS['url']['JQUERY_PLUGINS'].'/timers.js';
