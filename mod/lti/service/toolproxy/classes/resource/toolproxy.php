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
 * This file contains a class definition for the Tool Proxy resource
 *
 * @package    mod_lti
 * @copyright  2014 Vital Source Technologies http://vitalsource.com
 * @author     Stephen Vickers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace ltiservice_toolproxy\resource;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/lti/OAuth.php');
require_once($CFG->dirroot . '/mod/lti/TrivialStore.php');

// TODO: Switch to core oauthlib once implemented - MDL-30149.
use moodle\mod\lti as lti;

/**
 * A resource implementing the Tool Proxy.
 *
 * @copyright  2014 Vital Source Technologies http://vitalsource.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class toolproxy extends \mod_lti\ltiservice\resource_base {

    public function __construct($service) {

        parent::__construct($service);
        $this->id = 'ToolProxy.collection';
        $this->template = '/toolproxy';
        $this->formats[] = 'application/vnd.ims.lti.v2.toolproxy+json';
        $this->methods[] = 'POST';

    }

    public function execute($response) {

        $ok = $this->get_service()->check_tool_proxy(null, $response->get_request_data());
        $toolproxy = $this->get_service()->get_tool_proxy();
        if ($ok) {
            $toolproxyjson = json_decode($response->get_request_data());
            $ok = !is_null($toolproxyjson);
            $ok = $ok && isset($toolproxyjson->tool_profile->product_instance->product_info->product_family->vendor->code);
            $ok = $ok && isset($toolproxyjson->security_contract->shared_secret);
            $ok = $ok && isset($toolproxyjson->tool_profile->resource_handler);
        }
        if ($ok) {
            $baseurl = '';
            if (isset($toolproxyjson->tool_profile->base_url_choice[0]->default_base_url)) {
                $baseurl = $toolproxyjson->tool_profile->base_url_choice[0]->default_base_url;
            }
            $securebaseurl = '';
            if (isset($toolproxyjson->tool_profile->base_url_choice[0]->secure_base_url)) {
                $securebaseurl = $toolproxyjson->tool_profile->base_url_choice[0]->secure_base_url;
            }
            $resources = $toolproxyjson->tool_profile->resource_handler;
            foreach ($resources as $resource) {
                $icon = new \stdClass();
                if (isset($resource->icon_info[0]->default_location->path)) {
                    $icon->path = $resource->icon_info[0]->default_location->path;
                }
                $tool = new \stdClass();
                $tool->name = $resource->resource_name->default_value;
                $messages = $resource->message;
                foreach ($messages as $message) {
                    if ($message->message_type == 'basic-lti-launch-request') {
                        $tool->path = $message->path;
                        $tool->enabled_capability = $message->enabled_capability;
                        $tool->parameter = $message->parameter;
                    }
                }
                $config = new \stdClass();
                $config->lti_toolurl = "{$baseurl}{$tool->path}";
                $config->lti_typename = $tool->name;
                $config->lti_coursevisible = 1;
                $config->lti_forcessl = 0;

                $type = new \stdClass();
                $type->state = LTI_TOOL_STATE_PENDING;
                $type->toolproxyid = $toolproxy->id;
                $type->enabledcapability = implode("\n", $tool->enabled_capability);
                $type->parameter = self::lti_extract_parameters($tool->parameter);
                if (!empty($icon->path)) {
                    $type->icon = "{$baseurl}{$icon->path}";
                    if (!empty($securebaseurl)) {
                        $type->secureicon = "{$securebaseurl}{$icon->path}";
                    }
                }
                $ok = (lti_add_type($type, $config) !== false);
            }
            if (isset($toolproxyjson->custom)) {
                lti_set_tool_settings($toolproxyjson->custom, $toolproxy->id);
            }
        }
        if ($ok) {
            $toolproxy->state = LTI_TOOL_PROXY_STATE_ACCEPTED;
            $toolproxy->toolproxy = $response->get_request_data();
            $toolproxy->secret = $toolproxyjson->security_contract->shared_secret;
            $toolproxy->vendorcode = $toolproxyjson->tool_profile->product_instance->product_info->product_family->vendor->code;

            $url = $this->get_endpoint();
            $body = <<< EOD
{
  "@context" : "http://purl.imsglobal.org/ctx/lti/v2/ToolProxyId",
  "@type" : "ToolProxy",
  "@id" : "{$url}",
  "tool_proxy_guid" : "{$toolproxy->guid}"
}
EOD;
            $response->set_code(201);
            $response->set_content_type('application/vnd.ims.lti.v2.toolproxy.id+json');
            $response->set_body($body);
        } else {
            $toolproxy->state = LTI_TOOL_PROXY_STATE_REJECTED;
            $response->set_code(400);
        }
        lti_update_tool_proxy($toolproxy);
    }

    /**
     * Extracts the message parameters from the tool proxy entry
     *
     * @param array $parameters     Parameter section of a message
     *
     * @return String  containing parameters
     */
    private static function lti_extract_parameters($parameters) {

        $params = array();
        foreach ($parameters as $parameter) {
            if (isset($parameter->variable)) {
                $value = '$' . $parameter->variable;
            } else {
                $value = $parameter->fixed;
                if (strlen($value) > 0) {
                    $first = substr($value, 0, 1);
                    if (($first == '$') || ($first == '\\')) {
                        $value = '\\' . $value;
                    }
                }
            }
            $params[] = "{$parameter->name}={$value}";
        }

        return implode("\n", $params);

    }

}
