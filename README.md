# Smart Resume (`local_smart_resume`)

**Versión actual:** `1.6.0` (`2026111900`) — Compatible con **Moodle 4.5 LTS – 5.3**.

Plugin local para Moodle diseñado para optimizar la experiencia de aprendizaje (UX), guiando y orientando automáticamente al estudiante hacia su primera actividad pendiente de finalización dentro de la vista del curso.

---

## English Summary

**Smart Resume (`local_smart_resume` v1.6.0)** is a lightweight local plugin for **Moodle 4.5 LTS – 5.3** that automatically highlights and scrolls to the student's next incomplete activity when opening a course page.

- **Real-Time Completion Detection:** Evaluates activity completion via Moodle's Completion API (`completion_info`) and `get_fast_modinfo($course, $userid)`, traversing `mod_subsection` delegated sections in visual order while respecting hidden parent sections, stealth modules, and access restrictions.
- **Accessible & Safe UI:** Builds the `"Next Activity"` badge client-side with `textContent` (no raw HTML injection), supports collapsed course sections (`.local-smart-resume-section-highlight`), and honors `prefers-reduced-motion`.
- **Strict Access & Privacy Compliance:** Enforces `can_access_course()` checks, excludes `*-restricted` section pages, and implements `\core_privacy\local\metadata\null_provider` (no personal data stored or transmitted).
- **Verified Quality:** Covered by **35 PHPUnit tests** (`local_smart_resume_testsuite`) and **2 Behat `@javascript` scenarios**, plus automated CI across `MOODLE_405_STABLE`, `MOODLE_501_STABLE`, and `main` on PHP 8.3.
- **Changelog & License:** See [CHANGES.md](CHANGES.md) and [LICENSE](LICENSE) (GNU GPL v3.0 or later).

---

## 🚀 Características principales

- **🎯 Detección Inteligente en Tiempo Real**: Evalúa los criterios de finalización de actividades mediante la Completion API oficial de Moodle (`completion_info`).
- **📂 Soporte Completo para Subsecciones (`mod_subsection`) y Secciones Colapsadas**: Recorre las secciones en orden visual descendiendo en subsecciones delegadas, validando la visibilidad tanto de la sección padre como de la subsección, y resaltando la cabecera/tarjeta (`.local-smart-resume-section-highlight`) cuando el contenido está colapsado.
- **📜 Desplazamiento Adaptable (A11y)**: Sitúa automáticamente el viewport sobre la actividad objetivo respetando la preferencia del sistema operativo `prefers-reduced-motion` (conmutando de forma transparente entre scroll suave o instantáneo).
- **💡 Indicador Visual Distintivo**: Aplica un contenedor de realce con el color primario del tema activo (fallback Moodle `#0f6cbf`), sombra suave y una insignia responsive ("Siguiente actividad") con reglas de contención de texto para evitar desbordamientos en pantallas móviles estrechas. La insignia se construye en cliente con `textContent` (sin inyección de HTML).
- **👁️ Modo Previsualización para Docentes y Administradores**: Opción en configuración para que usuarios con permisos de gestión (`moodle/course:update` o `moodle/course:manageactivities`) verifiquen visualmente el indicador durante el diseño del curso.
- **🛡️ Filtro Estricto de Visibilidad y Permisos**: Valida acceso al curso (`can_access_course()`), respeta actividades ocultas, módulos en modo stealth, restricciones de acceso no cumplidas y excluye páginas de secciones restringidas (`*-restricted`).
- **⚡ Arquitectura de Alto Rendimiento y Desacoplada**:
  - Conectado al hook moderno del core `core\hook\output\before_footer_html_generation`.
  - Lógica de resolución delegada al servicio desacoplado `\local_smart_resume\service\resume_finder`.
  - No añade tablas a la base de datos; opera en memoria aprovechando la caché por curso de la Completion API (`$wholecourse = true`) y `get_fast_modinfo($course, $userid)`.
  - Restringido a páginas de visualización de curso (`course-view-*` / `/course/view.php` y `/course/section.php` acotado a la sección activa).

---

## 📋 Requisitos

