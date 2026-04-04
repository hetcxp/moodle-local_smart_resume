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
 * Smart Resume AMD module (Standard format for max compatibility).
 *
 * @package    local_smart_resume
 * @copyright  2025 Héctor Eduardo Terán Canelones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    return {
        init: function(strings, targetCmid) {
            var label = strings.nextactivity || "Next Activity";
            
            // Check if strings or targetCmid were swapped or combined (defensive coding).
            if (typeof strings === 'object' && strings.targetCmid && !targetCmid) {
                targetCmid = strings.targetCmid;
                label = strings.label || strings.nextactivity || label;
            }

            if (!targetCmid) {
                return;
            }

            // Try multiple selectors common in Moodle 4.x.
            var selectors = [
                '#module-' + targetCmid,                        // Classic/Boost ID
                '[data-id="' + targetCmid + '"]',               // Common in 4.x activity wrappers
                '[data-cmid="' + targetCmid + '"]',             // Custom or newer formats
                '.activity[id*="section-"][id*="-item-' + targetCmid + '"]' // Some course formats
            ];

            var targetElement = null;
            for (var i = 0; i < selectors.length; i++) {
                targetElement = document.querySelector(selectors[i]);
                if (targetElement) {
                    break;
                }
            }

            if (!targetElement) {
                return;
            }

            // Scroll to the element.
            targetElement.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            // Add highlight class.
            targetElement.classList.add('local-smart-resume-highlight');

            // Remove any existing labels to avoid duplication.
            var existingLabels = targetElement.querySelectorAll('.local-smart-resume-label');
            existingLabels.forEach(function(el) { el.remove(); });

            // Insert label HTML fragment.
            targetElement.insertAdjacentHTML('beforeend', label);
        }
    };
});