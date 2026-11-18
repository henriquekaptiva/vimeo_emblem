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
 * Privacy subsystem implementation for block_vimeo_emblem.
 *
 * @package   block_vimeo_emblem
 * @copyright 2026 Henrique
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_vimeo_emblem\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for stored watch progress.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe stored personal data.
     *
     * @param collection $collection Collection of stored metadata.
     * @return collection Declared personal data.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('block_vimeo_emblem', [
            'instanceid' => 'privacy:metadata:instanceid',
            'userid' => 'privacy:metadata:userid',
            'videoid' => 'privacy:metadata:videoid',
            'watched' => 'privacy:metadata:watched',
            'duration' => 'privacy:metadata:duration',
            'percent' => 'privacy:metadata:percent',
            'completed' => 'privacy:metadata:completed',
            'badgeissued' => 'privacy:metadata:badgeissued',
            'timecreated' => 'privacy:metadata:timecreated',
            'timemodified' => 'privacy:metadata:timemodified',
        ], 'privacy:metadata:block_vimeo_emblem');

        $collection->add_external_location_link('vimeo', [
            'ipaddress' => 'privacy:metadata:vimeo:ipaddress',
            'videoid' => 'privacy:metadata:vimeo:videoid',
        ], 'privacy:metadata:vimeo');

        return $collection;
    }

    /**
     * Get contexts that contain user information.
     *
     * @param int $userid User id.
     * @return contextlist Contexts that contain the user's progress.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {block_vimeo_emblem} p
                  JOIN {block_instances} bi ON bi.id = p.instanceid
                  JOIN {context} ctx ON ctx.instanceid = bi.id AND ctx.contextlevel = :contextlevel
                 WHERE p.userid = :userid";
        $contextlist->add_from_sql($sql, [
            'userid' => $userid,
            'contextlevel' => CONTEXT_BLOCK,
        ]);
        return $contextlist;
    }

    /**
     * Get users in a context.
     *
     * @param userlist $userlist User list for the requested context.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_BLOCK) {
            return;
        }
        $sql = "SELECT p.userid
                  FROM {block_vimeo_emblem} p
                 WHERE p.instanceid = :instanceid";
        $userlist->add_from_sql('userid', $sql, ['instanceid' => $context->instanceid]);
    }

    /**
     * Export user data.
     *
     * @param approved_contextlist $contextlist Contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if ($contextlist->count() == 0) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_BLOCK) {
                continue;
            }
            $records = $DB->get_records('block_vimeo_emblem', [
                'instanceid' => $context->instanceid,
                'userid' => $userid,
            ]);
            foreach ($records as $record) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'block_vimeo_emblem'), $record->videoid],
                    (object) [
                        'videoid' => $record->videoid,
                        'watched' => $record->watched,
                        'percent' => $record->percent,
                        'duration' => $record->duration,
                        'completed' => $record->completed,
                        'badgeissued' => $record->badgeissued,
                        'timecreated' => \core_privacy\local\request\transform::datetime($record->timecreated),
                        'timemodified' => \core_privacy\local\request\transform::datetime($record->timemodified),
                    ]
                );
            }
        }
    }

    /**
     * Delete all data for a context.
     *
     * @param \context $context Context to delete.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel != CONTEXT_BLOCK) {
            return;
        }
        $DB->delete_records('block_vimeo_emblem', ['instanceid' => $context->instanceid]);
    }

    /**
     * Delete data for users in a context.
     *
     * @param approved_userlist $userlist Users approved for deletion in the context.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_BLOCK) {
            return;
        }
        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }
        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $inparams['instanceid'] = $context->instanceid;
        $DB->delete_records_select(
            'block_vimeo_emblem',
            "instanceid = :instanceid AND userid $insql",
            $inparams
        );
    }

    /**
     * Delete data for a user.
     *
     * @param approved_contextlist $contextlist Contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_BLOCK) {
                continue;
            }
            $DB->delete_records('block_vimeo_emblem', [
                'instanceid' => $context->instanceid,
                'userid' => $userid,
            ]);
        }
    }
}
