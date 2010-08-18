<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2009 Point Clark Networks.
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

require_once("../../gui/Webconfig.inc.php");
require_once("../../api/ClamAv.class.php");
require_once("../../api/AntispamUpdates.class.php");
// require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Subscription information
//
///////////////////////////////////////////////////////////////////////////////

// TODO: implement this better
require_once("clearcenter-status.inc.php");
$header = "<script type='text/javascript' src='/admin/clearcenter-status.js.php?service=Antispam'></script>\n";

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

// TODO: translate
define("WEB_LANG_PAGE_TITLE", "Antispam Updates");
define("WEB_LANG_PAGE_INTRO", "The antispam update service providesa updated signatures and optimized antispam rules");

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE, "default", $header);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$clamav = new ClamAv();

try {
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

WebDialogServiceStatus(AntispamUpdates::CONSTANT_NAME, CLAMAV_LANG_ANTISPAM_UPDATES);
DisplayAntispamUpdates();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayAntispamUpdates()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAntispamUpdates()
{
	// HTML
	//-----

	WebFormOpen();
	WebTableOpen(WEB_LANG_PAGE_TITLE);
	echo "<tr><td>TODO</td></tr>";
	WebTableClose();
	WebFormClose();
}

// vim: syntax=php ts=4
?>
