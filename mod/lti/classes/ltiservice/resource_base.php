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
 * This file contains an abstract definition of an LTI resource
 *
 * @package    mod_lti
 * @copyright  2014 Vital Source Technologies http://vitalsource.com
 * @author     Stephen Vickers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace mod_lti\ltiservice;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/lti/locallib.php');


abstract class resource_base {

    private $service;
    protected $type;
    protected $id;
    protected $template;
    protected $variables;
    protected $formats;
    protected $methods;
    protected $params;


    public function __construct($service) {

        $this->service = $service;
        $this->type = 'RestService';
        $this->id = null;
        $this->template = null;
        $this->methods = array();
        $this->variables = array();
        $this->formats = array();
        $this->methods = array();
        $this->params = null;

    }

    public function get_path() {

        return $this->get_template();

    }

    public function get_type() {

        return $this->type;

    }

    public function get_service() {

        return $this->service;

    }

    public function get_id() {

        return $this->id;

    }

    public function get_template() {

        return $this->template;

    }

    public function get_methods() {

        return $this->methods;

    }

    public function get_formats() {

        return $this->formats;

    }

    public function get_variables() {

        return $this->variables;

    }

    public function get_endpoint() {

        global $CFG;

        $this->parse_template();
        $url = $this->get_service()->get_service_path() . $this->get_template();
        foreach ($this->params as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
        }
        $toolproxy = $this->get_service()->get_tool_proxy();
        if (!is_null($toolproxy)) {
            $url = str_replace('{tool_proxy_id}', $toolproxy->guid, $url);
        }

        return $url;

    }

    public abstract function execute($response);

    public function parse_value($value) {

        return $value;

    }

    protected function parse_template() {

        if (is_null($this->params)) {
            $this->params = array();
            if (isset($_SERVER['PATH_INFO'])) {
                $path = explode('/', $_SERVER['PATH_INFO']);
                $parts = explode('/', $this->get_template());
                for ($i = 0; $i < count($parts); $i++) {
                    if ((substr($parts[$i], 0, 1) == '{') && (substr($parts[$i], -1) == '}')) {
                        $value = '';
                        if ($i < count($path)) {
                            $value = $path[$i];
                        }
                        $this->params[substr($parts[$i], 1, -1)] = $value;
                    }
                }
            }
        }

        return $this->params;

    }

}
