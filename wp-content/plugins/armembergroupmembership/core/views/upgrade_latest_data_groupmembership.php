<?php

global $wpdb, $arm_gm_newdbversion;

$arm_gm_version = get_option('arm_gm_version');

if (version_compare($arm_gm_version, '1.1', '<')) {

	global $arm_gm_class;
	$arm_gm_class->arm_gm_capabilities_assign();
}

$arm_gm_newdbversion = '2.1';

update_option('arm_gm_old_version', $arm_gm_version);
update_option('arm_gm_version', $arm_gm_newdbversion);

$arm_version_updated_date_key = 'arm_gm_version_updated_date_'.$arm_gm_newdbversion;
$arm_version_updated_date = current_time('mysql');
update_option($arm_version_updated_date_key, $arm_version_updated_date);