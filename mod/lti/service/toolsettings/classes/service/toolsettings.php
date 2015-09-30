<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains a class definition for the Tool Settings service
 *
 * @package    mod_lti
 * @copyright  2014 Vital Source Technologies http://vitalsource.com
 * @author     Stephen Vickers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace ltiservice_toolsettings\service;

defined('MOODLE_INTERNAL') || die();

/**
 * A service implementing Tool Settings.
 *
 * @copyright  2014 Vital Source Technologies http://vitalsource.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class toolsettings extends \mod_lti\ltiservice\service_base {

    public function __construct() {

        parent::__construct();
        $this->id = 'toolsettings';
        $this->name = 'Tool Settings';

    }

    public function get_resources() {

        if (is_null($this->resources)) {
            $this->resources = array();
            $this->resources[] = new \ltiservice_toolsettings\resource\systemsettings($this);
            $this->resources[] = new \ltiservice_toolsettings\resource\contextsettings($this);
            $this->resources[] = new \ltiservice_toolsettings\resource\linksettings($this);
        }

        return $this->resources;

    }

    public static function distinct_settings(&$systemsettings, &$contextsettings, $linksettings) {

        if (!is_null($systemsettings)) {
            foreach ($systemsettings as $key => $value) {
                if ((!is_null($contextsettings) && array_key_exists($key, $contextsettings)) ||
                    (!is_null($linksettings) && array_key_exists($key, $linksettings))) {
                    unset($systemsettings[$key]);
                }
            }
        }
        if (!is_null($contextsettings)) {
            foreach ($contextsettings as $key => $value) {
                if (!is_null($linksettings) && array_key_exists($key, $linksettings)) {
                    unset($contextsettings[$key]);
                }
            }
        }
    }

    public static function settings_to_json($settings, $simpleformat, $type, $resource) {

        $json = '';
        if (!is_null($resource)) {
            $indent = '';
            if (!$simpleformat) {
                $json .= "    {\n      \"@type\":\"{$type}\",\n";
                $json .= "      \"@id\":\"{$resource->get_endpoint()}\",\n";
                $json .= '      "custom":';
                $json .= "{";
                $indent = '      ';
            }
            $isfirst = true;
            if (!is_null($settings)) {
                foreach ($settings as $key => $value) {
                    if (!$isfirst) {
                        $json .= ",";
                    } else {
                        $isfirst = false;
                    }
                    $json .= "\n{$indent}  \"{$key}\":\"{$value}\"";
                }
            }
            if (!$simpleformat) {
                $json .= "\n{$indent}}\n    }";
            }
        }

        return $json;

    }

}
