<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'base';
$app['version'] = '5.9.9.3';
$app['release'] = '2.1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('base_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('base_app_name');
$app['category'] = lang('base_category_system');
$app['subcategory'] = lang('base_subcategory_settings');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

// Special case -- hide this from controller list
// $app['controllers']['base']['title'] = lang('base_dashboard');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'clearos-base',
    'clearos-framework',
    'system-theme',
    'chkconfig',
    'coreutils',
    'file',
    'initscripts',
    'passwd',
    'rpm',
    'shadow-utils',
    'sudo',
    'sysvinit-tools',
    'syswatch',
    'util-linux-ng',
    'webconfig-mod_ssl',
    'webconfig-php',
    'webconfig-utils'
);


$app['core_file_manifest'] = array(
   'webconfig-restart' => array(
        'target' => '/usr/sbin/webconfig-restart',
        'mode' => '0755',
        'owner' => 'root',
        'group' => 'root',
    ),
   'base' => array( 'target' => '/var/clearos/base/access_control/public' ),
);

$app['core_directory_manifest'] = array(
    '/var/clearos/base' => array(),
    '/var/clearos/base/access_control' => array(),
    '/var/clearos/base/access_control/authenticated' => array(),
    '/var/clearos/base/access_control/custom' => array(),
    '/var/clearos/base/access_control/public' => array(),
);
