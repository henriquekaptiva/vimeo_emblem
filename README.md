# Vimeo video badge

Awards a course badge after a learner watches a Vimeo video embedded in a course block. The block counts unique seconds watched and issues the selected badge when the configured percentage is reached.

This plugin is not affiliated with Vimeo.

## Requirements

- Moodle 4.5, 5.1, 5.2 or 5.3.
- A course badge that has already been created and enabled.
- A Vimeo video that allows embedding.
- The learner's browser must be able to load `https://player.vimeo.com`.

No extra Moodle plugin and no Composer install are required.

## Installation

1. Upload the ZIP in Site administration > Plugins > Install plugins, or unzip it so the folder is `moodle/blocks/vimeo_emblem`.
2. Open Site administration > Notifications and complete the install.

The ZIP contains one root folder, `vimeo_emblem`, with `version.php` inside it.

## Setup

1. In the course, create a badge and enable it.
2. Turn editing on and add the Vimeo video badge block to the course page.
3. Paste the Vimeo embed HTML (the iframe).
4. Choose the badge and the watch percentage. The default is 95.

The block can be added to course pages only.

## Watch progress

The browser reports unique seconds to Moodle. Seeking to the end without watching those seconds does not increase the percentage. "Prevent skipping ahead" is applied in the browser only.

A learner who can open the course can also submit watched seconds through the web service. Do not use this block as proof for high-stakes certification.

## Privacy

The block stores, for each user and block instance, the Vimeo video id, the unique seconds watched, the duration, the percentage, whether the threshold was reached, and whether the badge was issued.

Loading the player sends the user's IP address and the video id to Vimeo from the browser.

## License

GNU GPL v3 or later. See http://www.gnu.org/copyleft/gpl.html.
