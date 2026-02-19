# Presentación - Bravon

## Diapositiva 1: Portada
**Bravon**  
Plataforma web multi-tenant para gestión de cursos  
Estado del proyecto y próximos pasos

---

## Diapositiva 2: Contexto y oportunidad
- Necesidad de escalar múltiples academias/clientes sin mezclar datos.
- Requerimiento de operación centralizada con autonomía por cliente.
- Demanda de experiencia de aprendizaje medible y motivadora.

---

## Diapositiva 3: Qué es Bravon
- Plataforma **master + tenants**.
- Master: administración global de cuentas y plataformas.
- Tenant: operación académica por cliente (admin y estudiantes).
- Enfoque SaaS B2B para educación digital.

---

## Diapositiva 4: Propuesta de valor
- **Aislamiento de datos por tenant** (seguridad y orden operativo).
- **Escalabilidad** para múltiples clientes desde un solo core.
- **Flujo completo e-learning**: contenido, activación, avance.
- **Gamificación** para retención y engagement.

---

## Diapositiva 5: Arquitectura (alto nivel)
- Laravel 12 + PHP 8.3.
- Tenancy con base central + base por tenant.
- Identificación tenant híbrida:
  - Local: `/t/{tenant}`
  - Producción: subdominios
- Autenticación separada master/student tenant.

---

## Diapositiva 6: Capacidades implementadas
**Master**
- Home de marca Bravon.
- Login admin / registro / login student central.
- Dashboard central y creación de tenants.

**Tenant**
- Login student, activación por código.
- Cursos, módulos, lecciones y progreso.
- Panel admin con métricas y control de códigos.

---

## Diapositiva 7: Gamificación (Fase 2.5)
- XP por actividad.
- Nivel por acumulación de XP.
- Racha diaria por continuidad.
- Insignias por hitos.
- Leaderboard para seguimiento competitivo.

---

## Diapositiva 8: Estado actual
- Backend funcional y estabilizado.
- Flujos críticos operativos (admin + student).
- UI principal alineada a branding Bravon.
- Pruebas automatizadas en verde en iteraciones recientes.

---

## Diapositiva 9: Reglas clave de negocio
- Un admin master autenticado puede crear múltiples tenants.
- Alias tenant único para identificación.
- Marca master (Bravon) separada de marca tenant (nombre del cliente).

---

## Diapositiva 10: Próximos pasos
1. Cierre UX final y consistencia visual total.
2. Manuales operativos (master, tenant admin, student).
3. Hardening productivo (DNS, TLS, backups, monitoreo).
4. Roadmap Fase 3: analítica avanzada y automatizaciones.

---

## Anexo opcional (para conversación)
- Métricas objetivo: activación, progreso, finalización, retención.
- Go-live checklist: infraestructura, seguridad, soporte.
- Plan comercial por tipo de cliente tenant.
