# CometaX — landing (cometax.click)

Sitio comercial de CometaX. **100% estático**, sin backend: se despliega solo a
GitHub Pages con la Action del repo. Verificado: cero `fetch`/`XHR`/`api/`, todo
por CDN, ninguna ruta local que pueda dar 404.

## Archivos

- `index.html` — **el sitio**. Todo vive aquí (HTML + `<style>` + `<script>` inline).
- `Landing/index.html` — copia idéntica del mismo archivo.
- `index.backup-*.html` — respaldo previo a la reescritura de julio 2026.
- `frontend/`, `backend/` — otra app (Vite + TS), **no** es la landing.

Dependencias por CDN: Tailwind (config inline en el `<head>`), Three.js r128,
Font Awesome, Google Fonts (Archivo Black + Space Grotesk).

## Estructura de la página (`<main id="view-home">`)

hero → services → cases → **compare (blanca)** → plans → panel → method → faq →
leadership → projects → philanthropy (blanca) → alliances (formulario) → final-cta

Hay una segunda vista oculta, `#view-hub` (Social Hub / carreras), que se alterna
con `showPage('home'|'hub')`.

## Reglas que NO se deben romper

### 1. i18n simétrico
`translations = { en: {...}, es: {...} }` con `data-i18n="clave"` en el HTML.
El literal del HTML es el **inglés**; el español vive solo en el diccionario.

- **Las dos listas deben tener exactamente las mismas claves** (hoy 190 c/u). Si
  agregas texto nuevo, agrégalo a EN **y** ES o el toggle mostrará inglés suelto.
- `toggleLanguage()` cambia el texto dentro de un `setTimeout(..., 200)`. Si
  verificas por script, **espera >300 ms** o leerás el valor viejo y creerás que
  está roto.

### 2. `.euro-style` es `display:inline-block`
Un badge `inline-flex` justo antes de un título `euro-style` **queda en la misma
línea y se encima** (el `scaleX(1.1)` lo monta encima). Ya pasó dos veces.

```html
<!-- MAL: el badge se encima con el h2 -->
<div class="inline-flex ...">badge</div>
<h2 class="euro-style ...">TÍTULO</h2>

<!-- BIEN -->
<div class="flex w-max ...">badge</div>
<div class="euro-wrapper block"><h2 class="euro-style ...">TÍTULO</h2></div>
```

### 3. Las secciones oscuras van SIN fondo propio
El fondo es **un solo campo continuo**: `.space-field` en `<main>` (gradiente +
estrellas por `::before`) más cuatro `.space-orb` (nubes violeta).

Las secciones oscuras son **transparentes** a propósito. Si le pones `bg-[#020202]`
a una, tapa el resplandor de la anterior y **aparece una costura visible** — ese
era el bug de "se ve cortado". Las únicas con fondo propio son las dos blancas
(`#compare`, filantropía) y el hero.

Si una sección lleva contenido en flujo normal, dale `relative z-10` al wrapper
interno para que quede sobre el `::before` del campo.

## El hero (shader del cometa)

`initHeroAnimation()` monta un quad a pantalla completa con `ShaderMaterial`.
No hay partículas ni geometría: **todo es un fragment shader**.

- `cometAt(p, ct, cycle, start, dir, sizeMul, lav)` dibuja un cometa (cabeza,
  halo, cola que se abre). Se llama **dos veces**, desfasadas medio ciclo, para
  que nunca haya pantalla vacía.
- Ciclo ≈ **8.9 s** por cruce (`u_time * 0.27`, `cycle = 2.4`).
- Además: campo de estrellas + nebulosa violeta (fbm).
- Paleta: negro / blanco / violeta (`rankit-purple` #7c3aed).

Ajustes rápidos: velocidad → `u_time * 0.27`; tamaño → los divisores `18.0`
(cabeza) y `5.0` (halo); largo de cola → `1.1`; brillo → los multiplicadores al
final de `cometAt`.

## Formulario → WhatsApp

No hay backend, así que `sendLead()` **arma el mensaje y lo abre en WhatsApp**
(`WA_NUMBER`). Lee `#f-name`, `#f-email`, `#f-type`, `#f-msg` y respeta el idioma
activo. No lo conviertas en un POST a menos que agreguen backend o un servicio
tipo Formspree.

## Datos reales (del catálogo comercial 2026)

- **Correo**: cometax@cometax.click · **Tel/WhatsApp**: +52 55 3235 1392
- **IG**: @cometaxcompany · **Dominio**: cometax.click
- **Planes**: Básico $28,000 MXN/mes (domiciliado $26,000 · 40 h equipo) ·
  Pro $52,000 (domiciliado $50,000 · 80 h) · a medida se cotiza 30/40/30
- **Comparativo**: contratar 1 dev senior en nómina = $35,000–55,000 MXN/mes
- Fundadores: Joseph Fabrizzio Hernández (CEO, arquitectura) y José Ángel
  Soriano (co-founder, comercial)

**No inventes cifras ni casos.** Los casos que se muestran son reales y salen
del CV de Joseph: facturación SAT con millones de XML, rescate de una aseguradora
migrada a AWS sin código fuente, y arquitectura resiliente para 16 gasolineras.

## Cómo verificar cambios

El panel de preview reporta `document.visibilityState === "hidden"`, así que el
navegador **congela `requestAnimationFrame`**: el canvas se lee negro y los
screenshots se cuelgan. Eso **no** significa que el shader esté roto.

- Para validar GLSL: extrae el `fragmentShader` del HTML y **compílalo a mano**
  en un contexto WebGL desechable (`getShaderParameter(..., COMPILE_STATUS)`).
- `readPixels` / `drawImage` sobre el canvas no son fiables sin
  `preserveDrawingBuffer`.
- `<html>` tiene `scroll-smooth`: `scrollIntoView` es asíncrono. Para medir
  posiciones, asigna `window.scrollTo` directo.
- Revisa siempre la consola: un error de sintaxis en el `<script>` **tumba todo**
  (i18n, Three.js, el formulario). Una vez se coló un paréntesis de ancho
  completo (`）`) — si algo deja de funcionar por completo, busca eso primero.

Para previsualizar de verdad: copia `index.html` a una carpeta local, sírvela
(`python -m http.server`) y ábrela en el navegador real del usuario.

## Pendientes

- [ ] **Confirmar el número de WhatsApp** (`WA_NUMBER`) — se tomó del PDF, sin confirmar.
- [ ] Falta la URL del LinkedIn de la empresa (los enlaces siguen en `#`).
- [ ] La foto de Joseph usa el avatar de GitHub; José Ángel es un monograma "JA".
- [ ] Las tarjetas siguen en oro/cyan mientras el hero es violeta — decidir si se
      unifica la paleta.
