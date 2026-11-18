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
 * File serving for the Vimeo emblem block.
 *
 * @package   block_vimeo_emblem
 * @copyright 2026 Henrique
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Serve files from the block file area.
 *
 * @param stdClass $course Course object.
 * @param stdClass $birecordorcm Block instance record.
 * @param context $context Block context.
 * @param string $filearea File area name.
 * @param array $args Path arguments.
 * @param bool $forcedownload Whether the file must be downloaded.
 * @param array $options Extra file serving options.
 * @return void
 */
function block_vimeo_emblem_pluginfile($course, $birecordorcm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $CFG, $USER;

    if ($context->contextlevel != CONTEXT_BLOCK) {
        send_file_not_found();
    }

    if ($context->get_course_context(false)) {
        require_course_login($course);
    } else if ($CFG->forcelogin) {
        require_login();
    } else {
        $parentcontext = $context->get_parent_context();
        if ($parentcontext->contextlevel === CONTEXT_COURSECAT) {
            if (!core_course_category::get($parentcontext->instanceid, IGNORE_MISSING)) {
                send_file_not_found();
            }
        } else if ($parentcontext->contextlevel === CONTEXT_USER && $parentcontext->instanceid != $USER->id) {
            send_file_not_found();
        }
    }

    if ($filearea !== 'content') {
        send_file_not_found();
    }

    $fs = get_file_storage();
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $file = $fs->get_file($context->id, 'block_vimeo_emblem', 'content', 0, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    if ($parentcontext = context::instance_by_id($birecordorcm->parentcontextid, IGNORE_MISSING)) {
        if ($parentcontext->contextlevel == CONTEXT_USER) {
            $forcedownload = true;
        }
    } else {
        $forcedownload = true;
    }

    \core\session\manager::write_close();
    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Perform global search replace such as when migrating site to new URL.
 *
 * @param string $search Text to find in stored configuration.
 * @param string $replace Replacement text.
 */
function block_vimeo_emblem_global_db_replace($search, $replace) {
    global $DB;

    $instances = $DB->get_recordset('block_instances', ['blockname' => 'vimeo_emblem']);
    foreach ($instances as $instance) {
        $config = unserialize(base64_decode($instance->configdata), ['allowed_classes' => ['stdClass']]);
        if (isset($config->text) && is_string($config->text)) {
            $config->text = str_replace($search, $replace, $config->text);
            $DB->update_record('block_instances', [
                'id' => $instance->id,
                'configdata' => base64_encode(serialize($config)),
                'timemodified' => time(),
            ]);
        }
    }
    $instances->close();
}

/**
 * Given an array with a file path, it returns the itemid and the filepath for the defined filearea.
 *
 * @param string $filearea File area name.
 * @param array $args Path arguments.
 * @return array Item id and file path.
 */
function block_vimeo_emblem_get_path_from_pluginfile(string $filearea, array $args): array {
    array_shift($args);

    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    return [
        'itemid' => 0,
        'filepath' => $filepath,
    ];
}
