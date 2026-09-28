# Moodle Plugins Directory & Marketplace Listing Kit
**Plugin:** Smart Resume (`local_smart_resume`)  
**Version:** 1.4.1 | **Maturity:** Stable | **Target:** Moodle 4.5 LTS+ (`2024100700`+)  
**License:** GNU General Public License v3.0 (GPLv3)  
**Author:** Héctor Eduardo Terán Canelones  
**Guidelines Reference:** [Moodle Marketplace Submission Standards](https://moodle.atlassian.net/wiki/external/OTE4YmM3MDlmM2ZjNDczMGFlNzRlZGM0YTlmNGVlNDU)  
**Deliverables:** Listing copy (EN/ES), visual badges, and complete HTML snippets.

---

## 📋 Compliance & Marketing Checklist

| Requirement | Guideline Limit | Implementation Status | Notes |
|:---|:---|:---|:---|
| **Short Description** | Max 270 chars (Target ≤ 256 chars) | **198 chars (EN)** / **203 chars (ES)** | Front-loads the core value proposition in the first 119 chars before card truncation. |
| **Full Description (HTML)** | Max 7,000 chars | **~5,420 chars (EN)** / **~3,820 chars (ES)** | Clean semantic HTML with tables, headings, and lists ready for Moodle's editor. |
| **Plugin Icon** | 200 × 200 px (PNG/SVG) | **200 × 200 px RGBA & SVG** | Generated in `pix/marketplace_icon_200x200.png` and `pix/icon.svg`. |
| **External Dependencies** | Explicit disclosure | **Zero external services** | 100% self-hosted, no third-party accounts, no telemetry. |
| **Pricing / Business Model**| Commercial disclosure | **100% Free & Open Source** | GNU General Public License v3.0. |
| **GDPR & Privacy** | Moodle Privacy API | **Fully Compliant** | Implements `\core_privacy\local\metadata\null_provider`. Zero personal data stored. |
| **Database Footprint** | Architectural impact | **Zero tables added** | Operates purely in-memory via Moodle Completion API & `get_fast_modinfo`. |

---

# 🇬🇧 English Listing (Official Marketplace Submission)

### Plugin Title
```text
Smart Resume
```

### Short Description (Max 256 characters)
```text
Automatically guides students to their next incomplete activity with smooth scrolling and visual highlighting. Eliminates course navigation friction, saves time, and keeps learners focused on track.
```
* **Character count:** 198 / 256 characters
* **Card View (First 119 characters before truncation):**  
  `Automatically guides students to their next incomplete activity with smooth scrolling and visual highlighting. Eliminate`

---

### Full Description (HTML Source Code for Moodle Editor)
> Source HTML for Moodle plugin directory submission.

```html
<h2>Never Let Your Students Ask "Where Was I?" Again</h2>

<p>Navigating extensive Moodle courses with dozens of resources, assignments, and weekly topics often leads to cognitive overload and disorientation. Students waste valuable study time manually scanning course pages to identify where they left off.</p>

<p><strong>Smart Resume</strong> eliminates navigation friction by instantly orienting learners the moment they enter a course. Using Moodle's native activity completion data, it calculates the first pending task, smoothly scrolls the viewport to it, and highlights the module with a distinctive, accessible visual badge.</p>

<hr>

<h3>🎯 Who Is This For?</h3>
<ul>
    <li><strong>Higher Education Institutions:</strong> Improves student retention and digital onboarding across multi-topic semester syllabi with high activity volume.</li>
    <li><strong>Corporate Training &amp; Compliance L&amp;D:</strong> Helps busy professionals resume self-paced compliance tracks immediately without re-reading the entire course index.</li>
    <li><strong>K-12 &amp; Vocational Academies:</strong> Provides guided visual scaffolding for younger learners or students who struggle with long-page navigation.</li>
    <li><strong>Instructional Designers &amp; Moodle Admins:</strong> Instantly upgrades the UX of existing courses across the entire site with zero content restructuring.</li>
</ul>

<hr>

<h3>✨ Key Capabilities &amp; Benefits</h3>
<ul>
    <li><strong>🎯 Automatic Next-Activity Detection:</strong> Evaluates activity completion states in real time using native Moodle core APIs.</li>
    <li><strong>📜 Smooth Viewport Auto-Scroll:</strong> Gently animates the browser scroll position to bring the active module directly into the student's focal field.</li>
    <li><strong>💡 High-Visibility Pulse &amp; Badge:</strong> Encloses the incomplete activity in a branded accent border with soft glow and a clear <em>"Next Activity"</em> pill badge.</li>
    <li><strong>📂 Collapsed Section Intelligence:</strong> If the pending activity resides inside a closed section or accordion, Smart Resume softly highlights the parent section card without breaking accordion markup.</li>
    <li><strong>👁️ Preview Mode for Teachers &amp; Admins:</strong> Includes an optional administrative toggle to allow educators to preview the student resume cue during course design and verification.</li>
    <li><strong>🛡️ Strict Visibility Filter:</strong> Respects access restrictions, stealth activities, and group locks—only genuinely accessible activities are highlighted.</li>
    <li><strong>⚡ High-Performance Architecture:</strong> Hooks into Moodle's modern hook system (<code>core\hook\output\before_footer_html_generation</code>). Adds no custom tables, leveraging Moodle's optimized in-memory <code>get_fast_modinfo</code> and whole-course completion caching.</li>
</ul>

<hr>

<h3>📊 Before &amp; After: The UX Impact</h3>
<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%; margin-top: 12px; margin-bottom: 12px;">
    <thead>
        <tr style="background-color: #f1f5f9;">
            <th style="text-align: left; padding: 8px;">Feature</th>
            <th style="text-align: left; padding: 8px;">Standard Moodle Course View</th>
            <th style="text-align: left; padding: 8px;">With Smart Resume Enabled</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="padding: 8px;"><strong>Orientation Time</strong></td>
            <td style="padding: 8px;">10 to 30 seconds searching and scrolling</td>
            <td style="padding: 8px;"><strong>Instant (&lt; 0.5s auto-scroll &amp; focus)</strong></td>
        </tr>
        <tr style="background-color: #f8fafc;">
            <td style="padding: 8px;"><strong>Cognitive Friction</strong></td>
            <td style="padding: 8px;">High (learner must remember last activity)</td>
            <td style="padding: 8px;"><strong>Zero (guided checkpoint cue)</strong></td>
        </tr>
        <tr>
            <td style="padding: 8px;"><strong>Visual Beacon</strong></td>
            <td style="padding: 8px;">Standard checkbox list</td>
            <td style="padding: 8px;"><strong>Highlighted card + "Next Activity" badge</strong></td>
        </tr>
        <tr style="background-color: #f8fafc;">
            <td style="padding: 8px;"><strong>Collapsed Sections</strong></td>
            <td style="padding: 8px;">Learner must open each topic manually</td>
            <td style="padding: 8px;"><strong>Parent section card is visually flagged</strong></td>
        </tr>
        <tr>
            <td style="padding: 8px;"><strong>Database Overhead</strong></td>
            <td style="padding: 8px;">N/A</td>
            <td style="padding: 8px;"><strong>Zero new tables, 100% in-memory cache</strong></td>
        </tr>
        <tr style="background-color: #f8fafc;">
            <td style="padding: 8px;"><strong>Teacher Setup</strong></td>
            <td style="padding: 8px;">Manual links or custom page design</td>
            <td style="padding: 8px;"><strong>Zero course maintenance required</strong></td>
        </tr>
    </tbody>
</table>

<hr>

<h3>🚀 Getting Started in 3 Steps</h3>
<ol>
    <li><strong>Install:</strong> Place the <code>smart_resume</code> folder in your Moodle root <code>/local/smart_resume</code> and visit <em>Site Administration &gt; Notifications</em>.</li>
    <li><strong>Verify Prerequisites:</strong> Ensure completion tracking is enabled at site level (<em>Site administration &gt; Advanced features &gt; Enable completion tracking</em>) and in your course settings.</li>
    <li><strong>Configure (Optional):</strong> Visit <em>Site Administration &gt; Plugins &gt; Local plugins &gt; Smart Resume</em> to enable teacher preview mode if desired.</li>
</ol>

<hr>

<h3>⚙️ Prerequisites &amp; Compatibility</h3>
<ul>
    <li><strong>Moodle Version:</strong> 4.5 LTS or higher (Build <code>2024100700</code>+ with modern Core Hooks support).</li>
    <li><strong>Completion Tracking:</strong> Enabled (<code>enablecompletion = 1</code>).</li>
    <li><strong>PHP:</strong> Compatible with PHP 8.1, 8.2, and 8.3.</li>
    <li><strong>Database:</strong> Fully independent (MySQL, MariaDB, PostgreSQL, Oracle, MSSQL).</li>
</ul>

<hr>

<h3>🔒 Privacy, Security &amp; GDPR</h3>
<ul>
    <li><strong>Moodle Privacy API:</strong> Fully implements <code>\core_privacy\local\metadata\null_provider</code>.</li>
    <li><strong>Zero Telemetry:</strong> No user data, completion timestamps, or telemetry are stored or transmitted.</li>
    <li><strong>100% On-Premise:</strong> Operates entirely within your local Moodle instance without third-party web service connections.</li>
</ul>

<hr>

<h3>❓ Frequently Asked Questions (FAQ)</h3>
<p><strong>Does this plugin alter my course layout or break custom themes?</strong><br>
No. Smart Resume non-destructively overlays standard CSS classes and uses standard JavaScript scrolling. It harmonizes seamlessly with Boost and modern third-party themes.</p>

<p><strong>What happens if a course does not have completion tracking enabled?</strong><br>
The plugin safely does nothing. If completion is disabled or if a student has completed all activities, course view functions normally without any visual interruption.</p>

<p><strong>Does it highlight hidden or restricted activities?</strong><br>
No. Smart Resume strictly checks module visibility and availability conditions. Only activities the student currently has permission to access are considered.</p>
```

---

# 🇪🇸 Ficha en Español (Para Comunidades Hispanohablantes)

### Título del Plugin
```text
Smart Resume (Reanudar Inteligente)
```

### Descripción Corta (Máximo 256 caracteres)
```text
Guía automáticamente al estudiante a su siguiente actividad pendiente mediante scroll suave y resaltado visual. Reduce la fricción de navegación, ahorra tiempo y mantiene el enfoque del alumno en Moodle.
```
* **Conteo de caracteres:** 203 / 256 caracteres

---

### Descripción Completa (Código Fuente HTML para el Editor de Moodle)
> Código fuente HTML para copiar y pegar en el directorio de plugins de Moodle.

```html
<h2>Elimina la Desorientación: Guía a tus Alumnos Directo a su Próximo Desafío</h2>

<p>En cursos extensos con decenas de recursos y actividades organizadas por temas o semanas, los estudiantes sufren fatiga cognitiva intentando recordar en qué punto del itinerario se quedaron la última vez que iniciaron sesión.</p>

<p><strong>Smart Resume</strong> transforma la experiencia de navegación del estudiante (UX) en Moodle. Al ingresar al curso, el plugin identifica automáticamente la primera actividad incompleta, desplaza suavemente la pantalla hacia ella y la resalta con un borde distintivo y la etiqueta <em>"Siguiente Actividad"</em>.</p>

<hr>

<h3>🎯 Casos de Uso y Audiencias</h3>
<ul>
    <li><strong>Universidades y Educación Superior:</strong> Agiliza la navegación en asignaturas semestrales densas con múltiples entregas semanales.</li>
    <li><strong>Capacitación Corporativa y Compliance:</strong> Permite a profesionales con tiempo acotado retomar programas obligatorios de forma inmediata.</li>
    <li><strong>Academias y Formación Online:</strong> Reduce la tasa de abandono temprano ofreciendo un punto focal claro en cada sesión de estudio.</li>
    <li><strong>Administradores y Diseñadores Instruccionales:</strong> Moderniza la interfaz de todos los cursos del campus sin necesidad de rediseñar contenidos.</li>
</ul>

<hr>

<h3>✨ Características Principales</h3>
<ul>
    <li><strong>🎯 Detección Inteligente en Tiempo Real:</strong> Evalúa los criterios de finalización de actividades mediante la Completion API oficial de Moodle.</li>
    <li><strong>📜 Desplazamiento Suave (Auto-Scroll):</strong> Sitúa automáticamente el viewport sobre la actividad objetivo sin saltos bruscos.</li>
    <li><strong>💡 Resaltado Visual y Badge Dinámico:</strong> Aplica un contenedor con borde azul Moodle (<code>#0f6cbf</code>), sombra suave y una insignia destacada.</li>
    <li><strong>📂 Compatibilidad con Secciones Colapsadas:</strong> Si la tarea pendiente está dentro de un acordeón cerrado, resalta la cabecera de la sección orientando al alumno sin alterar la estructura del tema.</li>
    <li><strong>👁️ Modo Previsualización para Docentes:</strong> Ajuste opcional en administración para que profesores y diseñadores puedan verificar el comportamiento del indicador.</li>
    <li><strong>🛡️ Respeto Estricto de Permisos y Restricciones:</strong> Ignora módulos ocultos, en modo stealth o condicionados por restricciones de acceso no cumplidas.</li>
    <li><strong>⚡ Cero Impacto en Base de Datos:</strong> Emplea el sistema moderno de Hooks (<code>core\hook\output\before_footer_html_generation</code>) y opera en memoria con <code>get_fast_modinfo</code>.</li>
</ul>

<hr>

<h3>📊 Comparativa UX: Antes y Después</h3>
<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%; margin-top: 12px; margin-bottom: 12px;">
    <thead>
        <tr style="background-color: #f1f5f9;">
            <th style="text-align: left; padding: 8px;">Criterio</th>
            <th style="text-align: left; padding: 8px;">Moodle Estándar</th>
            <th style="text-align: left; padding: 8px;">Con Smart Resume</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="padding: 8px;"><strong>Tiempo de Orientación</strong></td>
            <td style="padding: 8px;">10 a 30 segundos buscando la actividad</td>
            <td style="padding: 8px;"><strong>Inmediato (&lt; 0.5s con scroll suave)</strong></td>
        </tr>
        <tr style="background-color: #f8fafc;">
            <td style="padding: 8px;"><strong>Carga Cognitiva</strong></td>
            <td style="padding: 8px;">Alta (el alumno debe memorizar su avance)</td>
            <td style="padding: 8px;"><strong>Cero (señalización directa y clara)</strong></td>
        </tr>
        <tr>
            <td style="padding: 8px;"><strong>Secciones Colapsadas</strong></td>
            <td style="padding: 8px;">Desorientación si la tarea está plegada</td>
            <td style="padding: 8px;"><strong>Resaltado inteligente de la sección padre</strong></td>
        </tr>
        <tr style="background-color: #f8fafc;">
            <td style="padding: 8px;"><strong>Impacto en Servidor</strong></td>
            <td style="padding: 8px;">N/A</td>
            <td style="padding: 8px;"><strong>Cero tablas nuevas, 100% en memoria</strong></td>
        </tr>
        <tr>
            <td style="padding: 8px;"><strong>Configuración Docente</strong></td>
            <td style="padding: 8px;">Manual y propensa a desactualización</td>
            <td style="padding: 8px;"><strong>100% automático basado en Completion</strong></td>
        </tr>
    </tbody>
</table>

<hr>

<h3>🚀 Instalación y Puesta en Marcha</h3>
<ol>
    <li>Copia la carpeta <code>smart_resume</code> en el directorio <code>/local/</code> de tu instalación (<code>/local/smart_resume</code>).</li>
    <li>Ingresa a <em>Administración del sitio &gt; Notificaciones</em> para completar el registro del plugin.</li>
    <li>Asegúrate de tener habilitado el rastreo de finalización (<em>Administración del sitio &gt; Características avanzadas &gt; Habilitar rastreo de finalización</em>).</li>
</ol>

<hr>

<h3>🔒 Privacidad y Cumplimiento GDPR</h3>
<p>Cumple rigurosamente con la <strong>Privacy API</strong> de Moodle (<code>local_smart_resume\privacy\provider</code>). No recopila, almacena ni comparte datos de carácter personal; opera únicamente como una capa visual en frontend.</p>
```
