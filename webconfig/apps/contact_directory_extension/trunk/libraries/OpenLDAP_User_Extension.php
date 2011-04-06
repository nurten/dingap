<?php

/**
 * Contact OpenLDAP user extension.
 *
 * @category   Apps
 * @package    Contact_Directory_Extension
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/contact_directory_extension/
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

namespace clearos\apps\contact_directory_extension;

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

use \clearos\apps\base\Country as Country;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\openldap\Utilities as Utilities;

clearos_load_library('base/Country');
clearos_load_library('base/Engine');
clearos_load_library('openldap/Utilities');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Contact OpenLDAP user extension.
 *
 * @category   Apps
 * @package    Contact_Directory_Extension
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/contact_directory_extension/
 */

class OpenLDAP_User_Extension extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $info_map = array();
    protected $name = 'Contact Directory Extension'; // FIXME: translate

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Contact OpenLDAP_User_Extension constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        include clearos_app_base('contact_directory_extension') . '/config/user_map.php';

        $this->info_map = $info_map;
    }

    /** 
     * Add LDAP attributes hook.
     *
     * @param array $user_info user information in hash array
     * @param array $ldap_object LDAP object
     *
     * @return array LDAP attributes
     * @throws Engine_Exception
     */

    public function add_attributes_hook($user_info, $ldap_object)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Set defaults
        //-------------

        /*
        if (! isset($user_info['contact']['mail']))
            $user_info['contact']['mail'] = $this->username . "@" . $directory->get_base_internet_domain();
        */

        // Convert to LDAP attributes
        //---------------------------

        $attributes = Utilities::convert_array_to_attributes($user_info['contact'], $this->info_map);

        return $attributes;
    }

    /**
     * Returns user info hash array.
     *
     * @param array $attributes LDAP attributes
     *
     * @return array user info array
     * @throws Engine_Exception
     */

    public function get_info_hook($attributes)
    {
        clearos_profile(__METHOD__, __LINE__);

        $info = Utilities::convert_attributes_to_array($attributes, $this->info_map);

        return $info;
    }

    /**
     * Returns user info map hash array.
     *
     * @return array user info array
     * @throws Engine_Exception
     */

    public function get_info_map_hook()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->info_map;
    }

    /** 
     * Update LDAP attributes hook.
     *
     * @param array $user_info user information in hash array
     * @param array $ldap_object LDAP object
     *
     * @return array LDAP attributes
     * @throws Engine_Exception
     */

    public function update_attributes_hook($user_info, $ldap_object)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Return if nothing needs to be done
        //-----------------------------------

        if (! isset($user_info['contact']))
            return array();

        // Convert to LDAP attributes
        //---------------------------

        $attributes = Utilities::convert_array_to_attributes($user_info['contact'], $this->info_map);

        return $attributes;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for city.
     *
     * @param string $city city
     *
     * @return string error message if city is invalid
     */

    public function validate_city($city)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $city))
            return lang('contact_city_is_invalid');
    }

    /**
     * Validation routine for country.
     *
     * @param string $country country
     *
     * @return string error message if country is invalid
     */

    public function validate_country($country)
    {
        clearos_profile(__METHOD__, __LINE__);

        $country_object = new Country();
        $country_list = $country_object->get_list();

        if (! array_key_exists($country, $country_list))
            return lang('contact_country_is_invalid');
    }

    /**
     * Validation routine for fax number.
     *
     * @param string $number fax number
     *
     * @return string error message if fax number is invalid
     */

    public function validate_fax_number($number)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $number))
            return lang('contact_fax_is_invalid');
    }

    /**
     * Validation routine for e-mail address.
     *
     * @param string $mail e-mail address
     *
     * @return string error message if e-mail address invalid
     */

    public function validate_email($address)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match("/^([a-z0-9_\-\.\$]+)@/", $address))
            return lang('contact_email_is_invalid');
    }

    /**
     * Validation routine for mobile number.
     *
     * @param string $number mobile number
     *
     * @return string error message if mobile number is invalid
     */

    public function validate_mobile_number($number)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $number))
            return lang('contact_mobile_number_is_invalid');
    }

    /**
     * Validation routine for organization.
     *
     * @param string $organization organization
     *
     * @return string error message if organization is invalid
     */

    public function validate_organization($organization)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $organization))
            return lang('contact_organization_is_invalid');
    }

    /**
     * Validation routine for post office box.
     *
     * @param string $pobox post office box
     *
     * @return string error message if post office box is invalid
     */

    public function validate_post_office_box($pobox)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $pobox))
            return lang('contact_post_office_box_is_invalid');
    }

    /**
     * Validation routine for postal code.
     *
     * @param string $postal_code postal code
     *
     * @return string error message if postal code is invalid
     */

    public function validate_postal_code($postal_code)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $postal_code))
            return lang('contact_postal_code_is_invalid');
    }

    /**
     * Validation routine for room number.
     *
     * @param string $room room number
     *
     * @return string error message if room number is invalid
     */

    public function validate_room_number($room)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $room))
            return lang('contact_room_number_is_invalid');
    }

    /**
     * Validation routine for state or province.
     *
     * @param string $region region
     *
     * @return string error message if region is invalid
     */

    public function validate_region($region)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $region))
            return lang('contact_region_is_invalid');
    }

    /**
     * Validation routine for street.
     *
     * @param string $street street
     *
     * @return string error message if street is invalid
     */

    public function validate_street($street)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $street))
            return lang('contact_street_is_invalid');
    }

    /**
     * Validation routine for telephone number.
     *
     * @param string $number telephone number
     *
     * @return string error message if telephone number is invalid
     */

    public function validate_telephone_number($number)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $number))
            return lang('contact_telephone_number_is_invalid');
    }

    /**
     * Validation routine for unit.
     *
     * @param string $unit unit
     *
     * @return string error message if unit is invalid
     */

    public function validate_unit($unit)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $unit))
            return lang('contact_unit_is_invalid');
    }
}