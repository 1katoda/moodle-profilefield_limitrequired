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

namespace profilefield_text;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/user/profile/field/limitrequired/field.class.php');

use profile_field_limitrequired;

/**
 * Unit tests for the profilefield_text.
 *
 * @package    profilefield_text
 * @copyright  2022 The Open University
 * @copyright  2025 1katoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \profilefield_text\profile_field_limitrequired
 */
class field_class_test extends \advanced_testcase {
    private $testuser;
    private  $testcategory;
    private  $testcourse;

    private function setup_user($auth = 'manual', $enrol = true): void {
        $this->set_user($auth);
        $this->setup_courses();
        if(!$enrol) {return;}
        $this->getDataGenerator()->enrol_user(
            $this->testuser->id,
            $this->testcourse->id
        );
    }

    private function setup_courses(): void {
        global $DB;
        $gen = $this->getDataGenerator();
        $this->testcategory = $gen->create_category([
            'name' => 'testcat'
        ]);
        $this->testcourse = $gen->create_course([
            'name' => 'testcourse',
            'category' => $this->testcategory->id
        ]);
    }

    private function set_user($auth = 'manual'): void {
        if(!empty($this->testuser)) {
            $this->setUser($this->testuser);
            return;
        }
        $this->testuser = $this->getDataGenerator()->create_user([
            'email' => 'user1@localhost.local',
            'username' => 'user1',
            'auth' => $auth
        ]);
        $this->setUser($this->testuser);
    }
    /**
     * Test that the profile text data is formatted and required filters applied
     *
     * @covers \profile_field_limitrequired::display_data
     * @dataProvider filter_profile_field_limitrequired_provider
     * @param string $input
     * @param string $expected
     */
    public function test_filter_display_data(string $input, string $expected): void {
        $this->resetAfterTest();
        $field = new profile_field_limitrequired();
        $field->data = $input;

        filter_set_global_state('multilang', TEXTFILTER_ON);
        filter_set_global_state('emoticon', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang', true);

        $actual = $field->display_data();
        $this->assertEquals($expected, $actual);
        $field->reset_cached_data();
    }

    /**
     * Data provider for {@see test_filter_display_data}
     *
     * @return string[]
     */
    public function filter_profile_field_limitrequired_provider(): array {
        return [
                'simple_string' => ['Simple string', 'Simple string'],
                'format_string' => ['HTML & is escaped', 'HTML &amp; is escaped'],
                'multilang_filter' =>
                    ['<span class="multilang" lang="en">English</span><span class="multilang" lang="fr">French</span>', 'English'],
                'emoticons_filter' => ['No emoticons filter :-(', 'No emoticons filter :-(']
        ];
    }

    /**
     * Test preprocess data validation
     */
    public function test_edit_save_data_preprocess(): void {
        $this->resetAfterTest();

        $fielddata = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param2' => 255, // Max length.
        ]);
        $field = new profile_field_limitrequired(0, 0, $fielddata);

