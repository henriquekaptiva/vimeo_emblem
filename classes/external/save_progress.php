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
 * External API to save watch progress.
 *
 * @package   block_vimeo_emblem
 * @copyright 2026 Henrique
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_vimeo_emblem\external;

defined('MOODLE_INTERNAL') || die();

use block_vimeo_emblem\manager;
use context_block;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Save unique watched seconds for a block instance.
 */
class save_progress extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters Parameter description.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'Block instance id'),
            'videoid' => new external_value(PARAM_ALPHANUM, 'Vimeo video id'),
            'seconds' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Watched second index'),
                'Newly watched unique seconds'
            ),
            'duration' => new external_value(PARAM_FLOAT, 'Video duration in seconds'),
        ]);
    }

    /**
     * Save progress and issue the badge when the threshold is met.
     *
     * @param int $instanceid Block instance id.
     * @param string $videoid Vimeo video id.
     * @param int[] $seconds Newly watched whole seconds.
     * @param float $duration Video duration in seconds.
     * @return array Watch progress result.
     */
    public static function execute(int $instanceid, string $videoid, array $seconds, float $duration): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'instanceid' => $instanceid,
            'videoid' => $videoid,
            'seconds' => $seconds,
            'duration' => $duration,
        ]);

        if (!isloggedin() || isguestuser()) {
            throw new \moodle_exception('noguest');
        }

        $instance = $DB->get_record('block_instances', [
            'id' => $params['instanceid'],
            'blockname' => 'vimeo_emblem',
        ], '*', MUST_EXIST);

        $context = context_block::instance($instance->id);
        self::validate_context($context);

        $coursecontext = $context->get_course_context(false);
        if ($coursecontext) {
            $course = get_course($coursecontext->instanceid);
            require_login($course, false);
        } else {
            require_login();
        }

        if (count($params['seconds']) > manager::MAX_DURATION) {
            $params['seconds'] = array_slice($params['seconds'], 0, manager::MAX_DURATION);
        }

        return manager::save_progress(
            (int)$params['instanceid'],
            (int)$USER->id,
            (string)$params['videoid'],
            $params['seconds'],
            (float)$params['duration']
        );
    }

    /**
     * Return structure.
     *
     * @return external_single_structure Return description.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'percent' => new external_value(PARAM_FLOAT, 'Unique watch percentage'),
            'completed' => new external_value(PARAM_BOOL, 'Whether the threshold was reached'),
            'badgeissued' => new external_value(PARAM_BOOL, 'Whether the badge was issued'),
        ]);
    }
}