- **Moodle**: 4.5 LTS a 5.3 (`requires = 2024100700`, `supported = [405, 503]`).
- **PHP**: Cualquier versión soportada por tu release de Moodle (8.1–8.3 en 4.5 LTS; 8.2 o superior en 5.x).
- **Rastreo de finalización**: Habilitado a nivel de plataforma (`$CFG->enablecompletion`), en la configuración del curso y en las actividades correspondientes.

---

## ⚙️ Configuración

Ubicación: _Administración del sitio > Extensiones > Extensiones locales > Smart Resume_ (`/admin/settings.php?section=local_smart_resume`):

| Ajuste | Clave | Por defecto | Descripción |
|---|---|---|---|
| **Habilitar Smart Resume** | `local_smart_resume/enable` | Activado (`1`) | Habilita o deshabilita la funcionalidad globalmente. |
| **Permitir previsualización** | `local_smart_resume/preview_for_admins` | Desactivado (`0`) | Permite a docentes y administradores visualizar el indicador para pruebas. |

---

## 💻 Instalación

1. Descarga o clona este repositorio en el directorio de plugins locales de tu instalación Moodle (`local/smart_resume` en Moodle 4.5–5.0, o `public/local/smart_resume` en Moodle 5.1+):
   ```bash
   # Moodle 4.5 LTS – 5.0:
   cd /ruta/hacia/moodle/local
   # Moodle 5.1+:
   cd /ruta/hacia/moodle/public/local

   git clone https://github.com/hetcxp/moodle-local_smart_resume.git smart_resume
   ```
2. Accede como administrador a _Administración del sitio > Notificaciones_ para completar la instalación de la extensión.
3. Si es necesario, purga las cachés de la plataforma desde _Administración del sitio > Desarrollo > Purgar cachés_ o vía CLI:
   ```bash
   php admin/cli/purge_caches.php
   ```

---

## 🧪 Testing y Calidad de Código

El plugin cuenta con una suite completa de verificación siguiendo los estándares de desarrollo de Moodle:

- **Análisis Estático (CodeChecker)**:
  ```bash
  phpcs --standard=moodle --extensions=php --ignore='amd/build/*' local/smart_resume
  ```
  Cumple 100% las directrices oficiales de Moodle HQ (0 errores, 0 advertencias).

- **Pruebas Unitarias (PHPUnit)**:
  Suite dedicada `local_smart_resume_testsuite` con **35 pruebas PHPUnit**:
  ```bash
  vendor/bin/phpunit --testsuite local_smart_resume_testsuite
  ```
  Cobertura de casos: portada, plugin inactivo, estudiante con actividad incompleta, curso completado, subsecciones `mod_subsection` y secciones padre ocultas, evaluación estricta por `$userid`, permisos docentes/admin, control de acceso no matriculado, páginas restringidas/externas, actividades stealth, privacidad (`provider_test.php`) y aislamiento de errores.

- **Pruebas de Aceptación (Behat)**:
  **2 escenarios Behat** (`@javascript`) en `tests/behat/resume_label.feature` para validación interactiva de la etiqueta y desplazamiento tras completar actividades.

- **Integración Continua (CI)**:
  Workflow automatizado en `.github/workflows/ci.yml` basado en `moodlehq/moodle-plugin-ci` v4 ejecutando `phplint`, `phpmd`, `codechecker`, `validate`, `savepoints`, `mustache`, `grunt`, `phpunit` y `behat` sobre matriz Moodle `MOODLE_405_STABLE`, `MOODLE_501_STABLE` y `main` en PHP 8.3 (PostgreSQL y MariaDB).

---

## 🔒 Privacidad y GDPR

Cumple estrictamente con la **Privacy API** de Moodle mediante la implementación de `local_smart_resume\privacy\provider` (`null_provider`). Este plugin no almacena, procesa ni transmite datos personales de los usuarios.

---

## 📜 Historial de Cambios

Consulta [CHANGES.md](CHANGES.md) para ver el detalle completo de cada versión.

---

## 📄 Licencia

Este software se distribuye bajo la licencia **GNU General Public License v3.0** (o posterior). Consulta el archivo [LICENSE](LICENSE) para más detalles.
Copyright (C) 2025 Héctor Eduardo Terán Canelones.
