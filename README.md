# Smart Resume (Reanudar Inteligente)

**Smart Resume** es un plugin local para Moodle diseñado para mejorar la experiencia del estudiante (UX) redirigiéndolo y resaltando automáticamente la primera actividad incompleta dentro de la vista del curso.

## Características principales
* **Enfoque en Estudiantes con Previsualización de Gestión**: Por defecto, opera exclusivamente para estudiantes. Incluye una opción de configuración para permitir que administradores y docentes previsualicen el indicador en el curso para fines de verificación.
* **Soporte para Secciones Colapsadas**: Si la actividad pendiente se encuentra dentro de una sección plegada, el plugin resalta suavemente la cabecera/tarjeta de la sección (`.local-smart-resume-section-highlight`) y sitúa el scroll para orientar al alumno sin romper el diseño del acordeón.
* **Scroll Automático y Badge Visual**: Aplica un borde distintivo, sombra suave y una etiqueta ("Siguiente actividad") sobre el módulo pendiente.
* **Filtro de Visibilidad Estricto**: Ignora actividades en modo stealth o no visibles en la página del curso, garantizando que el objetivo sea siempre accesible.
* **Optimizado para el Core y Rendimiento**:
  - Utiliza el sistema moderno de Hooks (`core\hook\output\before_footer_html_generation`).
  - Restringido exclusivamente a páginas de curso (`course-view-*` / `/course/view.php`).
  - No crea tablas adicionales en la base de datos y opera en memoria mediante la Completion API y `get_fast_modinfo`.

## Requisitos
* **Moodle**: 4.5 LTS o superior (requiere build `2024100700`+ con soporte para Hooks modernos).
* **Rastreo de finalización**: Debe estar habilitado tanto a nivel de sitio (`enablecompletion`) como dentro de los ajustes del curso y en las actividades correspondientes.

## Configuración
En _Administración del sitio > Extensiones > Extensiones locales > Smart Resume_ (`/admin/settings.php?section=local_smart_resume`):
* **Habilitar Smart Resume**: Activa o desactiva la funcionalidad de forma global.
* **Permitir previsualización a docentes y administradores**: Permite que usuarios con capacidades de gestión (`moodle/course:update` o `moodle/course:manageactivities`) visualicen el indicador para pruebas.

## Instalación
1. Descarga o copia la carpeta `smart_resume` en el directorio `/local/` de tu instalación de Moodle (`/local/smart_resume`).
2. Ve a _Administración del sitio > Notificaciones_ para registrar el plugin.
3. Purga las cachés de ser necesario (_Administración del sitio > Desarrollo > Purgar cachés_ o via CLI `php admin/cli/purge_caches.php`).

## Privacidad (GDPR)
Este plugin cumple estrictamente con la **Privacy API** de Moodle (`local_smart_resume\privacy\provider`). No almacena, rastrea ni transfiere datos personales. Únicamente consulta el estado de finalización de actividades en tiempo real para renderizar la asistencia visual en frontend.

---
Desarrollado por **Héctor Eduardo Terán Canelones**.