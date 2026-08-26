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
 * Smart Resume ESM module.
 *
 * @copyright  2025 Héctor Eduardo Terán Canelones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialize the module.
 *
 * @param {Object} strings Localized strings
 * @param {Number|String} targetCmid Target CMID
 */
export const init = (strings, targetCmid) => {
    let label = strings.nextactivity || "Next Activity";

    if (typeof strings === 'object' && strings.targetCmid && !targetCmid) {
        targetCmid = strings.targetCmid;
        label = strings.label || strings.nextactivity || label;
    }

    if (!targetCmid) {
        return;
    }

    const selectors = [
        `#module-${targetCmid}`,
        `[data-id="${targetCmid}"]`,
        `[data-cmid="${targetCmid}"]`,
        `.activity[id*="section-"][id*="-item-${targetCmid}"]`
    ];

    let targetElement = null;
    for (const selector of selectors) {
        targetElement = document.querySelector(selector);
        if (targetElement) {
            break;
        }
    }

    if (!targetElement) {
        return;
    }

    targetElement.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });

    targetElement.classList.add('local-smart-resume-highlight');

    const existingLabels = targetElement.querySelectorAll('.local-smart-resume-label');
    existingLabels.forEach(el => el.remove());

    targetElement.insertAdjacentHTML('beforeend', label);
};