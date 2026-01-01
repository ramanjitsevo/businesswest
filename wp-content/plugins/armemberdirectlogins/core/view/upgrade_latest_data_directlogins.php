<?php

global $arm_direct_logins_newdbversion;

$arm_direct_logins_version = get_option('arm_direct_logins_version');

$arm_direct_logins_newdbversion = '2.1';

update_option('arm_direct_logins_old_version', $arm_direct_logins_version);
update_option('arm_direct_logins_version', $arm_direct_logins_newdbversion);

$arm_version_updated_date_key = 'arm_direct_logins_version_updated_date_'.$arm_direct_logins_newdbversion;
$arm_version_updated_date = current_time('mysql');
update_option($arm_version_updated_date_key, $arm_version_updated_date);