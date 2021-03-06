<?php

/**
 * Javascript helper for Mail Archive.
 *
 * @category   Apps
 * @package    Mail_Archive
 * @subpackage Javascript
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearcenter.com/support/documentation/clearos/mail_archive/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

clearos_load_language('mail_archive');
clearos_load_language('base');

header('Content-Type: application/x-javascript');

echo "

$(document).ready(function() {

    $('#encrypt_password').attr('autocomplete', 'off');
    if ($('#encrypt').val() == 0) {
        $('#encrypt_password').val('');
        $('#encrypt_password').attr('disabled', true);
    }

    $('#encrypt').change(function(event) {
        if ($('#encrypt').val() == 0)
            $('#encrypt_password').attr('disabled', true);
        else
            $('#encrypt_password').attr('disabled', false);
    });

    if ($(location).attr('href').match('.*\/search(\/.*$|$)') != null) {
        refresh_filters();
        $('.theme-form input:text').each(function() {
            $(this).keyup(function() {
                refresh_filters();
            });
        });
    }
});

function refresh_filters() {
    start_hide = 2;
    for (index = 1; index <= 5; index++) {
        if ($('#search\\.' + index).val() == undefined || $('#search\\.' + index).val() == '') {
            start_hide = index + 1;
            break;
        }
    }
            
    // Never do anything with first filter...start index at 2
    for (index = 2; index <= 5; index++) {
        if (index >= start_hide) {
            $('#filter_' + index).hide();
            $('#field\\\\.' + index + '_field').hide();
            $('#pattern\\\\.' + index + '_field').hide();
            $('#search\\\\.' + index + '_field').hide();
        } else {
            $('#filter_' + index).show();
            $('#field\\\\.' + index + '_field').show();
            $('#pattern\\\\.' + index + '_field').show();
            $('#search\\\\.' + index + '_field').show();
        }
    }
}
";
// vim: syntax=php ts=4
