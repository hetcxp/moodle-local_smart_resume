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

/** @type {Function|null} Disposes the observer of a previous init() call, if still active. */
let disposeActiveObserver = null;

/**
 * Initialize the module.
 *
 * @param {Object} strings Localized strings
 * @param {Number|String} targetCmid Target CMID
 */
export const init = (strings, targetCmid) => {
    const rawCmid = targetCmid !== null && targetCmid !== undefined ? String(targetCmid).trim() : '';
    if (!rawCmid || !/^\d+$/.test(rawCmid)) {
        return;
    }
    const cmid = Number(rawCmid);
    if (!Number.isSafeInteger(cmid) || cmid <= 0) {
        return;
    }

    const rawLabel = typeof strings?.nextactivity === 'string' ? strings.nextactivity : '';
    const label = rawLabel.trim();

    if (disposeActiveObserver) {
        disposeActiveObserver();
        disposeActiveObserver = null;
    }

    const selectors = [
        `[data-for="cmitem"][data-id="${cmid}"]`,
        `#module-${cmid}`,
        `.activity[data-id="${cmid}"]`,
        `[data-cmid="${cmid}"]`,
        `.activity[id*="section-"][id$="-item-${cmid}"]`
    ];

    const findTarget = () => {
        for (const selector of selectors) {
            const el = document.querySelector(selector);
            if (el) {
                return el;
            }
        }
        return null;
    };

    const applyHighlight = (targetElement) => {
        const courseContainer = document.querySelector('#course-content, #region-main') || document;

        // Limpiar resaltados previos en el contenedor y cualquier badge previo.
        courseContainer.querySelectorAll('.local-smart-resume-highlight').forEach(el => {
            el.classList.remove('local-smart-resume-highlight');
            if (el.getAttribute('aria-current') === 'step') {
                el.removeAttribute('aria-current');
            }
        });
        courseContainer.querySelectorAll('.local-smart-resume-section-highlight').forEach(el => {
            el.classList.remove('local-smart-resume-section-highlight');
        });
        courseContainer.querySelectorAll('.local-smart-resume-label').forEach(el => {
            el.remove();
        });

        // Resaltar actividad con semántica ARIA estandarizada para pasos en progreso.
        targetElement.classList.add('local-smart-resume-highlight');
        targetElement.setAttribute('aria-current', 'step');

        // Inserción accesible de badge sin inyección HTML insegura.
        if (label) {
            const badge = document.createElement('div');
            badge.className = 'local-smart-resume-label';
            badge.setAttribute('role', 'status');
            badge.setAttribute('aria-label', label);
            badge.setAttribute('data-cmid', String(cmid));
            badge.textContent = label;
            targetElement.appendChild(badge);
        }

        // Detección selectiva de sección colapsada (evita falsos positivos en botones internos).
        const parentSection = targetElement.closest('.course-section, [data-for="section"], .section');
        let isCollapsed = false;

        if (parentSection) {
            const sectionCollapseBtn = parentSection.querySelector(
                '[data-toggle="collapse"], [data-bs-toggle="collapse"], .section-collapse-button, [data-action="collapsesection"]'
            );
            const contentElement = parentSection.querySelector('.course-section-header ~ .content, .section-summary-activities');

            isCollapsed = parentSection.classList.contains('collapsed') ||
                parentSection.getAttribute('data-is-expanded') === '0' ||
                (sectionCollapseBtn && sectionCollapseBtn.getAttribute('aria-expanded') === 'false') ||
                (contentElement && window.getComputedStyle(contentElement).display === 'none') ||
                (!targetElement.isConnected || targetElement.getClientRects().length === 0);
        }

        // Accesibilidad: respetar preferencia de movimiento reducido.
        const prefersReducedMotion = window.matchMedia &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const scrollBehavior = prefersReducedMotion ? 'auto' : 'smooth';

        if (isCollapsed) {
            parentSection.classList.add('local-smart-resume-section-highlight');
            parentSection.scrollIntoView({behavior: scrollBehavior, block: 'center'});
        } else {
            targetElement.scrollIntoView({behavior: scrollBehavior, block: 'center'});
        }
    };

    const initialTarget = findTarget();
    if (initialTarget) {
        applyHighlight(initialTarget);
        return;
    }

    // Soporte para renderizado diferido y reactivo de formatos de curso (Moodle 4.5+ y 5.x).
    if (typeof MutationObserver !== 'undefined') {
        const rootContainer = document.querySelector('#course-content, #region-main') || document.body;
        let observer = null;
        let timeoutId = null;
        let frameId = null;

        const cleanupObserver = () => {
            if (observer) {
                observer.disconnect();
                observer = null;
            }
            if (timeoutId) {
                clearTimeout(timeoutId);
                timeoutId = null;
            }
            if (frameId !== null) {
                window.cancelAnimationFrame(frameId);
                frameId = null;
            }
            if (disposeActiveObserver === cleanupObserver) {
                disposeActiveObserver = null;
            }
        };
        disposeActiveObserver = cleanupObserver;

        observer = new MutationObserver((mutations) => {
            if (!mutations.some(m => m.addedNodes.length > 0 || m.type === 'attributes')) {
                return;
            }
            if (frameId !== null) {
                return;
            }
            frameId = window.requestAnimationFrame(() => {
                frameId = null;
                const deferredTarget = findTarget();
                if (deferredTarget) {
                    cleanupObserver();
                    applyHighlight(deferredTarget);
                }
            });
        });

        observer.observe(rootContainer, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'aria-expanded', 'data-is-expanded', 'hidden', 'aria-hidden', 'style']
        });

        // Limitar la ventana de observación a 3 segundos para evitar memory leaks.
        timeoutId = setTimeout(cleanupObserver, 3000);
    }
};