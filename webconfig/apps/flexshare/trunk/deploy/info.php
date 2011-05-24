<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'flexshare';
$app['version'] = '5.9.9.0';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = lang('flexshare_app_summary');
$app['description'] = lang('flexshare_app_long_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('flexshare_flexshare');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_file');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'app-tasks-core', 
    'ntpdate >= 4.2.4p8'
);

$app['core_directory_manifest'] = array(
    '/var/flexshare' => array(),
    '/var/flexshare/shares' => array(),
);

$app['core_file_manifest'] = array( 
   'flexshare.conf' => array(
        'target' => '/etc/flexshare.conf',
        'mode' => '0600',
        'onwer' => 'root',
        'group' => 'root',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),

   'updateflexperms' => array(
        'target' => '/usr/sbin/updateflexperms',
        'mode' => '0755',
        'onwer' => 'root',
        'group' => 'root',
    ),
);