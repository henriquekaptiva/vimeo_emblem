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
 * Track unique Vimeo watch time and report it to Moodle.
 *
 * AMD module (define). Do not use native ES modules here: Moodle loads
 * this file with RequireJS. Rebuild amd/build with Moodle's grunt amd
 * before publishing.
 *
 * @module     block_vimeo_emblem/player
 * @copyright  2026 Henrique
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification', 'core/str'], function(Ajax, Notification, Str) {
    const SAVE_INTERVAL = 5000;
    let vimeoApiPromise = null;

    /**
     * @param {Object} config
     */
    const init = (config) => {
        try {
            const root = document.getElementById('inst' + config.instanceid);
            if (!root || root.dataset.vimeoEmblemaReady === '1') {
                return;
            }
            root.dataset.vimeoEmblemaReady = '1';

            const iframe = root.querySelector('iframe[src*="vimeo.com"]');
            if (!iframe) {
                return;
            }

            const videoid = extractVideoId(iframe.getAttribute('src') || '');
            if (!videoid) {
                return;
            }

            const status = root.querySelector('[data-region="vimeo-emblema-status"]');
            updateStatus(status, config, config.percent, config.badgeissued);

            if (config.completed && !config.badgeissued) {
                retryIssue(config, videoid, status);
            }

            if (config.completed && config.badgeissued) {
                return;
            }

            loadVimeoApi()
                .then(function() {
                    bindPlayer(iframe, videoid, config, status);
                })
                .catch(function() {
                    root.dataset.vimeoEmblemaReady = '0';
                });
        } catch (e) {
            // Never break Moodle navigation if the player fails.
        }
    };

    /**
     * @param {Object} config
     * @param {string} videoid
     * @param {Element|null} status
     */
    const retryIssue = (config, videoid, status) => {
        Promise.resolve(Ajax.call([{
            methodname: 'block_vimeo_emblem_save_progress',
            args: {
                instanceid: config.instanceid,
                videoid: videoid,
                seconds: [],
                duration: 0
            }
        }])[0]).then(function(result) {
            updateStatus(status, config, result.percent, result.badgeissued);
            if (result.badgeissued) {
                notifyIssued();
            }
            return result;
        }).catch(function() {
            return null;
        });
    };

    /**
     * @param {string} src
     * @return {string}
     */
    const extractVideoId = (src) => {
        const match = src.match(/vimeo\.com\/video\/(\d+)/);
        return match ? match[1] : '';
    };

    /**
     * Load player.js without letting its UMD wrapper register on RequireJS.
     * An anonymous define() from Vimeo breaks Moodle menus and theme JS.
     *
     * @return {Promise}
     */
    const loadVimeoApi = () => {
        if (window.Vimeo && window.Vimeo.Player) {
            return Promise.resolve();
        }
        if (vimeoApiPromise) {
            return vimeoApiPromise;
        }
        vimeoApiPromise = new Promise(function(resolve, reject) {
            const previousDefine = window.define;
            if (previousDefine && previousDefine.amd) {
                window.define = undefined;
            }
            const script = document.createElement('script');
            script.src = 'https://player.vimeo.com/api/player.js';
            script.async = true;
            script.onload = function() {
                window.define = previousDefine;
                resolve();
            };
            script.onerror = function() {
                window.define = previousDefine;
                reject();
            };
            document.head.appendChild(script);
        });
        return vimeoApiPromise;
    };

    /**
     * @param {HTMLIFrameElement} iframe
     * @param {string} videoid
     * @param {Object} config
     * @param {Element|null} status
     */
    const bindPlayer = (iframe, videoid, config, status) => {
        const player = new window.Vimeo.Player(iframe);
        const dirty = {};
        let dirtyCount = 0;
        let duration = 0;
        let maxWatched = 0;
        let saving = false;
        let completed = !!config.completed;
        let badgeissued = !!config.badgeissued;
        let lastPercent = config.percent || 0;
        let timer = null;

        const addDirty = (second) => {
            if (dirty[second]) {
                return;
            }
            dirty[second] = true;
            dirtyCount++;
        };

        const takeDirty = () => {
            const payload = Object.keys(dirty).map(function(key) {
                return parseInt(key, 10);
            });
            Object.keys(dirty).forEach(function(key) {
                delete dirty[key];
            });
            dirtyCount = 0;
            return payload;
        };

        const queueSave = (immediate) => {
            if (completed && badgeissued) {
                return;
            }
            if (immediate) {
                save();
                return;
            }
            if (timer) {
                return;
            }
            timer = setTimeout(function() {
                timer = null;
                save();
            }, SAVE_INTERVAL);
        };

        const save = () => {
            if (saving || dirtyCount === 0 || duration <= 0) {
                return;
            }
            saving = true;
            const payload = takeDirty();

            Promise.resolve(Ajax.call([{
                methodname: 'block_vimeo_emblem_save_progress',
                args: {
                    instanceid: config.instanceid,
                    videoid: videoid,
                    seconds: payload,
                    duration: duration
                }
            }])[0]).then(function(result) {
                lastPercent = result.percent;
                completed = result.completed;
                const justIssued = result.badgeissued && !badgeissued;
                badgeissued = result.badgeissued;
                updateStatus(status, config, lastPercent, badgeissued);
                if (justIssued) {
                    notifyIssued();
                }
                return result;
            }).catch(function() {
                payload.forEach(addDirty);
            }).then(function() {
                saving = false;
                if (dirtyCount > 0 && !(completed && badgeissued)) {
                    queueSave(false);
                }
            });
        };

        player.getDuration().then(function(value) {
            duration = value || 0;
            return duration;
        }).catch(function() {
            duration = 0;
        });

        player.on('timeupdate', function(data) {
            if (data.duration) {
                duration = data.duration;
            }
            const second = Math.floor(data.seconds);
            maxWatched = Math.max(maxWatched, second);
            addDirty(second);
            if (config.showprogress && duration > 0) {
                const estimate = Math.min(100, (dirtyCount / duration) * 100);
                updateStatus(status, config, Math.max(lastPercent, estimate), badgeissued);
            }
            queueSave(false);
        });

        player.on('ended', function() {
            queueSave(true);
        });

        player.on('pause', function() {
            queueSave(true);
        });

        if (config.preventseek) {
            player.on('seeked', function() {
                player.getCurrentTime().then(function(current) {
                    if (current > maxWatched + 1.25) {
                        return player.setCurrentTime(maxWatched);
                    }
                    return current;
                }).catch(function() {
                    return null;
                });
            });
        }
    };

    /**
     * @param {Element|null} status
     * @param {Object} config
     * @param {number} percent
     * @param {boolean} badgeissued
     */
    const updateStatus = (status, config, percent, badgeissued) => {
        if (!status || !config.showprogress) {
            return;
        }
        status.hidden = false;
        const bar = status.querySelector('.vimeo-emblema-bar');
        const fill = status.querySelector('.vimeo-emblema-bar span');
        const label = status.querySelector('.vimeo-emblema-label');
        const rounded = Math.max(0, Math.min(100, Math.floor(percent || 0)));
        if (bar) {
            bar.setAttribute('aria-valuenow', String(rounded));
        }
        if (fill) {
            fill.style.width = rounded + '%';
        }
        if (!label) {
            return;
        }
        const stringid = badgeissued ? 'badgeissued' : 'progresslabel';
        Str.get_string(stringid, 'block_vimeo_emblem', {
            percent: rounded,
            threshold: config.threshold
        }).then(function(text) {
            label.textContent = text;
            return text;
        }).catch(function() {
            label.textContent = rounded + '%';
        });
    };

    const notifyIssued = () => {
        Str.get_string('badgeissued', 'block_vimeo_emblem').then(function(message) {
            Notification.addNotification({
                message: message,
                type: 'success'
            });
            return message;
        }).catch(function() {
            return null;
        });
    };

    return {
        init: init
    };
});
