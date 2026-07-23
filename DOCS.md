# CometaX — Documentación del proyecto

Plataforma de CometaX: **landing comercial** (estática) + **panel** (Laravel) con
portal de cliente y panel interno (staff). Todo vive en `cometax.click` (Hostinger
compartido).

---

## 1. Arquitectura general

```
cometax.click/                 ← landing estática (public_html/index.html)
cometax.click/panel            ← app Laravel (portal cliente + admin)
cometax.click/panel/app        ← mockup estático de referencia (demo del portal)
```

- **Landing** (`index.html` en la raíz del repo): 100% estática, Tailwind
  **compilado a CSS inline** (sin CDN), animación del hero en canvas 2D, i18n
  ES/EN con auto-detección por navegador, formulario que envía por correo
  (endpoint `/panel/api/v1/contacto`, 2/IP/hora) o WhatsApp, y chatbot FAQ.
- **Backend** (`backend/`): Laravel 13 / PHP 8.3. Sirve el panel bajo `/panel`
  mediante un front controller en `public_html/panel/index.php` que apunta a
  `laravel/` (fuera del document root). Ver `backend/DEPLOY-HOSTINGER.md`.
- **Base de datos**: MySQL (`u413918370_CometaxDB`).

### Deploy
1. `git push origin main`.
2. En el server: `cd repo && git pull` (el symlink `laravel -> repo/backend`
   lo refleja), luego en `laravel/`: `migrate --force`, `optimize:clear`,
   `config:cache`, `view:cache`. **No** `route:cache` (rompe la raíz en
   subdirectorio). La landing se despliega con `git pull` en `public_html`.

---

## 2. Autenticación y roles

Dos guards de sesión (`config/auth.php`):

| Guard | Modelo | Quién | Login |
|-------|--------|-------|-------|
| `web` | `User` | Clientes (usuarios de un `Client`) | `/panel/login` (correo+password o Google) |
| `consultant` | `Consultant` | Staff interno | `/panel/admin/login` (correo+password) |

- **Clientes**: auto-registro con **verificación de correo** obligatoria
  (`MustVerifyEmail`). También login con Google (Socialite).
- **Staff**: se provisionan; definen contraseña por enlace (broker
  `consultants` + tabla propia). `ADMIN_EMAILS` en `.env` da rol `super_admin`
  al entrar por Google.
- Roles de `Consultant`: `consultant | admin | super_admin`. `super_admin`
  gestiona consultores. Middleware `staff` (`EnsureConsultantIsStaff`).
- **Ver como cliente**: el staff puede impersonar a un cliente
  (`ImpersonationController`); barra ámbar mientras dura.

---

## 3. Modelo de datos (tablas)

### Cuentas y planes
- **plans** — catálogo. `name, slug, price_cents` (pago único), `price_domiciliado_cents`
  (suscripción), `included_hours`, `hourly_overage_rate_cents`, `stripe_price_id_recurring`,
  `stripe_price_id_onetime`, `is_public`, `max_clients`, `sort_order`.
- **clients** — empresa/tenant y **entidad facturable de Cashier**. `name, slug,
  contact_email, contact_phone, rfc, address, plan_id`.
- **users** — usuarios de un cliente (guard web). `client_id, name, email,
  email_verified_at, password, role (admin|member), google_id, avatar_url`.
- **consultants** — staff. `name, title, email, password, role, google_id`.
- **password_reset_tokens** / **consultant_password_reset_tokens** — reset por guard.

### Facturación (Stripe / Cashier)
- **subscriptions**, **subscription_items** — suscripciones de Cashier (billable = `Client`).
- **invoices** — facturas propias. `client_id, plan_id, concept, amount_cents,
  currency, status (pendiente|pagado|fallido), paid_at, invoice_date, payment_method_masked`.

### Proyectos
- **projects** — `client_id, lead_consultant_id, name, slug, description,
  status (activo|en_revision|finalizado), start_date, estimated_delivery,
  progress_percent, hours_budgeted, hours_used`.
- **milestones** — hitos. `project_id, label, sort_order, status (pending|in_progress|done)`.
- **project_consultant** — pivote proyecto↔consultor (`role_label`).
- **time_entries** — registro de horas. `project_id, consultant_id, entry_date, activity, hours, notes`.
- **project_activities** — feed de avances. `project_id, actor (morph), action, description, occurred_at`.
- **project_links** — enlaces (GitHub/staging/etc). `project_id, kind, label, url`.
- **documents** — archivos/contratos. `client_id, project_id, type (document|contract),
  title, filename, file_path, version, status, signed_date, term_length, renewal_date, uploaded_by`.

### Incidencias
- **incidents** — tickets. `project_id, ticket_code (A-xxx, auto), title, description,
  priority (baja|media|urgente), status (nuevo|revision|progreso|resuelto),
  assignee_consultant_id, reporter_user_id, resolved_at`.
- **incident_attachments** — evidencia. `incident_id, kind (image|link), path, url,
  label, source (cliente|equipo)`. Imágenes en disco `public` (`/panel/storage/…`).

### Agenda
- **appointments** — citas. `client_id|lead_id (uno u otro), consultant_id, meeting_type,
  appointment_date, start_time, end_time, location, status (solicitada|confirmada|cancelada|completada), notes`.
- **blocked_slots** — bloqueos de agenda. `date, all_day, start_time, end_time, reason, consultant_id`.

### Comercial
- **leads** — prospectos (formulario landing). `name, email, phone, source, plan_id, status, notes`.
- **quotes**, **quote_scope_items**, **quote_quote_scope_item** — cotizaciones.
- **plan_waitlist_entries** — lista de espera de planes.
- **announcements** — avisos del equipo al cliente. `client_id (null = todos),
  consultant_id, title, body, published_at`.

---

## 4. Portales

### Portal de cliente (`/panel`, guard web + `verified`)
Resumen, Proyectos (+detalle con tabs), Incidencias (kanban, reporta y adjunta
evidencia), Facturación, Contratos, Calendario (rejilla de mes, solicita
reunión; respeta días bloqueados), Soporte. Gating: plan gratis puede agendar;
pago accede a todo.

### Panel interno (`/panel/admin`, guard consultant + `staff`)
Resumen (MRR, cobrado, cartera), Clientes (CRUD + usuarios + "ver como cliente"),
Proyectos (avances, hitos, horas, enlaces, asignar consultores), Incidencias
(kanban con cambio de estado y evidencia), Calendario (citas de todos + bloquear
días/horarios + confirmar/cancelar), Citas, Avisos, Suscripciones,
Consultores (solo super_admin: alta + invitación por correo).

---

## 5. Convenciones

- Vistas comparten layouts: `layouts.app` (base), `layouts.admin` y
  `layouts.client` (sidebar por rol). Estilo dark, `rounded-card`/`rounded-control`,
  Tailwind por CDN en el panel (la landing sí está compilada).
- El panel usa el logo real (`partials/logo`).
- Toda consulta del cliente va **scopeada** a su `Client`; los detalles
  verifican propiedad (403 si no).
- Correo: SMTP Hostinger, remitente `cometax@cometax.click`.
