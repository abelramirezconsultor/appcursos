# Documento Técnico - Plataforma Bravon

## 1. Resumen ejecutivo
Bravon es una plataforma web multi-tenant para gestión de cursos con dos niveles principales:
- **Master (central):** administración de cuentas, creación de plataformas (tenants) y acceso al panel central.
- **Tenant (cliente):** operación académica por cliente (estudiantes, cursos, lecciones, activación por código, progreso y gamificación).

La solución está construida sobre Laravel y separa datos centrales y datos por tenant para aislamiento operacional.

## 2. Objetivo funcional
El sistema permite que un usuario administrador:
1. Cree y gestione múltiples plataformas (tenants).
2. Administre contenido académico por tenant.
3. Entregue códigos de activación a estudiantes.
4. Visualice avance y métricas.

El estudiante puede:
1. Iniciar sesión en su plataforma tenant.
2. Activar cursos por código.
3. Consumir lecciones y registrar progreso.
4. Acumular XP, nivel, racha e insignias.

## 3. Stack tecnológico
- **Backend:** PHP 8.3 + Laravel 12
- **Multi-tenancy:** stancl/tenancy (DB central + DB por tenant)
- **Autenticación:** Laravel Breeze (master) + auth separada para estudiante tenant
- **Permisos/Roles:** Spatie Permission
- **Frontend:** Blade + Tailwind (master/admin) y Bootstrap en vistas student tenant
- **Base de datos:** MySQL
- **Entorno local de referencia:** XAMPP en subruta `/appcursos`

## 4. Arquitectura y partición de datos
### 4.1 Base central
Contiene:
- Usuarios master
- Catálogo de tenants
- Metadatos del propietario del tenant

### 4.2 Base por tenant
Contiene datos operativos del cliente:
- Usuarios student
- Cursos, módulos, lecciones
- Códigos de activación y redenciones
- Matrículas, progreso por curso y por lección
- Tablas de gamificación (XP, nivel, racha, insignias)

## 5. Tenancy por entorno
Soporte híbrido configurable por `TENANCY_IDENTIFICATION`:
- **auto (recomendado):**
  - Local: identificación por path `/t/{tenant}`
  - Producción: identificación por subdominio `{alias}.{APP_BASE_DOMAIN}`

Rutas tenant implementadas en [routes/tenant.php](routes/tenant.php).

## 6. Módulos funcionales implementados
### 6.1 Master
- Home pública de marca Bravon.
- Login admin, registro de cuenta y login student de acceso central.
- Dashboard central con listado de plataformas del admin.
- Registro de nuevas plataformas (tenant provisioning).

### 6.2 Tenant admin
- Alta de estudiantes.
- Alta de cursos, módulos y lecciones.
- Configuración de códigos de activación.
- Activar/desactivar códigos.
- Métricas de cursos y leaderboard gamificado.

### 6.3 Tenant student
- Login propio por tenant.
- Activación de curso por código.
- Vista de mis cursos.
- Detalle de curso con lecciones y reproducción de video.
- Marcado de lección completada y actualización de progreso.

### 6.4 Gamificación
- XP por progreso de aprendizaje.
- Nivel calculado a partir de XP.
- Racha diaria por actividad.
- Insignias por hitos.
- Leaderboard visible en panel admin tenant.

## 7. Flujos críticos
### 7.1 Registro inicial de cuenta + primer tenant
Flujo manejado por registro Breeze y servicio de provisioning de tenant.

### 7.2 Creación de tenant adicional por admin autenticado
Desde panel central:
- El formulario de creación tenant usa automáticamente nombre/email del usuario autenticado.
- No solicita contraseña nuevamente.
- Un mismo admin puede crear múltiples tenants con el mismo correo (restricción principal está en alias único).

### 7.3 Flujo estudiante
1. Selección de plataforma desde acceso central.
2. Redirección al login del tenant.
3. Activación de código.
4. Consumo de curso y avance.

## 8. Modelo de datos (alto nivel)
### 8.1 Central
- `users`
- `tenants` (incluye alias, owner_name, owner_email, owner_password, tenancy_db_name)

### 8.2 Tenant
- `users`
- `courses`
- `course_modules`
- `course_lessons`
- `activation_codes`
- `activation_code_redemptions`
- `course_enrollments`
- `course_progress`
- `enrollment_lesson_progress`
- `user_gamification_stats`
- `badges`
- `user_badges`

## 9. Seguridad y controles
- Middleware de autenticación en rutas master sensibles.
- Aislamiento tenant por middleware de tenancy.
- Validaciones de entrada en controladores.
- Alias de tenant único.
- Contraseñas con hash.

## 10. UI/Branding (estado actual)
- Bravon como marca master.
- Logo Bravon en portada principal y dashboard central.
- En vistas tenant se muestra nombre de la plataforma del cliente (`tenant('name')`) en lugar de marca master.
- Login admin/login student/crear cuenta mantienen encabezado consistente con navegación superior.

## 11. Rutas clave
- Master: [routes/web.php](routes/web.php)
- Tenant: [routes/tenant.php](routes/tenant.php)

## 12. Archivos clave de implementación
- Provisioning tenant: [app/Services/Tenancy/RegisterTenantService.php](app/Services/Tenancy/RegisterTenantService.php)
- Registro tenant autenticado: [app/Http/Controllers/TenantRegistrationController.php](app/Http/Controllers/TenantRegistrationController.php)
- Registro de usuario master: [app/Http/Controllers/Auth/RegisteredUserController.php](app/Http/Controllers/Auth/RegisteredUserController.php)
- Navegación dashboard central: [resources/views/layouts/navigation.blade.php](resources/views/layouts/navigation.blade.php)
- Dashboard central: [resources/views/dashboard.blade.php](resources/views/dashboard.blade.php)

## 13. Estado de calidad
- Migraciones tenant ejecutadas.
- Pruebas automatizadas disponibles y en estado verde durante iteraciones recientes.
- Sin errores de Blade en vistas principales tras últimos cambios de branding y flujos.

## 14. Recomendaciones siguientes
1. Extraer documentación funcional en dos manuales: admin master y admin tenant.
2. Estandarizar UI (Tailwind o Bootstrap) para reducir deuda visual.
3. Definir política de ownership cuando un admin maneja múltiples tenants (filtros, permisos finos, auditoría).
4. Preparar checklist de despliegue producción con subdominios y certificados TLS.

---
Documento elaborado para resumen técnico operativo del estado actual del proyecto Bravon.
