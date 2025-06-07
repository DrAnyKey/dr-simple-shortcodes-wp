<?php

if( ! defined('WP_UNINSTALL_PLUGIN') ) exit;

global $wpdb;
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name = 'dr_simple_shortcodes_option'");