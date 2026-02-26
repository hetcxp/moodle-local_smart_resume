# Smart Resume (Reanudar Inteligente)

**Smart Resume** es un plugin local para Moodle diseñado para mejorar la experiencia del estudiante (UX) al redirigirlo automáticamente a la primera actividad incompleta dentro de un curso.

## Características principales
* **Scroll Automático**: Desplaza la pantalla suavemente hasta la actividad que el estudiante debe realizar a continuación.
* **Resaltado Visual**: Aplica un borde y una etiqueta personalizada a la actividad pendiente.
* **Optimizado para el Core**: Utiliza el sistema de Hooks de Moodle y la API de Finalización (Completion API).
* **Ligero**: No añade tablas a la base de datos ni afecta el rendimiento del servidor.

## Requisitos
* **Moodle**: 4.5 LTS (compatible con 4.0+)
* **Finalización de actividades**: Debe estar habilitada en el sitio y en el curso.

## Instalación
1. Copia la carpeta `smart_resume` en el directorio `/local/` de tu instalación de Moodle.
2. Ve a 'Administración del sitio' > 'Notificaciones' para completar la instalación.
3. Activa el plugin en los ajustes de 'Plugins locales'.

## Privacidad (GDPR)
Este plugin cumple con la **Privacy API** de Moodle. No almacena, procesa ni transmite datos personales de los usuarios. Solo lee el estado de finalización de actividades para mejorar la navegación en el frontend.

---
Desarrollado por **Héctor Eduardo Terán Canelones**.