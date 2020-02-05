<?php

/**
 * @file
 * Development settings file.
 */

// Enable DEV config_split.
$config['config_split.config_split.devel']['status'] = TRUE;

// Enable local development services.
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';

// Disable caches.
$settings['cache']['bins']['render'] = 'cache.backend.null';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';
$settings['cache']['bins']['page'] = 'cache.backend.null';


$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

// Set trusted host pattern to allow everything.
$settings['trusted_host_patterns'] = [
  '.*',
];

// Disable prevent normal login for ecas.
$config['cas.settings']['forced_login']['enabled'] = false;
