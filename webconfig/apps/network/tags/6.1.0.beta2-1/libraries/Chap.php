<?php

/**
 * CHAP/PAP secrets configuration class.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2002-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
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

namespace clearos\apps\network;

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

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;

clearos_load_library('base/Engine');
clearos_load_library('base/File');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;

clearos_load_library('base/Engine_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * CHAP/PAP secrets configuration class.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2002-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Chap extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // M E M B E R S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_SECRETS_CHAP = '/etc/ppp/chap-secrets';
    const FILE_SECRETS_PAP = '/etc/ppp/pap-secrets';
    const LINE_DONE = -3;
    const LINE_DELETE = -2;
    const LINE_ADD = -1;
    const LINE_DEFINED = 0;
    const CONSTANT_ANY = '*';

    /**
     * @var configuration array
     */
    protected $secrets = array();

    /**
     * @var configuration loaded flag
     */
    protected $is_loaded = FALSE;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Chap constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Add a secret to the CHAP/PAP secrets file.
     *
     * @param string $username username
     * @param string $password password
     * @param string $server   server name
     * @param string $ip       IP address
     *
     * @return void
     * @throws Exception
     */

    public function add_secret($username, $password, $server = self::CONSTANT_ANY, $ip = self::CONSTANT_ANY)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load();

        if (isset($this->secrets[$username]))
            unset($this->secrets[$username]);

        $this->secrets[$username]['password'] = $password;
        $this->secrets[$username]['server'] = $server;
        $this->secrets[$username]['ip'] = $ip;
        $this->secrets[$username]['linestate'] = self::LINE_ADD;

        $this->_save();
    }

    /**
     * Deletes a secret from the CHAP/PAP secrets file. 
     * 
     * @param string $username username
     *
     * @return  void
     */

    public function delete_secret($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load();

        if (! isset($this->secrets[$username]))
            return;

        $this->secrets[$username]['linestate'] = self::LINE_DELETE;
        $this->_save();
    }

    /**
     * Returns an array of secrets from the CHAP/PAP secrets file.
     *
     * @return array list of secrets.
     * @throws Exception
     */

    public function get_secrets() 
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load();

        return $this->secrets;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Load CHAP/PAP secrets file in to local array.
     *
     * @access private
     * @return void
     * @throws Exception
     */

    protected function _load()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->loaded = FALSE;
        $this->secrets = array();

        $file = new File(self::FILE_SECRETS_CHAP);
        if (!$file->exists())
            $file->create('root', 'root', '600');
        else
            $file->chown('root', 'root');

        $linecount = 0;
        $lines = $file->get_contents_as_array();

        foreach ($lines as $line) {
            if (! preg_match("/^#/", $line)) {
                $linedata = preg_split('/[\s]+/', $line, 4);
                $username = preg_replace('/"/', '', $linedata[0]);
                $this->secrets[$username]['linestate'] = self::LINE_DEFINED;
                $this->secrets[$username]['server'] = preg_replace('/"/', '', $linedata[1]);
                $this->secrets[$username]['password'] = preg_replace('/"/', '', $linedata[2]);
                $this->secrets[$username]['ip'] = preg_replace('/"/', '', $linedata[3]);
            }

            $linecount++;
        }

        $this->loaded = TRUE;
    }

    /**
     * Saves local array to CHAP/PAP secrets file.
     *
     * @access private
     * @return void
     * @throws Exception
     */

    protected function _save()
    {
        clearos_profile(__METHOD__, __LINE__);

        $filedata = '';

        $this->loaded = FALSE;

        foreach ($this->secrets as $username => $value) {
            if (isset($this->secrets[$username]['linestate'])
                && ($this->secrets[$username]['linestate'] == self::LINE_DELETE)
            ) {
                continue;
            } else {
                $filedata .= $this->_format_line(
                    $username, 
                    $this->secrets[$username]['password'],
                    $this->secrets[$username]['server'],
                    $this->secrets[$username]['ip']
                );
            } 
        }

        $file_chap = new File(self::FILE_SECRETS_CHAP . '.cctmp');
        if ($file_chap->exists())
            $file_chap->delete();

        $file_pap = new File(self::FILE_SECRETS_PAP . '.cctmp');
        if ($file_pap->exists())
            $file_pap->delete();

        $file_chap->create('root', 'root', '0600');
        $file_pap->create('root', 'root', '0600');

        $file_chap->add_lines($filedata);
        $file_pap->add_lines($filedata);

        $file_chap->move_to(self::FILE_SECRETS_CHAP);
        $file_pap->move_to(self::FILE_SECRETS_PAP);
    }

    /**
     * Returns the line entry with the proper formatting.
     *
     * @param string $username username
     * @param string $password password
     * @param string $server   server name
     * @param string $ip       IP address
     *
     * @access private
     * @return string formatted CHAP/PAP line
     */

    protected function _format_line($username, $password, $server, $ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        $username = "\"$username\"";
        $password = "\"$password\"";

        if ($server != self::CONSTANT_ANY)
            $server = "\"$server\"";

        if ($ip != self::CONSTANT_ANY)
            $server = "\"$server\"";

        return sprintf("%s %s %s %s\n", $username, $server, $password, $ip);
    }
}
