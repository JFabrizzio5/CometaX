# CometaX

Agencia de tecnología para PYMES: arquitectura, desarrollo y mantenimiento de
software con un equipo de 6+ especialistas, al costo de una sola contratación
interna. Landing estática + panel (Laravel) en `cometax.click`.

- **Documentación técnica** (arquitectura, tablas, deploy): [`DOCS.md`](DOCS.md).
- **Backend:** `backend/` (Laravel 13 / PHP 8.3). **Landing:** `index.html`.

---

# Estrategia de negocio y contenido

> Contexto: agencia boutique, **capacidad ~3–4 clientes** (mix objetivo 2 caros
> de 50 h + 3 baratos de 20 h ≈ 160 h/mes). Fundadores: **Joseph** (técnico/CEO)
> y **José Ángel** (comercial). Con poca capacidad la estrategia **no es volumen,
> es precisión y retención.**

## 1. Posicionamiento
- **Boutique premium, cupo limitado.** No competir por precio con "soluciones IA
  genéricas". Vender: equipo senior real + casos medibles + garantía.
- **Escasez real:** "Solo tomamos 3–4 clientes por trimestre." Sube valor
  percibido, justifica precio y filtra.
- **Diferenciador (prueba real):** facturación SAT con millones de XML, rescate
  de una aseguradora en AWS sin código fuente, arquitectura resiliente para 16
  gasolineras. Eso el contenido genérico no lo tiene — ponerlo grande, con números.

## 2. Mix de clientes y economía
- 2 caros (50 h) + 3 baratos (20 h) = **160 h/mes** (techo de capacidad).
- Los **baratos son la puerta de entrada** (land) → subir a caro cuando ven
  resultados (expand).
- ⚠️ **Retención = supervivencia:** perder 1 caro ≈ −30 % de ingreso. Marketing
  sin retención = balde con hoyo. Usar el panel admin (reportes de avance, avisos,
  reuniones) para mantener al cliente feliz → renueva, sube de plan y refiere.

## 3. Canales (priorizados para poca capacidad)
1. **Referidos + red directa (#1).** Pedir referidos a cada cliente/contacto;
   LinkedIn de los fundadores. Casi gratis, leads calientes.
2. **Outbound selectivo, no masivo.** Lista de 30–50 empresas ideales en un
   nicho con caso propio (gasolineras, despachos contables/SAT, aseguradoras).
   Mensaje personalizado. 30 bien elegidos > 1000 fríos.
3. **Google Business Profile (CDMX)** — para el que ya busca; local; gratis;
   rankea rápido. Pedir reseñas.
4. **Remarketing Meta (Pixel).** Anuncios solo a quien ya visitó la landing.
   Barato y caliente. Es donde Meta gana (el cold B2B quema presupuesto).
5. **Contenido por vertical** (SEO long-tail + autoridad).
- **No** gastar en ads de volumen: no se puede atender el volumen.

## 4. Marca personal → agencia (Joseph)
Muy rentable para este caso. Contenido técnico de Joseph construye autoridad y
manda leads calientes y baratos a la agencia.
- **Temas:** cómo diseñar arquitecturas, cómo resolver problemas reales, migrar
  sistemas legacy, facturación SAT masiva, "un jr sale caro → mejor
  **team-as-a-service**", comparativo costo dev interno vs CometaX.
- **Formatos:** hilos/posts técnicos en LinkedIn/X, video corto con resultados,
  casos de estudio escritos por vertical.
- El fundador da la cara; el contenido educa y precalifica.

## 5. Publicidad pagada
- **Cómo saber costos reales:** Google **Keyword Planner** (gratis) → CPC +
  volumen por keyword y región. No adivinar.
- **Google Ads:** pujar por **long-tail nicho** (no genéricas caras) y mandar a
  una **landing específica** del servicio, no al home. CPC más barato, mejor
  conversión. Da leads **ya** mientras el SEO madura.
- **Meta:** remarketing + **lead ads** + video de casos. CPC más barato que
  Google pero leads más fríos → filtrar con 1–2 preguntas de calificación.
- **Presupuesto de arranque:** ~$3,000–5,000 MXN/mes; escalar lo que convierte.
  Costo por lead ≈ CPC ÷ conversión.
- (Estimaciones de mercado MX, no verificadas en vivo: validar con Keyword Planner.)

## 6. SEO
- **Google Search Console** + enviar `sitemap.xml` + solicitar indexación. La
  **posición promedio de GSC** es el dato real, no la búsqueda manual.
- **Ganable pronto:** marca ("CometaX") y long-tail nicho.
- **Difícil pronto** (dominio nuevo): genéricas competidas → requieren contenido
  + backlinks + tiempo.
- **Backlinks:** Clutch, GoodFirms, directorios de agencias, GitHub, prensa/partners.
- La landing ya trae: meta description/OG/Twitter, `robots.txt`, `sitemap.xml`,
  `llms.txt`, y `<meta keywords>` (ojo: Google ignora keywords para ranking).

## 7. Reparto de roles
- **Joseph (técnico/CEO):** arquitectura, entrega, decisiones técnicas, liderar
  al equipo, scoping del diagnóstico.
- **José Ángel (comercial/ops):** pipeline y cierre, **account management y
  retención**, ejecución de marketing (Business Profile, ads, contenido, redes),
  operaciones (contratos, facturación, agenda, el panel admin), referidos y
  alianzas. Es la cara del cliente.
- Sync semanal de 30 min: pipeline, clientes en riesgo, entregas.

## 8. Embudo y KPIs
- **Embudo:** diagnóstico gratis (ya en la landing/portal) → llamada → plan
  barato (land) → subir a caro (expand).
- **KPIs (medir a José Ángel):** reuniones/leads por mes, tasa de cierre,
  **retención/renovaciones**, y **MRR** (visible en `/panel/admin` → Resumen y
  Suscripciones).

## 9. Ideas de contenido concretas (backlog)
- Caso de estudio: "Facturación SAT con millones de XML sin caídas".
- Caso: "Rescatamos una plataforma en AWS sin código fuente".
- Post: "Team-as-a-service vs contratar un dev senior: el costo real".
- Serie: "Arquitectura para PYMES" (1 problema → 1 solución por post).
- Video corto: antes/después con métricas de un proyecto.
- Página por vertical (gasolineras / despachos SAT / aseguradoras) para
  outbound + Ads long-tail.
