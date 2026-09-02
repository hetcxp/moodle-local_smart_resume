// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Smart Resume module for highlighting the first incomplete activity.
 *
 * @module     local_smart_resume/main
 * @copyright  2025 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialize the module.
 *
 * @param {Object} strings Localized strings
 * @param {Number|String} targetCmid Target CMID
 */
export const init = (strings, targetCmid) => {
    const label = strings?.nextactivity ?? '';

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

    // Mark activity and insert badge.
    targetElement.classList.add('local-smart-resume-highlight');

    const existingLabels = targetElement.querySelectorAll('.local-smart-resume-label');
    existingLabels.forEach(el => el.remove());

    targetElement.insertAdjacentHTML('beforeend', label);

    // Detect collapsed section.
    const parentSection = targetElement.closest('.course-section, [data-for="section"], .section');
    const isCollapsed = parentSection && (
        parentSection.classList.contains('collapsed') ||
        parentSection.getAttribute('aria-expanded') === 'false' ||
        parentSection.querySelector('.collapsed, [aria-expanded="false"]') !== null
    );

    if (isCollapsed) {
        parentSection.classList.add('local-smart-resume-section-highlight');
        parentSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
        targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
};