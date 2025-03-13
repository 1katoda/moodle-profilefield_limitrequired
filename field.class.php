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
 * AAI text profile field.
 *
 * @package    profilefield_limitrequired
 * @copyright  1katoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class profile_field_limitrequired
 *
 * @copyright 1katoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_field_limitrequired extends profile_field_base {

    private static $isrequiredcached = false;
    private static $isrequiredcache = false;

    /**
     * Overwrite the base class to display the data for this field
     */
    public function display_data() {
        // Default formatting.
        $data = format_string($this->data);

        return $data;
    }

    /**
     * Add fields for editing an aai text profile field.
     * @param moodleform $mform
     */
    public function edit_field_add($mform) {
        $maxlength = 255;
        $fieldtype = 'text';
        $paramtype = PARAM_TEXT;
        $attributes = 'maxlength="'.$maxlength.'" size="50" ';
        switch($this->field->param1) {
            case 1:
                $fieldtype = 'password';
                break;
            // case 2:
            //     $fieldtype = 'email';
            //     break;
            // case 2:
                // $fieldtype = 'number';
                // $attributes = '';
                // $paramtype = PARAM_INT;
        }

        // Create the form field.
        $mform->addElement($fieldtype, $this->inputname, format_string($this->field->name),
                    'maxlength="'.$maxlength.'" size="50" ');
        $mform->setType($this->inputname, $paramtype);
    }

    /**
     * Process the data before it gets saved in database
     *
     * @param string|null $data
     * @param stdClass $datarecord
     * @return string|null
     */
    public function edit_save_data_preprocess($data, $datarecord) {
        if ($data === null) {
            return null;
        }
        $data = core_text::substr(
            $data,
            0,
            255
        );
        // $data = preg_replace('[^\d,]', '', $data);
        return $data;
    }

    /**
     * Convert external data (csv file) from value to key for processing later by edit_save_data_preprocess
     *
     * @param string $data
     * @return string|null
     */
    public function convert_external_data($data) {
        if (core_text::strlen($data) > 255) {
            return null;
        }
        return $data;
    }

    public function is_required() {
        global $DB, $USER;
        $isfound = $this->field->required;

        if(self::$isrequiredcached) {
	        return self::$isrequiredcache;
        }
        if(!empty($this->field->param2)) {
            // Check if the user is specified for
            // this profile field
            $userids = explode(",", $this->field->param2);
            $found = false;
            foreach($userids as $userid) {
                if($USER->id == intval(trim($userid))) {
                    // return true;
                    $found = true;
                    break;
                }
            }
            if(!$found) {
                return false;
            }
            if($this->field->required) {
                // Found user, matches at least
                // one criterium.
                return true;
            }
            $isfound = true;
        }
        if(!empty($this->field->param5)) {
            // Check if the user has the specified
            // auth type
            $auth_types = explode(",", $this->field->param5);
            $found = false;
            foreach($auth_types as $auth_type) {
                if($USER->auth === trim($auth_type)) {
                    $found = true;
                    break;
                }
            }
            self::$isrequiredcache = $found;
            self::$isrequiredcached = true;
            if(!$found) {
                return false;
            }
            if($this->field->required) {
                // Found user, matches at least
                // one criterium.
                return true;
            }
            $isfound = true;
        }

        if(!empty($this->field->param3)) {
            // Check if the user is enroled in any
            // courses.
            $courseids = explode(",", $this->field->param3);
            foreach($courseids as $courseid) {
                if(!$DB->record_exists_sql('SELECT ue.id
                FROM {user_enrolments} ue
                JOIN {enrol} e
                ON e.id = ue.enrolid
                JOIN {course} c
                ON c.id = e.courseid
                WHERE ue.userid = ?
                AND c.id = ?
                ', [$USER->id, $courseid])) {
                    self::$isrequiredcache = false;
                    self::$isrequiredcached = true;
                    // If user is not enrolled in any
                    // course under the specified category,
                    // the field is not required.
                    return false;
                }
            }
            if($this->field->required) {
                // Found user, matches at least
                // one criterium.
                return true;
            }
            $isfound = true;
        }

        if(!empty($this->field->param4)) {
            // Check if the user is enroled in any
            // courses under the specified
            // categories.
            $categoryids = explode(",", $this->field->param4);
            foreach($categoryids as $categoryid) {
                if(!$DB->record_exists_sql('SELECT ue.id
                FROM {user_enrolments} ue
                JOIN {enrol} e
                ON e.id = ue.enrolid
                JOIN {course} c
                ON c.id = e.courseid
                JOIN {course_categories} cat
                ON cat.id = c.category
                WHERE ue.userid = ?
                AND (cat.path LIKE ?
                OR cat.path LIKE ?)
                ', [$USER->id, '%/' . trim($categoryid) . '/%','%/' . trim($categoryid)])) {
                    self::$isrequiredcache = false;
                    self::$isrequiredcached = true;
                    // If user is not enrolled in any
                    // course under the specified category,
                    // the field is not required.
                    return false;
                }
            }
            if($this->field->required) {
                // Found user, matches at least
                // one criterium.
                return true;
            }
            $isfound = true;
        }
        self::$isrequiredcache = true;
        self::$isrequiredcached = true;

        return $isfound;
    }

    /**
     * Return the field type and null properties.
     * This will be used for validating the data submitted by a user.
     *
     * @return array the param type and null property
     * @since Moodle 3.2
     */
    public function get_field_properties() {
        return array(PARAM_TEXT, NULL_NOT_ALLOWED);
    }

    public function reset_cached_data() {
        self::$isrequiredcache = false;
        self::$isrequiredcached = false;
    }
}


