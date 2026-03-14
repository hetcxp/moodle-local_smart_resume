<?php
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
 * Smart Resume plugin before_footer hook.
 *
 * @package    local_smart_resume
 * @copyright  2025 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smart_resume\hook;

use core\hook\output\before_footer_html_generation;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook to inject Smart Resume AMD module on course view pages.
 */
class before_footer {
    /**
     * Callback for the before_footer_html_generation hook.
     *
     * @param before_footer_html_generation $hook
     */
    public static function execute(before_footer_html_generation $hook): void {
        global $PAGE, $USER, $CFG;

        // Seguridad: Verificar acceso de usuario válido ($USER no guest, isloggedin).
        if (!isloggedin() || isguestuser()) {
            return;
        }

        // Optimización: Verificar que la completitud esté habilitada a nivel de sitio.
        // Esto evita procesamientos y carga de librerías innecesarios.
        if (empty($CFG->enablecompletion)) {
            return;
        }

        // Check if plugin is enabled globally.
        if (!get_config('local_smart_resume', 'enable')) {
            return;
        }

        // Check if we are on the main course page (seguridad y prevención de errores nulos).
        if ($PAGE->pagelayout === 'course' && $PAGE->has_set_url() && strpos($PAGE->url->get_path(), '/course/view.php') !== false) {
            $course = $PAGE->course;

            // Instanciar el contexto del curso para evaluar los roles del usuario.
            $context = \context_course::instance($course->id);
            $user_roles = get_user_roles($context, $USER->id, true);
            
            $has_student_role = false;
            foreach ($user_roles as $role) {
                // Chequear por el shortname estándar o el arquetipo de estudiante.
                if ($role->shortname === 'student' || $role->archetype === 'student') {
                    $has_student_role = true;
                    break;
                }
            }

            // Si a pesar de tener otros roles, NO posee el rol de estudiante explícitamente, cancelamos el proceso.
            if (!$has_student_role) {
                return;
            }

            // Ensure completion lib is loaded dynamically only when it's necessary.
            require_once($CFG->libdir.'/completionlib.php');
            $completion = new \completion_info($course);

            // Validar si el curso especifico tiene completitud habilitada.
            if (!$completion->is_enabled()) {
                return;
            }

            // Determine the first incomplete activity.
            $first_incomplete_cmid = null;
            $modinfo = get_fast_modinfo($course);

            foreach ($modinfo->get_cms() as $cm) {
                // Seguridad interna: chequeo de visibilidad (Obligatorio regla 3).
                if (!$cm->uservisible) {
                    continue;
                }
                
                // Skip if completion is not tracked for this activity.
                if ($cm->completion == COMPLETION_TRACKING_NONE) {
                    continue;
                }
                
                // Moodle Coding Style: snake_case variables.
                $completion_data = $completion->get_data($cm, true, $USER->id);
                
                // Check if incomplete (0).
                if ($completion_data->completionstate == COMPLETION_INCOMPLETE) {
                    $first_incomplete_cmid = $cm->id;
                    break;
                }
            }

            // Preparar strings para el JS (Regla 4 Cadenas de Texto AMD).
            $strings = [
                'nextactivity' => get_string('nextactivity', 'local_smart_resume')
            ];

            // Initialize the AMD module con los datos de forma segura.
            $PAGE->requires->js_call_amd('local_smart_resume/main', 'init', [$strings, $first_incomplete_cmid]);
        }
    }
}