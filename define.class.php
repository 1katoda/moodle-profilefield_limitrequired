<?php
// This file is part of profilefield_limitrequired plugin for Moodle - http://moodle.org/
//
// Moodle, as well as this plugin, is free software: you can redistribute it and/or modify
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
 * Text profile field definition.
 *
 * @package    profilefield_limitrequired
 * @copyright 1katoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class profile_define_limitrequired
 *
 * @copyright 1katoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_define_limitrequired extends profile_define_base {

    /**
     * Add elements for creating/editing an AAI text profile field.
     *
     * @param MoodleQuickForm $form
     */
    public function define_form_specific($form) {
        // Default data.
        $form->addElement('text', 'defaultdata', get_string('profiledefaultdata', 'admin'), 'size="50"');
        $form->setType('defaultdata', PARAM_TEXT);

        $form->addElement('select', 'param1', get_string('fieldtype', 'profilefield_limitrequired'), [
            get_string('text', 'profilefield_limitrequired'),
            get_string('password', 'profilefield_limitrequired'),
            // get_string('email', 'profilefield_limitrequired'),
            // get_string('number', 'profilefield_limitrequired'),
        ]);
        $form->setType('param1', PARAM_INT);

        // Param 3 for limitrequired type contains
        // user ids. This field is required for users on this list.
        $form->addElement('text', 'param2', get_string('require_for_users', 'profilefield_limitrequired'), 'size="50"');
        $form->setType('param2', PARAM_TEXT);
        $form->addHelpButton('param2', 'require_for_users', 'profilefield_limitrequired');

        // Param 3 for limitrequired type contains
        // course ids. If the user is enroled in
        // a course with a specified id, the
        // field is required.
         $form->addElement('text', 'param3', get_string('require_for_enrolments_in_courses', 'profilefield_limitrequired'), 'size="50"');
        $form->setType('param3', PARAM_TEXT);
        $form->addHelpButton('param3', 'require_for_enrolments_in_courses', 'profilefield_limitrequired');

        // Param 4 for limitrequired type contains
        // category ids. If the user is enroled in
        // a course under the specified id, the
        // field is required.
        $form->addElement('text', 'param4', get_string('require_for_enrolments_in_categories', 'profilefield_limitrequired'), 'size="50"');
        $form->setType('param4', PARAM_TEXT);
        $form->addHelpButton('param4', 'require_for_enrolments_in_categories', 'profilefield_limitrequired');

        // Param 5 for limitrequired type contains user
        // authentication methods to set required
        // flag
        $form->addElement('text', 'param5', get_string('require_for_auth_methods', 'profilefield_limitrequired'), 'size="50"');
        $form->setType('param5', PARAM_TEXT);
        $form->addHelpButton('param5', 'require_for_auth_methods', 'profilefield_limitrequired');
    }

    /**
     * Sets the field object and default data and format into $this->data and $this->dataformat
     *
     * @param stdClass $field
     * @throws coding_exception
     */
    public function set_field($field) {
        global $CFG;
        if ($CFG->debugdeveloper) {
            $properties = ['id', 'shortname', 'name', 'datatype', 'description', 'descriptionformat', 'categoryid', 'sortorder',
                'required', 'locked', 'visible', 'forceunique', 'signup', 'defaultdata', 'defaultdataformat', 'param1', 'param2',
                'param3', 'param4', 'param5'];
            foreach ($properties as $property) {
                if (!property_exists($field, $property)) {
                    debugging('The \'' . $property . '\' property must be set.', DEBUG_DEVELOPER);
                }
            }
        }
        if ($this->fieldid && $this->fieldid != $field->id) {
            throw new coding_exception('Can not set field object after a different field id was set');
        }
        $this->fieldid = $field->id;
        $this->field = $field;
        $this->inputname = 'profile_field_' . $this->field->shortname;
        $this->data = $this->field->defaultdata;
        $this->dataformat = FORMAT_HTML;
    }

    function define_validate_specific($data, $files) {
        global $DB;
        $errors = array();
        if(!empty($data->param2)) {
            $cleaned = preg_replace('[^\d,]', '', $data->param2);
            if($cleaned !== $data->param2) {
                $errors['param2'] = get_string('error:onlynumbersandcolons', 'profilefield_limitrequired');
            }
            $userids = explode(',', $data->param2);
            foreach($userids as $userid) {
                $userid = intval($userid);
                if($userid < 2 ||
                    !$DB->record_exists('user', ['id' => $userid])) {
                    $errors['param2'] = get_string('error:invalidid', 'profilefield_limitrequired', $userid);
                    break;
                }
            }
        }
        if(!empty($data->param3)) {
            $cleaned = preg_replace('[^\d,]', '', $data->param3);
            if($cleaned !== $data->param3) {
                $errors['param3'] = get_string('error:onlynumbersandcolons', 'profilefield_limitrequired');
            }
            $courseids = explode(',', $data->param3);
            foreach($courseids as $courseid) {
                $courseid = intval($courseid);
                if($courseid < 1 ||
                    !$DB->record_exists('course', ['id' => $courseid])) {
                    $errors['param3'] = get_string('error:invalidid', 'profilefield_limitrequired', $courseid);
                    break;
                }
            }
        }
        if(!empty($data->param4)) {
            $cleaned = preg_replace('[^\d,]', '', $data->param4);
            if($cleaned !== $data->param4) {
                $errors['param4'] = get_string('error:onlynumbersandcolons', 'profilefield_limitrequired');
            }
            $catids = explode(',', $data->param4);
            foreach($catids as $catid) {
                $catid = intval($catid);
                if($catid < 1 ||
                    !$DB->record_exists('course_categories', ['id' => $catid])) {
                    $errors['param4'] = get_string('error:invalidid', 'profilefield_limitrequired', $catid);
                    break;
                }
            }
        }
        if(!empty($data->param5)) {
            $cleaned = preg_replace('[^\d,]', '', $data->param5);
            if($cleaned !== $data->param5) {
                $errors['param5'] = get_string('error:onlynumbersandcolons', 'profilefield_limitrequired');
            }
        }

        return $errors;
     }
}
