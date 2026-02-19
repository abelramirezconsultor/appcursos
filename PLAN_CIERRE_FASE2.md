# Plan de cierre Fase 2

## Objetivo
Cerrar los pendientes críticos de Fase 2 con foco en:
1) **F2.1 Multi-tenant avanzado**
2) **F2.6 Evaluación (quizzes)**
3) **F2.8 Notificaciones**

---

## Estado actual (resumen)
- **Completado o muy avanzado:** F2.4, gran parte de F2.3 y F2.5.
- **Parcial:** F2.1, F2.2, F2.7.
- **Pendiente:** F2.6 y F2.8.

---

## Backlog priorizado (ejecutable)

### Bloque A — F2.1 Multi-tenant avanzado (prioridad alta)
### Alcance
- Estado tenant: `active`, `suspended`, `trial`, `cancelled`.
- Plan tenant: `starter`, `pro`, `enterprise`.
- Límites: usuarios, cursos, almacenamiento.
- Branding tenant básico: logo tenant y color primario.

### Tareas técnicas
1. Migración central `tenants`:
	- `status`, `plan_code`, `max_users`, `max_courses`, `max_storage_mb`, `logo_path`, `primary_color`.
2. Validaciones en creación/edición tenant.
3. Middleware/política de límites:
	- Bloquear creación de usuarios/cursos al superar límites.
4. Branding por tenant:
	- Mostrar `tenant('name')`, `logo_path`, `primary_color` en vistas tenant.
5. Panel admin tenant:
	- Tarjeta de uso vs límite (usuarios/cursos).

### Criterios de aceptación
- No se puede crear curso/usuario si excede el límite del plan.
- Tenant en `suspended` no permite login estudiante/admin tenant.
- Branding tenant visible en login y vistas de estudiante.

---

### Bloque B — F2.6 Evaluación (quizzes) (prioridad alta)
### Alcance
- Quiz por curso o módulo.
- Nota mínima de aprobación.
- Desbloqueo por desempeño.

### Tareas técnicas
1. Nuevas tablas tenant:
	- `quizzes`, `quiz_questions`, `quiz_options`, `quiz_attempts`, `quiz_answers`.
2. Flujo de evaluación:
	- Presentar quiz, enviar respuestas, calificar.
3. Reglas de negocio:
	- `minimum_score` por quiz.
	- Bloquear lección/módulo siguiente si no aprueba.
4. UI estudiante:
	- Pantalla de quiz + resultado + reintento.
5. UI admin tenant:
	- CRUD básico de quiz y preguntas.

### Criterios de aceptación
- Se calcula nota y estado `approved/rejected` por intento.
- Si nota < mínima, no se desbloquea el siguiente contenido.
- Se guarda historial de intentos por alumno.

---

### Bloque C — F2.8 Notificaciones (prioridad media-alta)
### Alcance
- Bienvenida de alumno.
- Código asignado.
- Recordatorio de racha y finalización.

### Tareas técnicas
1. Definir canales iniciales:
	- In-app (base) y correo opcional.
2. Eventos y listeners:
	- `StudentCreated`, `ActivationCodeAssigned`, `StreakReminderDue`, `CourseCompleted`.
3. Plantillas:
	- Mensajes y subject por evento.
4. Centro de notificaciones simple:
	- Lista de notificaciones del estudiante.

### Criterios de aceptación
- Se genera notificación al crear alumno y asignar código.
- Se registra recordatorio de racha/finalización (in-app).

---

## Cierre de parciales restantes

### F2.2 Catálogo académico
- Agregar prerequisitos de curso/módulo.
- Regla de desbloqueo por prerequisito cumplido.

### F2.3 Acceso por código
- Intentos fallidos por código/usuario/IP.
- Reglas antiabuso y auditoría de fallos.

### F2.5 Gamificación
- Tablero semanal (filtro por semana).
- Logros por curso (no solo global).

### F2.7 Reportes
- Códigos usados/no usados.
- Conversión por código/curso.
- Avance por curso con export simple CSV.

---

## Orden sugerido de ejecución (3 iteraciones)
1. **Iteración 1 (rápida):** F2.1 completo + cierre F2.3 intentos.
2. **Iteración 2:** F2.6 completo (modelo + flujo + UI).
3. **Iteración 3:** F2.8 + cierre F2.7 + remate F2.5 semanal.

---

## Definición de “Fase 2 cerrada”
- Todos los criterios de aceptación de F2.1 a F2.8 en verde.
- Flujo e2e probado:
  - Crear tenant con plan/límites/branding.
  - Crear catálogo con prerequisitos.
  - Asignar y canjear código con auditoría.
  - Estudiante avanza, rinde quiz, desbloquea, recibe notificaciones.
  - Reportes muestran conversión, avance y ranking semanal.
