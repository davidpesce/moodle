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
 * Handles synchronising grades for the enrolment LTI.
 *
 * @package    enrol_lti
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lti\task;

/**
 * Task for synchronising grades for the enrolment LTI.
 *
 * @package    enrol_lti
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_grades extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('tasksyncgrades', 'enrol_lti');
    }

    /**
     * Performs the synchronisation of grades.
     *
     * @return bool|void
     */
    public function execute() {
        // Check if the authentication plugin is disabled.
        if (!is_enabled_auth('lti')) {
            mtrace('Skipping task - ' . get_string('pluginnotenabled', 'auth', get_string('pluginname', 'auth_lti')));
            return true;
        }

        // Check if the enrolment plugin is disabled - isn't really necessary as the task should not run if
        // the plugin is disabled, but there is no harm in making sure core hasn't done something wrong.
        if (!enrol_is_enabled('lti')) {
            mtrace('Skipping task - ' . get_string('enrolisdisabled', 'enrol_lti'));
            return true;
        }

        // Get all the enabled tools and spawn an adhoc task to handle each.
        if ($tools = \enrol_lti\helper::get_lti_tools(array('status' => ENROL_INSTANCE_ENABLED, 'gradesync' => 1,
            'ltiversion' => 'LTI-1p0/LTI-2p0'))) {
            foreach ($tools as $tool) {
                $task = new \enrol_lti\task\sync_tool_grades();
                $task->set_custom_data($tool);
                $task->set_component('enrol_lti');
                \core\task\manager::queue_adhoc_task($task, true);
            }

            mtrace('Spawned ' . count($resources) . ' adhoc tasks to sync grades.');
        }
    }
}
