# Smart Resume (Reanudar Inteligente)

**Smart Resume** es un plugin local para Moodle diseñado para mejorar la experiencia del estudiante (UX) redirigiéndolo automáticamente a la primera actividad incompleta dentro de un curso.

## Características principales
* **Enfoque en Estudiantes**: Se activa únicamente cuando el usuario tiene el rol de estudiante dentro del curso, evitando afectar la navegación de los profesores, mánagers o administradores.
* **Scroll Automático**: Desplaza la pantalla suavemente hasta la actividad que el estudiante debe realizar a continuación.
* **Resaltado Visual**: Aplica un borde distintivo y una etiqueta personalizada ("Siguiente Actividad") a la actividad pendiente.
* **Optimizado para el Core**: Utiliza el sistema de Hooks moderno de Moodle y la API de Finalización (Completion API).
* **Altamente Eficiente**: No añade tablas a la base de datos, valida de antemano el estado de completitud global y evalúa permisos en tiempo de ejecución para no afectar el rendimiento del servidor.

## Requisitos
* **Moodle**: 4.5 LTS nativo (compatible con 4.0+)
* **Finalización de actividades**: Debe estar habilitada tanto a nivel de sitio como dentro de la configuración del curso.

## Instalación
1. Copia la carpeta `smart_resume` en el directorio `/local/` de tu instalación de Moodle.
2. Ve a _Administración del sitio > Notificaciones_ para completar la instalación y actualizar la base de datos.
3. El plugin estará activo por defecto. Puedes deshabilitarlo globalmente en los ajustes de _Plugins locales > Smart Resume_.

## Privacidad (GDPR)
Este plugin cumple estrictamente con la **Privacy API** de Moodle. No almacena, procesa, rastrea ni transmite datos personales de los usuarios. Únicamente lee el estado de finalización de actividades en tiempo real para mejorar la navegación en la interfaz (frontend).

---
Desarrollado por **Héctor Eduardo Terán Canelones**.