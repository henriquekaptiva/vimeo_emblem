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
 * Vimeo emblem block main class.
 *
 * @package   block_vimeo_emblem
 * @copyright 2026 Henrique
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_vimeo_emblem extends block_base {

    /**
     * Adds title to block instance and initializes it.
     */
    public function init() {
        $this->title = get_string('vimeo_emblem', 'block_vimeo_emblem');
    }

    /**
     * Overrides block instance content.
     * Called immediately after init().
     */
    public function specialization() {
        if (empty($this->config)) {
            $this->config = new \stdClass();
            $this->config->show_as_card = '0';
            $this->config->show_header = '0';
            $this->config->custom_title = '';
            $this->config->custom_subtitle = '';
            $this->config->text = '';
            $this->config->badgeid = 0;
            $this->config->threshold = 95;
            $this->config->prevent_seek = '0';
            $this->config->show_progress = '1';
        }

        if (empty($this->config->custom_title)) {
            $this->title = get_string('vimeo_emblem', 'block_vimeo_emblem');
        } else {
            $this->title = format_text($this->config->custom_title, FORMAT_HTML, ['filter' => false]);
        }
    }

    /**
     * Gets block instance content.
     */
    public function get_content() {
        global $CFG, $DB, $USER;

        require_once($CFG->libdir . '/filelib.php');

        if ($this->content !== null) {
            return $this->content;
        }

        $filteropt = new stdClass();
        $filteropt->overflowdiv = true;
        $filteropt->noclean = true;

        $this->content = new stdClass();

        $showheader = !empty($this->config->show_header);
        $showascard = !empty($this->config->show_as_card);
        $subtitle = $this->config->custom_subtitle ?? '';
        $text = $this->config->text ?? '';

        $blockintro = '';
        if ($showheader && !$showascard) {
            $blockintro = '<div class="purity-block-intro">
                <h3 class="block-main-title">' . $this->title . '</h3>';
            if ($subtitle) {
                $blockintro .= '<div class="block-title-separator"></div>
                    <div class="block-subtitle">' .
                    format_text($subtitle, FORMAT_HTML, ['filter' => true]) . '</div>';
            }
            $blockintro .= '</div>';
        }

        $text = file_rewrite_pluginfile_urls(
            $text,
            'pluginfile.php',
            $this->context->id,
            'block_vimeo_emblem',
            'content',
            null
        );

        // Drop embed <script> tags. Vimeo's player.js is a UMD bundle: if it
        // hits RequireJS it registers an anonymous module and Moodle navigation
        // (and Purity header colour) stops working. The AMD module loads the API.
        $text = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $text);

        $content = '<div class="custom-html-content vimeo-emblema-content">' .
            format_text($text, FORMAT_HTML, $filteropt) . '</div>';

        $statushtml = '';
        if (!empty($this->config->show_progress)) {
            $statushtml = '<div class="vimeo-emblema-status" data-region="vimeo-emblema-status" hidden>
                <div class="vimeo-emblema-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                    <span></span>
                </div>
                <p class="vimeo-emblema-label"></p>
            </div>';
        }

        $this->content->text = '
            <div class="custom-html-container vimeo-emblema-container">
                ' . $blockintro . '
                ' . $content . '
                ' . $statushtml . '
            </div>';

        if (isloggedin() && !isguestuser() && !empty($this->instance->id)) {
            $percent = 0;
            $completed = 0;
            $badgeissued = 0;
            try {
                $records = $DB->get_records('block_vimeo_emblem', [
                    'instanceid' => $this->instance->id,
                    'userid' => $USER->id,
                ]);
                foreach ($records as $record) {
                    $percent = max($percent, (float)$record->percent);
                    $completed = $completed || (int)$record->completed;
                    $badgeissued = $badgeissued || (int)$record->badgeissued;
                }
            } catch (\Throwable $e) {
                debugging('block_vimeo_emblem: could not read progress: ' . $e->getMessage(), DEBUG_DEVELOPER);
                $records = [];
            }

            $this->page->requires->js_call_amd('block_vimeo_emblem/player', 'init', [[
                'instanceid' => (int)$this->instance->id,
                'threshold' => (float)($this->config->threshold ?? 95),
                'preventseek' => !empty($this->config->prevent_seek),
                'showprogress' => !empty($this->config->show_progress),
                'percent' => $percent,
                'completed' => (bool)$completed,
                'badgeissued' => (bool)$badgeissued,
            ]]);
        }

        return $this->content;
    }

    /**
     * Serialize and store config data.
     *
     * @param stdClass $data Form data.
     * @param bool $nolongerused Unused flag kept for the parent signature.
     */
    public function instance_config_save($data, $nolongerused = false) {
        $config = clone($data);
        $config->text = file_save_draft_area_files(
            $data->text['itemid'],
            $this->context->id,
            'block_vimeo_emblem',
            'content',
            0,
            ['subdirs' => true],
            $data->text['text']
        );
        $config->format = $data->text['format'];
        parent::instance_config_save($config, $nolongerused);
    }

    /**
     * Delete files and progress when the instance is removed.
     *
     * @return bool True when the instance data has been removed.
     */
    public function instance_delete() {
        global $DB;

        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, 'block_vimeo_emblem');
        if (!empty($this->instance->id)) {
            $DB->delete_records('block_vimeo_emblem', ['instanceid' => $this->instance->id]);
        }
        return true;
    }

    /**
     * Modifies the HTML attributes of the block.
     *
     * @return array HTML attributes for the block container.
     */
    public function html_attributes() {
        $attributes = parent::html_attributes();

        if (empty($this->config) || empty($this->config->show_header)) {
            $attributes['class'] .= ' block_hide_header';
        }
        if (empty($this->config) || empty($this->config->show_as_card)) {
            $attributes['class'] .= ' block_not_card';
        }

        return $attributes;
    }

    /**
     * Allows multiple instances of the block.
     *
     * @return bool True when more than one instance is allowed.
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * No global plugin settings page.
     *
     * @return bool True when the plugin has a site settings page.
     */
    public function has_config() {
        return false;
    }

    /**
     * Locations where the block can be displayed.
     *
     * @return array Page formats where the block can be added.
     */
    public function applicable_formats() {
        return [
            'all' => false,
            'course-view' => true,
            'site' => false,
            'mod' => false,
            'my' => false,
        ];
    }
}
