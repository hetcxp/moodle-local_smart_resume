# Smart Resume (`local_smart_resume`)

Plugin local para Moodle diseñado para optimizar la experiencia de aprendizaje (UX), guiando y orientando automáticamente al estudiante hacia su primera actividad pendiente de finalización dentro de la vista principal del curso.

---

## 🚀 Características principales

- **🎯 Detección Inteligente en Tiempo Real**: Evalúa los criterios de finalización de actividades mediante la Completion API oficial de Moodle (`completion_info`).
- **📜 Desplazamiento Adaptable (A11y)**: Sitúa automáticamente el viewport sobre la actividad objetivo respetando la preferencia del sistema operativo `prefers-reduced-motion` (conmutando de forma transparente entre scroll suave o instantáneo).
- **💡 Indicador Visual Distintivo**: Aplica un contenedor de realce con el color primario del tema activo (fallback Moodle `#0f6cbf`), sombra suave y una insignia responsive ("Siguiente actividad") con reglas de contención de texto para evitar desbordamientos en pantallas móviles estrechas. La insignia se construye en cliente con `textContent` (sin inyección de HTML).
- **📂 Compatibilidad con Secciones Colapsadas**: Si la tarea pendiente se encuentra dentro de un acordeón o sección cerrada, resalta la cabecera/tarjeta de la sección (`.local-smart-resume-section-highlight`) orientando al alumno sin alterar la estructura del tema.
- **👁️ Modo Previsualización para Docentes y Administradores**: Opción en configuración para que usuarios con permisos de gestión (`moodle/course:update` o `moodle/course:manageactivities`) verifiquen visualmente el indicador durante el diseño del curso.
- **🛡️ Filtro Estricto de Visibilidad y Permisos**: Respeta actividades ocultas, módulos en modo stealth y restricciones de acceso no cumplidas.
- **⚡ Arquitectura de Alto Rendimiento y Desacoplada**:
  - Conectado al hook moderno del core `core\hook\output\before_footer_html_generation`.
  - Lógica de resolución delegada al servicio desacoplado `\local_smart_resume\service\resume_finder`.
  - No añade tablas a la base de datos; opera en memoria aprovechando la caché por curso de la Completion API (`$wholecourse = true`) y `get_fast_modinfo`.
  - Restringido exclusivamente a páginas de visualización de curso (`course-view-*` / `/course/view.php`).

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

1. Descarga o clona este repositorio en el directorio `local/smart_resume` de tu instalación Moodle:
   ```bash
   cd /ruta/hacia/moodle/local
   git clone https://github.com/tu-usuario/moodle-local_smart_resume.git smart_resume
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
  phpcs --standard=moodle --extensions=php local/smart_resume
  ```
  Cumple 100% las directrices oficiales de Moodle HQ (0 errores, 0 advertencias).

- **Pruebas Unitarias (PHPUnit)**:
  Suite dedicada `local_smart_resume_testsuite` con 10 escenarios:
  ```bash
  vendor/bin/phpunit --testsuite local_smart_resume_testsuite
  ```
  Cobertura de casos: portada, plugin inactivo, estudiante con actividad incompleta, curso completado, permisos docentes/admin, actividades stealth, páginas externas, páginas sin contexto de curso inicializado y aislamiento de errores del servicio `resume_finder`.

- **Pruebas de Aceptación (Behat)**:
  Escenarios BDD en `tests/behat/resume_label.feature` para validación interactiva de la etiqueta y desplazamiento tras completar actividades.

- **Integración Continua (CI)**:
  Workflow automatizado en `.github/workflows/ci.yml` ejecutando lint de sintaxis PHP y Moodle CodeChecker en PHP 8.3.

---

## 🔒 Privacidad y GDPR

Cumple estrictamente con la **Privacy API** de Moodle mediante la implementación de `local_smart_resume\privacy\provider` (`null_provider`). Este plugin no almacena, procesa ni transmite datos personales de los usuarios.

---

## 📄 Licencia

Este software se distribuye bajo la licencia **GNU General Public License v3.0** (o posterior). Consulta el archivo de licencia oficial para más detalles.
Copyright (C) 2025 Héctor Eduardo Terán Canelones.