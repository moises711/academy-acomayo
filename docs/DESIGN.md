# Sistema visual de Academia

## Tokens

- **Marca:** azul tinta `#173B66`; azul acción `#2563EB`; acento cálido `#F2A93B`.
- **Superficies:** fondo `#F5F7FB`, tarjeta `#FFFFFF`, texto `#172033`, texto secundario `#64748B`, borde `#E2E8F0`.
- **Estados:** éxito `#15803D`, pendiente `#B45309`, error/falta `#B91C1C`, información `#0369A1`.
- **Degradados informativos:** azul, violeta, cian, ámbar y esmeralda; alternan por tarjeta y aumentan contraste en modo oscuro.
- **Tipografía:** Geist Variable; títulos con peso 650–750, texto general 400–500.
- **Espaciado:** escala de 4 px (1, 2, 3, 4, 6, 8, 10 y 12 unidades Tailwind).
- **Radios:** control 10 px, tarjeta 16 px, panel destacado 24 px.
- **Sombras:** controles `0 1px 2px #0f172a0d`; tarjetas `0 12px 32px #0f172a0a`; diálogo `0 24px 64px #0f172a26`.
- **Panel lateral:** 256 px abierto y 56 px compacto en escritorio; drawer en móvil.

## Componentes base

`PageHeader`, `StatCard`, `DataTable`, `EmptyState`, `Skeleton`, `ConfirmDialog`, `FormField`,
`StatusBadge` y `FileUpload` mantienen tipografía, radios, foco, estados y espaciado comunes.
Las tablas muestran filtros, búsqueda, orden y paginación; en pantallas pequeñas permiten
desplazamiento horizontal contenido o una vista de tarjetas.

## Reglas de uso

- Reutilizar variables CSS y utilidades compartidas; no introducir colores ni tamaños aislados.
- Mantener modo claro y oscuro, foco visible, etiquetas asociadas y contraste legible.
- Verificar el flujo a 360, 768 y 1280 px. En móvil, el panel lateral se abre como drawer.
- Las vistas de datos distinguen carga, vacío, error con reintento y datos disponibles.
- Formularios muestran errores junto al campo, estado de guardado y confirmación para acciones destructivas.
- La configuración del sitio público se divide por secciones y ofrece una vista previa parcial en vivo antes de publicar.
- La interfaz, validaciones visibles y mensajes se redactan en español. Importes en soles y fechas `dd/mm/aaaa`.

## Navegación y adaptación móvil

- Panel: barra inferior por debajo de 768 px, con iconos y etiquetas, accesos según permisos y botón Menú para abrir el drawer completo.
- Sitio público: barra inferior y menú de secciones por debajo de 1024 px.
- Reservar espacio para la barra y el área segura del teléfono; los diálogos quedan por encima.
- Cuadrículas y formularios permiten reducir sus columnas; tablas con scroll contenido, filas de acciones con ajuste de línea y campos a 16 px en teléfonos.
