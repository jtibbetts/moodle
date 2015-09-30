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
 * This file contains an abstract definition of an LTI service
 *
 * @package    mod_lti
 * @copyright  2014 Vital Source Technologies http://vitalsource.com
 * @author     Stephen Vickers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace mod_lti\ltiservice;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/lti/locallib.php');
require_once($CFG->dirroot . '/mod/lti/OAuthBody.php');

// TODO: Switch to core oauthlib once implemented - MDL-30149.
use moodle\mod\lti as lti;


abstract class service_base {

    const LTI_VERSION2P0 = 'LTI-2p0';

    protected $id;
    protected $name;
    protected $unsigned;
    private $toolproxy;
    protected $resources;


    public function __construct() {

        $this->id = null;
        $this->name = null;
        $this->unsigned = false;
        $this->toolproxy = null;
        $this->resources = null;

    }

    public function get_id() {

        return $this->id;

    }

    public function get_name() {

        return $this->name;

    }

    public function is_unsigned() {

        return $this->unsigned;

    }

    public function get_tool_proxy() {

        return $this->toolproxy;

    }

    public function set_tool_proxy($toolproxy) {

        $this->toolproxy = $toolproxy;

    }

    abstract public function get_resources();

    public static function get_service_path() {

        $url = new \moodle_url('/mod/lti/services.php');

        return $url->out(false);

    }

    public function parse_value($value) {

        if (is_null($this->resources)) {
            $this->resources = $this->get_resources();
        }
        if (!is_null($this->resources)) {
            foreach ($this->resources as $resource) {
                $value = $resource->parse_value($value);
            }
        }

        return $value;

    }

    public function check_tool_proxy($toolproxyguid, $body = null) {

        $ok = false;
        $toolproxy = null;
        $h = lti\OAuthUtil::get_headers();
        $consumerkey = lti\get_oauth_key_from_headers();
        if (is_null($toolproxyguid)) {
            $toolproxyguid = $consumerkey;
        }

        if (!is_null($toolproxyguid)) {
            $toolproxy = lti_get_tool_proxy_from_guid($toolproxyguid);
            if ($toolproxy !== false) {
                if (!$this->is_unsigned() && ($toolproxy->guid == $consumerkey)) {
                    $ok = $this->check_signature($toolproxy->guid, $toolproxy->secret, $body);
                } else {
                    $ok = $this->is_unsigned();
                }
            }
        }
        if ($ok) {
            $this->toolproxy = $toolproxy;
        }

        return $ok;

    }

    private function check_signature($consumerkey, $secret, $body) {

        $ok = true;
        try {
            // TODO: Switch to core oauthlib once implemented - MDL-30149.
            lti\handle_oauth_body_post($consumerkey, $secret, $body);
        } catch (Exception $e) {
            debugging($e->getMessage() . "\n");
            $ok = false;
        }

        return $ok;

    }

}

