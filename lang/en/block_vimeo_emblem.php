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
 * English language strings for the Vimeo emblem block.
 *
 * @package   block_vimeo_emblem
 * @copyright 2026 Henrique
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Vimeo video badge';
$string['vimeo_emblem'] = 'Vimeo video badge';
$string['vimeo_emblem:addinstance'] = 'Add a new Vimeo video badge block';
$string['vimeo_emblem:myaddinstance'] = 'Add a new Vimeo video badge block to the Dashboard';

$string['show_as_card'] = 'Show block as card';
$string['show_header'] = 'Show block header';
$string['custom_title'] = 'Custom block title';
$string['custom_subtitle'] = 'Custom block subtitle';
$string['content'] = 'Content (Vimeo embed)';
$string['content_help'] = 'Paste the Vimeo embed HTML (iframe). The block tracks unique seconds watched and issues the selected badge when the threshold is reached.';

$string['badgesettings'] = 'Badge settings';
$string['badgeid'] = 'Badge to issue';
$string['badgeid_help'] = 'Choose an active course badge. It is issued automatically when the user watches at least the configured percentage of unique video time. Create and enable the badge in the course first.';
$string['nobadge'] = 'Do not issue a badge';
$string['nobadgesavailable'] = 'No course badges found. Create a course badge before selecting it here.';
$string['threshold'] = 'Watch percentage required';
$string['threshold_help'] = 'Percentage of unique seconds the user must watch before the badge is issued (for example 95). Seeking to the end without watching does not count.';
$string['prevent_seek'] = 'Prevent skipping ahead';
$string['prevent_seek_help'] = 'If enabled, the viewer cannot jump beyond the furthest second already watched.';
$string['show_progress'] = 'Show watch progress';
$string['show_progress_help'] = 'Displays a progress bar under the video for the logged-in user.';

$string['progresslabel'] = 'Watched: {$a->percent}% (badge at {$a->threshold}%)';
$string['badgeissued'] = 'Badge issued. You watched the required portion of the video.';
$string['alreadycompleted'] = 'You have already completed this video.';

$string['privacy:metadata'] = 'The Vimeo video badge block stores watch progress for each user in order to issue a badge.';
$string['privacy:metadata:block_vimeo_emblem'] = 'Video watch progress linked to a block instance.';
$string['privacy:metadata:instanceid'] = 'The block instance ID.';
$string['privacy:metadata:userid'] = 'The ID of the user who watched the video.';
$string['privacy:metadata:videoid'] = 'The Vimeo video ID.';
$string['privacy:metadata:watched'] = 'The unique seconds watched.';
$string['privacy:metadata:duration'] = 'The video duration in seconds.';
$string['privacy:metadata:percent'] = 'The unique watch percentage.';
$string['privacy:metadata:completed'] = 'Whether the watch threshold was reached.';
$string['privacy:metadata:badgeissued'] = 'Whether the badge was issued.';
$string['privacy:metadata:timecreated'] = 'When progress tracking started.';
$string['privacy:metadata:timemodified'] = 'When progress was last updated.';
$string['privacy:metadata:vimeo'] = 'The block loads the Vimeo player in the browser. Vimeo receives the request made to play the video.';
$string['privacy:metadata:vimeo:ipaddress'] = 'The user IP address is sent to Vimeo by the browser when the player is loaded.';
$string['privacy:metadata:vimeo:videoid'] = 'The Vimeo video ID is requested from Vimeo so the player can be shown.';
