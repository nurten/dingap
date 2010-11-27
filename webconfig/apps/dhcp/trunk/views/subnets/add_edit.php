<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 ClearFoundation
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

// FIXME: what to do with read-only form values?
// FIXME: what to do with validating IP ranges and its ilk

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('dhcp');

///////////////////////////////////////////////////////////////////////////////
// Form modes
///////////////////////////////////////////////////////////////////////////////

if ($formtype === 'edit') {
	$buttons =
		form_submit_update('submit') .
		anchor_cancel('/app/dhcp/subnets/') .
		anchor_delete('/app/dhcp/subnets/delete/' . $interface);
} else {
	$buttons =	
		form_submit_add('submit') .
		anchor_cancel('/app/dhcp/subnets/');
}

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('/dhcp/subnets/edit/' . $interface);
echo form_fieldset(lang('dhcp_subnet'));

///////////////////////////////////////////////////////////////////////////////
// Form fields
///////////////////////////////////////////////////////////////////////////////

echo cos_form_input('interface', $interface, lang('dhcp_network_interface'), TRUE);
echo cos_form_input('network', $network, lang('dhcp_network'), TRUE);
echo cos_form_dropdown('lease_time', $lease_times, $lease_time, lang('dhcp_lease_time'));
echo cos_form_input('gateway', $gateway, lang('dhcp_gateway'));
echo cos_form_input('start', $start, lang('dhcp_ip_range_start'));
echo cos_form_input('end', $end, lang('dhcp_ip_range_end'));
echo cos_form_input('dns1', $dns[0], lang('dhcp_dns') . " #1");
echo cos_form_input('dns2', $dns[1], lang('dhcp_dns') . " #2");
echo cos_form_input('dns3', $dns[2], lang('dhcp_dns') . " #3");
echo cos_form_input('wins', $wins, lang('dhcp_wins'));
echo cos_form_input('tftp', $tftp, lang('dhcp_tftp'));
echo cos_form_input('ntp', $ntp, lang('dhcp_ntp'));

///////////////////////////////////////////////////////////////////////////////
// Buttons
///////////////////////////////////////////////////////////////////////////////

echo cos_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_fieldset_close();
echo form_close();

// vim: ts=4 syntax=php
