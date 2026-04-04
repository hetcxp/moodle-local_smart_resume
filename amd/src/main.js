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
 * @package    local_smart_resume
 * @copyright  2025 Héctor Eduardo Terán Canelones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Validates and highlights the next activity in the course.
 *
 * @param {Object} params Parameters passed from PHP.
 * @param {string} params.label The HTML for the label (rendered by PHP).
 * @param {Number} params.targetCmid The course module ID of the first incomplete activity.
 */
export const init = ({label, targetCmid}) => {
    if (!targetCmid) {
        return;
    }

    // Find the activity element using the standard Moodle module ID.
    let firstIncomplete = document.getElementById(`module-${targetCmid}`);

    // Fallback for some custom themes that might use a different attribute.
    if (!firstIncomplete) {
        firstIncomplete = document.querySelector(`[data-cmid="${targetCmid}"]`);
    }

    if (!firstIncomplete) {
        return;
    }

    // Ensure we are targeting the main activity card for styling.
    const selectors = '.activity-item, .activityinstance, .contentwithoutlink';
    const activityCard = firstIncomplete.querySelector(selectors);
    const targetElement = activityCard || firstIncomplete;

    // Scroll to the element.
    targetElement.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });

    // Add highlight class.
    targetElement.classList.add('local-smart-resume-highlight');

    // Add label.
    // Remove any existing labels first.
    const existingLabels = targetElement.querySelectorAll('.local-smart-resume-label');
    existingLabels.forEach(el => el.remove());

    // Create label from the HTML string provided by PHP.
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = label.trim();
    const labelElement = tempDiv.firstChild;

    if (labelElement) {
        targetElement.appendChild(labelElement);
    }
};