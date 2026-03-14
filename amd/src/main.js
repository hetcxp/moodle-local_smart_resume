// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Smart Resume AMD module.
 *
 * @package    local_smart_resume
 * @copyright  2025 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';

/**
 * Validates and highlights the next activity in the course.
 *
 * @param {Object} strings Localized strings passed from PHP
 * @param {Number|null} targetCmid The course module ID of the first incomplete activity
 */
export const init = (strings, targetCmid) => {
    if (!targetCmid) {
        // No incomplete activity identified by the server.
        return;
    }

    // Find the activity element using the standard Moodle module ID
    // Standard Moodle themes use id="module-<cmid>" on the list item or wrapper div.
    let firstIncomplete = $(`#module-${targetCmid}`);

    // Fallback for some custom themes that might use a different attribute
    if (firstIncomplete.length === 0) {
        // Try searching by data attribute if ID fails
        firstIncomplete = $(`[data-cmid="${targetCmid}"]`);
    }

    // If still not found, we can't do anything.
    if (firstIncomplete.length === 0) {
        return;
    }

    // Ensure we are targeting the main activity card for styling
    // Sometimes the ID is on a list item <li> which contains the card <div>.
    // We want to highlight the card if possible, or the list item if that's the container.
    const activityCard = firstIncomplete.find('.activity-item, .activityinstance, .contentwithoutlink');
    const targetElement = activityCard.length > 0 ? activityCard.first() : firstIncomplete;

    // Scroll to the element
    $('html, body').animate({
        scrollTop: targetElement.offset().top - 150
    }, 800);

    // Add highlight class
    targetElement.addClass('local-smart-resume-highlight');

    // Add label
    // Remove any existing labels first (just in case)
    targetElement.find('.local-smart-resume-label').remove();
    const labelHtml = `<div class="local-smart-resume-label">${strings.nextactivity || 'Next Activity'}</div>`;

    // We append to the target element.
    // Position: relative is set in CSS for .local-smart-resume-highlight to handle absolute positioning of label.
    targetElement.append(labelHtml);
};