<?php

$app['basename'] = 'graphical_console';
$app['version'] = '5.9.9.0';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'Graphical console tool.';
$app['description'] = 'Graphical console tool for configuring the network.'; // FIXME: translate

$app['name'] = 'Graphical Console'; // FIXME: translate
$app['category'] = lang('base_category_system');
$app['subcategory'] = lang('base_subcategory_settings');

// Packaging
//    'mesa-dri-drivers',
$app['core_dependencies'] = array(
    'app-base-core',
    'dbus-x11',
    'gconsole',
    'ratpoison',
    'urw-fonts',
    'xorg-x11-drivers',
    'xorg-x11-server-Xorg',
    'xorg-x11-xinit',
);

$app['core_file_manifest'] = array( 
   'xinitrc' => array(
        'target' => '/var/lib/clearconsole/.xinitrc',
        'mode' => '0644',
        'onwer' => 'root',
        'group' => 'root',
    ),
   'Xdefaults' => array(
        'target' => '/var/lib/clearconsole/.Xdefaults',
        'mode' => '0644',
        'onwer' => 'root',
        'group' => 'root',
    ),
);