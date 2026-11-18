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
 * Instance settings form for the Vimeo emblem block.
 *
 * @package   block_vimeo_emblem
 * @copyright 2026 Henrique
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_vimeo_emblem_edit_form extends block_edit_form {

    /**
     * Block-specific settings.
     *
     * @param MoodleQuickForm $mform Block settings form.
     */
    protected function specific_definition($mform) {
        global $CFG;

        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        $yesno = [
            '0' => get_string('no'),
            '1' => get_string('yes'),
        ];
        $mform->addElement('select', 'config_show_as_card', get_string('show_as_card', 'block_vimeo_emblem'), $yesno);
        $mform->setDefault('config_show_as_card', '0');

        $hideoptions = [
            '0' => get_string('hide'),
            '1' => get_string('show'),
        ];
        $mform->addElement('select', 'config_show_header', get_string('show_header', 'block_vimeo_emblem'), $hideoptions);
        $mform->setDefault('config_show_header', '0');

        $mform->addElement('text', 'config_custom_title', get_string('custom_title', 'block_vimeo_emblem'));
        $mform->setDefault('config_custom_title', '');
        $mform->setType('config_custom_title', PARAM_TEXT);

        $mform->addElement(
            'textarea',
            'config_custom_subtitle',
            get_string('custom_subtitle', 'block_vimeo_emblem'),
            'wrap="virtual" rows="5" cols="50"'
        );
        $mform->setDefault('config_custom_subtitle', '');
        $mform->setType('config_custom_subtitle', PARAM_TEXT);

        $editoroptions = [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'noclean' => true,
            'context' => $this->block->context,
        ];
        $mform->addElement('editor', 'config_text', get_string('content', 'block_vimeo_emblem'), null, $editoroptions);
        $mform->addHelpButton('config_text', 'content', 'block_vimeo_emblem');
        $mform->setType('config_text', PARAM_RAW);

        $mform->addElement('header', 'config_badgesettings', get_string('badgesettings', 'block_vimeo_emblem'));

        $badgeoptions = [0 => get_string('nobadge', 'block_vimeo_emblem')];
        require_once($CFG->libdir . '/badgeslib.php');
        $courseid = !empty($this->page->course->id) ? (int)$this->page->course->id : 0;
        if ($courseid && $courseid != SITEID) {
            $badges = badges_get_badges(BADGE_TYPE_COURSE, $courseid);
            foreach ($badges as $badge) {
                $badgeoptions[$badge->id] = $badge->name;
            }
        }
        $mform->addElement('select', 'config_badgeid', get_string('badgeid', 'block_vimeo_emblem'), $badgeoptions);
        $mform->addHelpButton('config_badgeid', 'badgeid', 'block_vimeo_emblem');
        $mform->setDefault('config_badgeid', 0);
        $mform->setType('config_badgeid', PARAM_INT);

        if (count($badgeoptions) === 1) {
            $mform->addElement('static', 'config_nobadges', '', get_string('nobadgesavailable', 'block_vimeo_emblem'));
        }

        $mform->addElement('text', 'config_threshold', get_string('threshold', 'block_vimeo_emblem'));
        $mform->addHelpButton('config_threshold', 'threshold', 'block_vimeo_emblem');
        $mform->setDefault('config_threshold', 95);
        $mform->setType('config_threshold', PARAM_FLOAT);

        $mform->addElement('select', 'config_prevent_seek', get_string('prevent_seek', 'block_vimeo_emblem'), $yesno);
        $mform->addHelpButton('config_prevent_seek', 'prevent_seek', 'block_vimeo_emblem');
        $mform->setDefault('config_prevent_seek', '0');

        $mform->addElement('select', 'config_show_progress', get_string('show_progress', 'block_vimeo_emblem'), $yesno);
        $mform->addHelpButton('config_show_progress', 'show_progress', 'block_vimeo_emblem');
        $mform->setDefault('config_show_progress', '1');
    }

    /**
     * Load editor draft files.
     *
     * @param stdClass $defaults Form defaults.
     */
    public function set_data($defaults) {
        $text = '';
        if (!empty($this->block->config) && is_object($this->block->config)) {
            $text = $this->block->config->text ?? '';
            $draftid = file_get_submitted_draft_itemid('config_text');
            $defaults->config_text['text'] = file_prepare_draft_area(
                $draftid,
                $this->block->context->id,
                'block_vimeo_emblem',
                'content',
                0,
                ['subdirs' => true],
                $text
            );
            $defaults->config_text['itemid'] = $draftid;
            $defaults->config_text['format'] = $this->block->config->format ?? FORMAT_HTML;
        }

        if (isset($this->block->config)) {
            unset($this->block->config->text);
        }
        parent::set_data($defaults);
        if (!isset($this->block->config)) {
            $this->block->config = new stdClass();
        }
        $this->block->config->text = $text;
    }
}
