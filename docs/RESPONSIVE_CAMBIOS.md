# Ajustes de adaptación móvil

Barra inferior con iconos y etiquetas en el panel y sitio público. El panel selecciona accesos según permisos y permite abrir todos los módulos mediante Menú. Se corrigen anchos del sidebar para Tailwind 3, cierre accesible del drawer, límites de tablas, encogimiento de tarjetas y formularios, filas que envuelven su contenido y desplazamiento de diálogos altos.

## Archivos creados

- `resources/js/Components/MobileNavigation.tsx`
- `docs/RESPONSIVE_CAMBIOS.md` (este informe)

## Archivos modificados

- `docs/DESIGN.md`
- `resources/css/app.css`
- `resources/js/Layouts/AuthenticatedLayout.tsx`
- `resources/js/Components/AppSidebar.tsx`
- `resources/js/Components/Sidebar.tsx`
- `resources/js/Components/ui/sheet.tsx`
- `resources/js/Components/PageShell.tsx`
- `resources/js/Components/Table.tsx`
- `resources/js/Components/Modal.tsx`
- `resources/js/Components/FormModal.tsx`
- `resources/js/Pages/Welcome.tsx`
- `resources/js/Pages/Dashboard.tsx`
- `resources/js/Pages/Auth/VerifyEmail.tsx`
- `resources/js/Pages/Estudiantes/Show.tsx`
- `resources/js/Pages/Notas/Index.tsx`
- `resources/js/Pages/Carnets/Diseno.tsx`
- `resources/js/Pages/Reportes/Index.tsx`
- `resources/js/Pages/Pagos/Comprobante.tsx`
- `resources/js/Pages/Usuarios/Index.tsx`
- `resources/js/Pages/Mi/Dashboard.tsx`

- `public/build/manifest.json` y recursos generados por Vite al compilar.

## Archivos eliminados

Ningún archivo fuente. Vite regenera sus recursos de compilación.

## Verificación

- `npx tsc --noEmit`: correcto.
- `./vendor/bin/pint`: correcto; no hubo cambios PHP.
- `npm run build`: correcto; Vite avisa de opciones obsoletas del plugin React existente.
- `php artisan test`: no pasa; falta el controlador PDO MySQL. También advierte que no puede escribir `.phpunit.result.cache`.
- `./vendor/bin/pint --dirty`: no disponible porque la carpeta no es un repositorio Git; se ejecutó Pint completo.
- Base de datos sin cambios; no corresponde ejecutar `migrate:fresh`.

## Pendiente

Revisión visual e interacción de todos los módulos a 360, 768 y 1280 px, en ambos temas, y pruebas PHP en un entorno con PDO MySQL. No se añadieron dependencias ni pruebas de navegador: el proyecto no tiene una suite frontend instalada. La compilación valida el código, pero no confirma el resultado visual de cada vista.
