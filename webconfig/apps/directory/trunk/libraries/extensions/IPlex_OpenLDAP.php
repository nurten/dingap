<?php

/**
 * IPlex OpenLDAP directory extension.
 *
 * @category   Apps
 * @package    Directory
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/directory/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\directory\extensions;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\directory\OpenLDAP as OpenLDAP;
use \clearos\apps\directory\OpenLDAP_Utilities as OpenLDAP_Utilities;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Engine');
clearos_load_library('directory/OpenLDAP');
clearos_load_library('directory/OpenLDAP_Utilities');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;

clearos_load_library('base/Engine_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * IPlex OpenLDAP directory extension.
 *
 * @category   Apps
 * @package    Directory
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/directory/
 */

class IPlex_OpenLDAP extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $ldaph = NULL;
    protected $info_map = array();
    protected $dn;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * IPlex_OpenLDAP constructor.
     */

    public function __construct($dn)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->dn = $dn; 

        $this->info_map = array(
            'account_flags' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_account_flags',
                'objectclass' => 'sambaSamAccount',
                'attribute' => 'sambaAcctFlags'
            ),
        );
    }

    /** 
     * Converts attributes to PHP array.
     *
     * @param array $attributes LDAP attributes
     * @return string default home server
     * @throws Engine_Exception
     */

    public function convert_attributes_to_array($attributes)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

       $info = OpenLDAP_Utilities::convert_attributes_to_array($attributes, $this->info_map);

        /*
            // The 'D' flag indicates a disabled account
            if (isset($attributes['sambaAcctFlags']) && !preg_match('/D/', $attributes['sambaAcctFlags'][0]))
                $userinfo['sambaFlag'] = TRUE;
            else
                $userinfo['sambaFlag'] = FALSE;

            // The 'L' flag indicates a locaked account
            if (isset($attributes['sambaAcctFlags']) && preg_match('/L/', $attributes['sambaAcctFlags'][0]))
                $userinfo['sambaAccountLocked'] = TRUE;
            else
                $userinfo['sambaAccountLocked'] = FALSE;
        */

        return $info;
    }

    /**
     * Creates an LDAP handle.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _get_ldap_handle()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->ldaph = OpenLDAP_Utilities::get_ldap_handle();
    }
}
