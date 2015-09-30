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
 * This file contains a class definition for the System Settings resource
 *
 * @package    mod_lti
 * @copyright  2014 Vital Source Technologies http://vitalsource.com
 * @author     Stephen Vickers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace ltiservice_toolsettings\resource;

defined('MOODLE_INTERNAL') || die();

/**
 * A resource implementing the System-level (ToolProxy) Settings.
 *
 * @copyright  2014 Vital Source Technologies http://vitalsource.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class systemsettings extends \mod_lti\ltiservice\resource_base {

    public function __construct($service) {

        parent::__construct($service);
        $this->id = 'ToolProxySettings';
        $this->template = '/toolproxy/{tool_proxy_id}/custom';
        $this->variables[] = 'ToolProxy.custom.url';
        $this->formats[] = 'application/vnd.ims.lti.v2.toolsettings+json';
        $this->formats[] = 'application/vnd.ims.lti.v2.toolsettings.simple+json';
        $this->methods[] = 'GET';
        $this->methods[] = 'PUT';

    }

    public function execute($response) {

        $params = $this->parse_template();
        $tpid = $params['tool_proxy_id'];
        $bubble = optional_param('bubble', null, PARAM_ALPHA);
        $ok = !empty($tpid) && $this->get_service()->check_tool_proxy($tpid, $response->get_request_data());
        if (!$ok) {
            $response->set_code(401);
        }
        $contenttype = $response->get_accept();
        $simpleformat = !is_null($contenttype) && ($contenttype == $this->formats[1]);
        if ($ok) {
            $ok = (is_null($bubble) || ((($bubble == 'distinct') || ($bubble == 'all')))) &&
               (!$simpleformat || is_null($bubble) || ($bubble != 'all')) &&
               (is_null($bubble) || ($response->get_request_method() == 'GET'));
            if (!$ok) {
                $response->set_code(406);
            }
        }

        if ($ok) {
            $systemsettings = lti_get_tool_settings($this->get_service()->get_tool_proxy()->id);
            if ($response->get_request_method() == 'GET') {
                $json = '';
                if ($simpleformat) {
                    $response->set_content_type($this->formats[1]);
                    $json .= "{";
                } else {
                    $response->set_content_type($this->formats[0]);
                    $json .= "{\n  \"@context\":\"http://purl.imsglobal.org/ctx/lti/v2/ToolSettings\",\n  \"@graph\":[\n";
                }
                $json .= \ltiservice_toolsettings\service\toolsettings::settings_to_json($systemsettings, $simpleformat,
                    'ToolProxy', $this);
                if ($simpleformat) {
                    $json .= "\n}";
                } else {
                    $json .= "\n  ]\n}";
                }
                $response->set_body($json);
            } else { // PUT.
                $settings = null;
                if ($response->get_content_type() == $this->formats[0]) {
                    $json = json_decode($response->get_request_data());
                    $ok = !is_null($json);
                    if ($ok) {
                        $ok = isset($json->{"@graph"}) && is_array($json->{"@graph"}) && (count($json->{"@graph"}) == 1) &&
                              ($json->{"@graph"}[0]->{"@type"} == 'ToolProxy');
                    }
                    if ($ok) {
                        $settings = $json->{"@graph"}[0]->custom;
                    }
                } else {  // Simple JSON.
                    $json = json_decode($response->get_request_data(), true);
                    $ok = !is_null($json);
                    if ($ok) {
                        $ok = is_array($json);
                    }
                    if ($ok) {
                        $settings = $json;
                    }
                }
                if ($ok) {
                    lti_set_tool_settings($settings, $this->get_service()->get_tool_proxy()->id);
                } else {
                    $response->set_code(406);
                }
            }
        }

    }

    public function parse_value($value) {

        $value = str_replace('$ToolProxy.custom.url', parent::get_endpoint(), $value);

        return $value;

    }

}
