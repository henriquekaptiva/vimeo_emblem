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
 * Progress tracking and badge issuance for the Vimeo emblem block.
 *
 * @package   block_vimeo_emblem
 * @copyright 2026 Henrique
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_vimeo_emblem;

defined('MOODLE_INTERNAL') || die();

/**
 * Stores unique watched seconds and issues the configured course badge.
 */
class manager {

    /** Maximum video duration accepted, in seconds (12 hours). */
    const MAX_DURATION = 43200;

    /**
     * Merge newly watched seconds, persist progress, and issue the badge when due.
     *
     * @param int $instanceid Block instance id.
     * @param int $userid User id.
     * @param string $videoid Vimeo video id.
     * @param int[] $seconds Newly watched whole seconds.
     * @param float $duration Video duration in seconds.
     * @return array{percent: float, completed: bool, badgeissued: bool} Progress result.
     */
    public static function save_progress(int $instanceid, int $userid, string $videoid, array $seconds, float $duration): array {
        global $CFG, $DB;

        $instance = $DB->get_record('block_instances', [
            'id' => $instanceid,
            'blockname' => 'vimeo_emblem',
        ], '*', MUST_EXIST);

        $config = self::load_config($instance);
        $threshold = self::normalise_threshold($config->threshold ?? 95);
        $now = time();

        $record = $DB->get_record('block_vimeo_emblem', [
            'instanceid' => $instanceid,
            'userid' => $userid,
            'videoid' => $videoid,
        ]);

        $duration = max(0, min(self::MAX_DURATION, $duration));
        if ($record && (float)$record->duration > $duration) {
            $duration = (float)$record->duration;
        }
        $seconds = self::sanitise_seconds($seconds, $duration);

        $watched = [];
        if ($record && !empty($record->watched)) {
            $decoded = json_decode($record->watched, true);
            if (is_array($decoded)) {
                $watched = $decoded;
            }
        }

        $watched = array_values(array_unique(array_merge($watched, $seconds)));
        sort($watched, SORT_NUMERIC);
        $possible = max(1, (int)round($duration));
        $percent = min(100, round((count($watched) / $possible) * 100, 2));
        if ($record) {
            $percent = max($percent, (float)$record->percent);
        }
        $completed = $percent >= $threshold ? 1 : 0;

        $data = (object) [
            'instanceid' => $instanceid,
            'userid' => $userid,
            'videoid' => $videoid,
            'watched' => json_encode($watched),
            'duration' => $duration,
            'percent' => $percent,
            'completed' => $completed,
            'badgeissued' => $record ? (int)$record->badgeissued : 0,
            'timemodified' => $now,
        ];

        if ($record) {
            $data->id = $record->id;
            $DB->update_record('block_vimeo_emblem', $data);
        } else {
            $data->timecreated = $now;
            $data->id = $DB->insert_record('block_vimeo_emblem', $data);
        }

        $badgeissued = !empty($data->badgeissued);
        if ($completed && !$badgeissued && !empty($config->badgeid)) {
            require_once($CFG->libdir . '/badgeslib.php');
            $badgeissued = self::issue_badge((int)$config->badgeid, $userid, $instance);
            if ($badgeissued) {
                $DB->set_field('block_vimeo_emblem', 'badgeissued', 1, ['id' => $data->id]);
            }
        }

        return [
            'percent' => (float)$percent,
            'completed' => (bool)$completed,
            'badgeissued' => (bool)$badgeissued,
        ];
    }

    /**
     * Issue a course (or site) badge to the user when it is active and not yet awarded.
     *
     * @param int $badgeid Badge id.
     * @param int $userid User id.
     * @param \stdClass $instance Block instance record.
     * @return bool True when the badge is issued or was already issued.
     */
    public static function issue_badge(int $badgeid, int $userid, \stdClass $instance): bool {
        global $CFG;

        require_once($CFG->libdir . '/badgeslib.php');

        try {
            if (class_exists('\core_badges\badge')) {
                $badge = new \core_badges\badge($badgeid);
            } else {
                $badge = new \badge($badgeid);
            }
        } catch (\Throwable $e) {
            debugging('block_vimeo_emblem: badge not found: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }

        if (!$badge->is_active()) {
            return false;
        }

        $coursecontext = \context_block::instance($instance->id)->get_course_context(false);
        if ((int)$badge->type === BADGE_TYPE_COURSE) {
            if (!$coursecontext || (int)$badge->courseid !== (int)$coursecontext->instanceid) {
                return false;
            }
        }

        if ($badge->is_issued($userid)) {
            return true;
        }

        try {
            $badge->issue($userid);
        } catch (\Throwable $e) {
            debugging('block_vimeo_emblem: could not issue badge: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }

        return $badge->is_issued($userid);
    }

    /**
     * Decode instance configdata.
     *
     * @param \stdClass $instance Block instance record.
     * @return \stdClass Instance configuration.
     */
    public static function load_config(\stdClass $instance): \stdClass {
        if (empty($instance->configdata)) {
            return new \stdClass();
        }
        $config = unserialize(base64_decode($instance->configdata), ['allowed_classes' => ['stdClass']]);
        return is_object($config) ? $config : new \stdClass();
    }

    /**
     * Keep threshold between 1 and 100.
     *
     * @param mixed $threshold Configured watch percentage.
     * @return float Threshold between 1 and 100.
     */
    public static function normalise_threshold($threshold): float {
        $threshold = (float)$threshold;
        if ($threshold <= 0) {
            return 95.0;
        }
        return min(100, $threshold);
    }

    /**
     * Keep only valid unique second indexes.
     *
     * @param array $seconds Reported second indexes.
     * @param float $duration Video duration in seconds.
     * @return int[] Valid unique second indexes.
     */
    public static function sanitise_seconds(array $seconds, float $duration): array {
        $max = (int)ceil(max(0, min(self::MAX_DURATION, $duration)));
        $clean = [];
        foreach ($seconds as $second) {
            $second = (int)$second;
            if ($second < 0 || $second > $max) {
                continue;
            }
            $clean[$second] = $second;
        }
        return array_values($clean);
    }
}
