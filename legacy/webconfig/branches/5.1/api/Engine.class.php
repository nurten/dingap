<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006 Point Clark Networks.
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

/**
 * Base class for the API.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once(dirname(__FILE__) . '/../common/Globals.inc.php');
require_once(dirname(__FILE__) . '/../common/Logger.class.php');
require_once(dirname(__FILE__) . '/../common/Error.class.php');

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S E S
///////////////////////////////////////////////////////////////////////////////

/**
 * Base exception for all exceptions in the API.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class EngineException extends Exception
{
	/**
	 * EngineException constructor.
	 *
	 * Unlike a core PHP exception, the message and code parameters are required.
	 *
	 * - COMMON_DEBUG - debug message
	 * - COMMON_VALIDATION - validation error message
	 * - COMMON_INFO - informational message (e.g. dynamic DNS updated with IP w.x.y.z)
	 * - COMMON_NOTICE - pedantic warnings (e.g. dynamic DNS updated with IP w.x.y.z)
	 * - COMMON_WARNING - normal but significant errors (e.g. dynamic DNS could not detect WAN IP)
	 * - COMMON_ERROR - errors that should not happen under normal circumstances
	 * - COMMON_FATAL - really nasty errors
	 *
	 * @param string $message error message
	 * @param integer $code error code
	 * @return void
	 */

	public function __construct($message, $code)
	{
		parent::__construct($message, $code);

		if ($code >= COMMON_WARNING)
			Logger::LogException($this, true);
	}
}

/**
 * Validation exception for API.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class ValidationException extends EngineException
{
	/**
	 * ValidationException constructor.
	 *
	 * @param string $message error message
	 * @return void
	 */

	public function __construct($message)
	{
		parent::__construct($message, COMMON_VALIDATION);
	}
}

/**
 * Custom configuration discovered exception.
 *
 * In some instances, it may be impossible to manage a configuration file if
 * it has been customized.  For instance, the Amavis configuration file uses
 * regular expressions in the banned_filename_re parameter.  These regular
 * expressions are flexible, but are impossible to decipher without showing
 * the end user some not-so-user-friendly details.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class CustomConfigurationException extends EngineException
{
	/**
	 * Custom configuration constructor.
	 *
	 * @param string $file file name
	 * @return void
	 */

	public function __construct($file)
	{
		parent::__construct("Custom configuration discovered" . " - " . $file, COMMON_NOTICE);
	}
}

/**
 * Duplicate entry exception for API.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class DuplicateException extends EngineException
{
	/**
	 * DuplicateException constructor.
	 *
	 * @param string $message error message
	 * @return void
	 */

	public function __construct($message)
	{
		parent::__construct($message, COMMON_WARNING);
	}
}

/**
 * Security violation exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2007, Point Clark Networks
 */

class SecurityViolationException extends EngineException
{
	/**
	 * SecurityViolationException constructor.
	 *
	 * @return void
	 */

	public function __construct()
	{
		parent::__construct("Security violation", COMMON_ERROR);
	}
}

/**
 * SQL exception for API.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class SqlException extends EngineException
{
	/**
	 * SqlException constructor.
	 *
	 * @param string $message error message
	 * @return void
	 */

	public function __construct($message)
	{
		parent::__construct($message, COMMON_WARNING);
	}
}

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Base class for the API.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * @var array validation error queue
	 */

	protected $errors = array();

	const COMMAND_API = "/usr/bin/api";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Engine constructor.
	 *
	 * @return void
	 */

	function __construct()
	{
		if (! file_exists("/etc/system/nosecurity")) {
			$user = get_current_user();

			if (!(($user == "root") || ($user == "webconfig")))
				throw new SecurityViolationException();
		}

		require_once(GlobalGetLanguageTemplate(preg_replace("/Engine/", "Locale",__FILE__)));
	}

	/**
	 * Prints a message to the log.
	 *
	 * The following log levels are used:
	 *
	 * - COMMON_DEBUG - debug messages
	 * - COMMON_VALIDATION - validation error message
	 * - COMMON_INFO - informational messages (e.g. dynamic DNS updated with IP w.x.y.z)
	 * - COMMON_NOTICE - pedantic warnings (e.g. dynamic DNS updated with IP w.x.y.z)
	 * - COMMON_WARNING - normal but significant errors (e.g. dynamic DNS could not detect WAN IP)
	 * - COMMON_ERROR - errors that should not happen under normal circumstances
	 * - COMMON_FATAL - really nasty errors
	 *
	 * @param  int  $code  error code
	 * @param  string  $message  short and informative message
	 * @param  string  $tag  identifier (usually the method)
	 * @param  int  $line  line number
	 * @return  void
	 * @static
	 */

	static function Log($code, $message, $tag, $line)
	{
		$error = new Error($code, $message, $tag, $line, null, true);
		Logger::Log($error);
	}

	///////////////////////////////////////////////////////////////////////////////
	// E R R O R  H A N D L I N G
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Add a validation error to the queue.
	 *
	 * @param string $message error message
	 * @param string $tag tag (usually the method call)
	 * @param integer $line line number
	 * @return void
	 */

	protected function AddValidationError($message, $tag, $line)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$error = new Error(COMMON_VALIDATION, $message, $tag, $line, null, Error::TYPE_ERROR, true);
		$this->errors[] = $error;

		if (COMMON_DEBUG_MODE)
			Logger::Log($error);
	}

	/**
	 * Returns an array of validation error messages.
	 *
	 * @param boolean $purge  (optional) if true, the error queue will be purged.
	 * @return array validation errors
	 */

	public function GetValidationErrors($purge = false)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$error_messages = array();

		foreach ($this->errors as $error)
			$error_messages[] = $error->GetMessage();

		if ($purge)
			$this->errors = array();

		return $error_messages;
	}

	/**
	 * Returns an array of validation error objects.
	 *
	 * @param boolean $purge  (optional) if true, the error queue will be purged.
	 * @return array validation errors
	 */

	public function CopyValidationErrors($purge = false)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$errors_copy = $this->errors;

		if ($purge)
			$this->errors = array();

		return $errors_copy;
	}

	/**
	 * Returns true if validation errors exist.
	 *
	 * @return boolean true if validation errors exist
	 */

	public function CheckValidationErrors()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (empty($this->errors))
			return false;
		else
			return true;
	}

	/**
	 * @access private
	 */

	public function __destruct()
	{
		// A bit noisy
		// if (COMMON_DEBUG_MODE)
		//	$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);
	}
}

// vim: syntax=php ts=4
?>
