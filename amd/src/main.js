/**
 * Smart Resume AMD module.
 *
 * @package    local_smart_resume
 * @copyright  2025 Héctor Eduardo Terán Canelones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {
    return {
        init: function(strings, targetCmid) {
            if (!targetCmid) {
                // No incomplete activity identified by the server.
                return;
            }

            // Find the activity element using the standard Moodle module ID
            // Standard Moodle themes use id="module-<cmid>" on the list item or wrapper div.
            let firstIncomplete = $('#module-' + targetCmid);

            // Fallback for some custom themes that might use a different attribute
            if (firstIncomplete.length === 0) {
                // Try searching by data attribute if ID fails
                firstIncomplete = $('[data-cmid="' + targetCmid + '"]');
            }

            // If still not found, we can't do anything.
            if (firstIncomplete.length === 0) {
                return;
            }

            // Ensure we are targeting the main activity card for styling
            // Sometimes the ID is on a list item <li> which contains the card <div>.
            // We want to highlight the card if possible, or the list item if that's the container.
            // Adjust selection to ensure visibility.
            // If it's an LI, sometimes the visual "card" is inside.
            // But usually highlighting the LI is fine.
            // Let's check if there's a specific 'activity-card' inside to be more specific with the border.
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
            const labelHtml = '<div class="local-smart-resume-label">' +
                (strings.nextactivity || 'Next Activity') +
                '</div>';

            // We append to the target element.
            // Position: relative is set in CSS for .local-smart-resume-highlight to handle absolute positioning of label.
            targetElement.append(labelHtml);
        }
    };
});