        $value = $field->edit_save_data_preprocess('ABCDE', new \stdClass());
        $this->assertEquals('ABCDE', $value);
        $field->reset_cached_data();
    }

    /**
     * Test external data validation
     */
    public function test_convert_external_data(): void {
        $this->resetAfterTest();

        $fielddata = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text',
            'name' => 'Test',
            'shortname' => 'test',
            'param2' => 5, // Max length.
        ]);
        $field = new profile_field_limitrequired(0, 0, $fielddata);

        $value = $field->convert_external_data('ABCDE');
        $this->assertEquals('ABCDE', $value);
        $field->reset_cached_data();
    }

    /**
     * Test is_required function with set user ids
     */
    public function test_is_required_user_id(): void {
        $this->resetAfterTest();
        $this->setup_user('email', false);

        // User id doesn't match, require logic OR
        $field1data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param2' => 2,
            'required' => true
        ]);
        $field1 = new profile_field_limitrequired(0, 0, $field1data);

        $value1 = $field1->is_required();
        $this->assertEquals(false, $value1);

        // User id matches, require logic OR
        $field2data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param2' => $this->testuser->id,
            'required' => true
        ]);
        $field1->reset_cached_data();
        $field2 = new profile_field_limitrequired(0, 0, $field2data);

        $value2 = $field2->is_required();
        $this->assertEquals(true, $value2);
        $field2->reset_cached_data();

        // User id doesn't match,
        // user auth matches
        // require logic AND
        $field1data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param2' => 2,
            'param5' => 'email',
            'required' => false
        ]);
        $field1 = new profile_field_limitrequired(0, 0, $field1data);

        $value1 = $field1->is_required();
        $this->assertEquals(false, $value1);

        // User id doesn't match,
        // user auth matches
        // require logic OR
        $field1data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param2' => 2,
            'param5' => 'email',
            'required' => true
        ]);
        $field1 = new profile_field_limitrequired(0, 0, $field1data);

        $value1 = $field1->is_required();
        $this->assertEquals(false, $value1);

        // User id matches,
        // user auth matches
        // require logic AND
        $field2data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param2' => $this->testuser->id,
            'param5' => 'email',
            'required' => false
        ]);
        $field1->reset_cached_data();
        $field2 = new profile_field_limitrequired(0, 0, $field2data);

        $value2 = $field2->is_required();
        $this->assertEquals(true, $value2);
        $field2->reset_cached_data();

        // User id matches,
        // user auth matches
        // require logic OR
        $field2data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param2' => $this->testuser->id,
            'param5' => 'email',
            'required' => true
        ]);
        $field1->reset_cached_data();
        $field2 = new profile_field_limitrequired(0, 0, $field2data);

        $value2 = $field2->is_required();
        $this->assertEquals(true, $value2);
        $field2->reset_cached_data();
    }

    /**
     * Test is_required function with
     * course enrol and user id
     */
    public function test_is_required_course_enrol(): void {
        $this->resetAfterTest();
        $this->setup_user('email', true);

        // Course id doesn't match, require logic OR
        $field1data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param3' => 2,
            'required' => true
        ]);
        $field1 = new profile_field_limitrequired(0, 0, $field1data);

        $value1 = $field1->is_required();
        $this->assertEquals(false, $value1);

        // Course id matches, require logic OR
        $field2data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param3' => $this->testcourse->id,
            'required' => true
        ]);
        $field1->reset_cached_data();
        $field2 = new profile_field_limitrequired(0, 0, $field2data);

        $value2 = $field2->is_required();
        $this->assertEquals(true, $value2);
        $field2->reset_cached_data();

        // Course id doesn't match,
        // user auth matches
        // require logic AND
        $field1data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param3' => 2,
            'param5' => 'email',
            'required' => false
        ]);
        $field1 = new profile_field_limitrequired(0, 0, $field1data);

        $value1 = $field1->is_required();
        $this->assertEquals(false, $value1);

        // Course id doesn't match,
        // user auth matches
        // require logic OR
        $field1data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param3' => 2,
            'param5' => 'email',
            'required' => true
        ]);
        $field1 = new profile_field_limitrequired(0, 0, $field1data);

        $value1 = $field1->is_required();
        $this->assertEquals(false, $value1);

        // Course id matches,
        // user auth matches
        // require logic OR
        $field2data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param3' => $this->testcourse->id,
            'param5' => 'email',
            'required' => true
        ]);
        $field1->reset_cached_data();
        $field2 = new profile_field_limitrequired(0, 0, $field2data);

        $value2 = $field2->is_required();
        $this->assertEquals(true, $value2);
        $field2->reset_cached_data();

        // Course id matches,
        // user auth matches
        // require logic AND
        $field2data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param3' => $this->testcourse->id,
            'param5' => 'email',
            'required' => false
        ]);
        $field1->reset_cached_data();
        $field2 = new profile_field_limitrequired(0, 0, $field2data);

        $value2 = $field2->is_required();
        $this->assertEquals(true, $value2);
        $field2->reset_cached_data();

        // User id matches,
        // user auth matches
        // course enrol does not match
        // require logic AND
        $field2data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param2' => $this->testuser->id,
            'param3' => 2,
            'param5' => 'email',
            'required' => false
        ]);
        $field1->reset_cached_data();
        $field2 = new profile_field_limitrequired(0, 0, $field2data);

        $value2 = $field2->is_required();
        $this->assertEquals(false, $value2);
        $field2->reset_cached_data();

        // User id matches,
        // user auth matches
        // course enrol matches
        // require logic AND
        $field2data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param2' => $this->testuser->id,
            'param3' => $this->testcourse->id,
            'param5' => 'email',
            'required' => false
        ]);
        $field1->reset_cached_data();
        $field2 = new profile_field_limitrequired(0, 0, $field2data);

        $value2 = $field2->is_required();
        $this->assertEquals(true, $value2);
        $field2->reset_cached_data();

        // User id matches,
        // user auth matches
        // course enrol matches
        // require logic OR
        $field2data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param2' => $this->testuser->id,
            'param3' => $this->testcourse->id,
            'param5' => 'email',
            'required' => true
        ]);
        $field1->reset_cached_data();
        $field2 = new profile_field_limitrequired(0, 0, $field2data);

        $value2 = $field2->is_required();
        $this->assertEquals(true, $value2);
        $field2->reset_cached_data();
    }

    /**
     * Test is_required function with
     * course in a category enrol and user id
     */
    public function test_is_required_category_enrol(): void {
        $this->resetAfterTest();
        $this->setup_user('email', true);

        // Course id doesn't match, require logic OR
        $field1data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param4' => 2,
            'required' => true
        ]);
        $field1 = new profile_field_limitrequired(0, 0, $field1data);

        $value1 = $field1->is_required();
        $this->assertEquals(false, $value1);

        // Course id matches, require logic OR
        $field2data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param4' => $this->testcategory->id,
            'required' => true
        ]);
        $field1->reset_cached_data();
        $field2 = new profile_field_limitrequired(0, 0, $field2data);

        $value2 = $field2->is_required();
        $this->assertEquals(true, $value2);
        $field2->reset_cached_data();

        // Course id doesn't match,
        // user auth matches
        // require logic AND
        $field1data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param4' => 2,
            'param5' => 'email',
            'required' => false
        ]);
        $field1 = new profile_field_limitrequired(0, 0, $field1data);

        $value1 = $field1->is_required();
        $this->assertEquals(false, $value1);

        // Course id doesn't match,
        // user auth matches
        // require logic OR
        $field1data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param4' => 2,
            'param5' => 'email',
            'required' => true
        ]);
        $field1 = new profile_field_limitrequired(0, 0, $field1data);

        $value1 = $field1->is_required();
        $this->assertEquals(false, $value1);

        // Course id matches,
        // user auth matches
        // require logic OR
        $field2data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param4' => $this->testcategory->id,
            'param5' => 'email',
            'required' => true
        ]);
        $field1->reset_cached_data();
        $field2 = new profile_field_limitrequired(0, 0, $field2data);

        $value2 = $field2->is_required();
        $this->assertEquals(true, $value2);
        $field2->reset_cached_data();

        // Course id matches,
        // user auth matches
        // require logic AND
        $field2data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param4' => $this->testcategory->id,
            'param5' => 'email',
            'required' => false
        ]);
        $field1->reset_cached_data();
        $field2 = new profile_field_limitrequired(0, 0, $field2data);

        $value2 = $field2->is_required();
        $this->assertEquals(true, $value2);
        $field2->reset_cached_data();

        // User id matches,
        // user auth matches
        // course enrol does not match
        // require logic AND
        $field2data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param2' => $this->testuser->id,
            'param4' => 2,
            'param5' => 'email',
            'required' => false
        ]);
        $field1->reset_cached_data();
        $field2 = new profile_field_limitrequired(0, 0, $field2data);

        $value2 = $field2->is_required();
        $this->assertEquals(false, $value2);
        $field2->reset_cached_data();

        // User id matches,
        // user auth matches
        // category enrol matches
        // require logic AND
        $field2data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param2' => $this->testuser->id,
            'param4' => $this->testcategory->id,
            'param5' => 'email',
            'required' => false
        ]);
        $field1->reset_cached_data();
        $field2 = new profile_field_limitrequired(0, 0, $field2data);

        $value2 = $field2->is_required();
        $this->assertEquals(true, $value2);
        $field2->reset_cached_data();

        // User id matches,
        // user auth matches
        // course enrol matches
        // require logic AND
        $field2data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param2' => $this->testuser->id,
            'param4' => $this->testcategory->id,
            'param5' => 'email',
            'required' => false
        ]);
        $field1->reset_cached_data();
        $field2 = new profile_field_limitrequired(0, 0, $field2data);

        $value2 = $field2->is_required();
        $this->assertEquals(true, $value2);
        $field2->reset_cached_data();

        // User id matches,
        // user auth matches
        // course enrol matches
        // require logic OR
        $field2data = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'limitrequired',
            'name' => 'Test',
            'shortname' => 'test',
            'param2' => $this->testuser->id,
            'param4' => $this->testcategory->id,
            'param5' => 'email',
            'required' => true
        ]);
        $field1->reset_cached_data();
        $field2 = new profile_field_limitrequired(0, 0, $field2data);

        $value2 = $field2->is_required();
        $this->assertEquals(true, $value2);
        $field2->reset_cached_data();
    }
}

