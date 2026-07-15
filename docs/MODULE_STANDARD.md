# Estándar de módulos (VMS Cloud + Filament)

Este documento define cómo añadir un **módulo de dominio** de forma uniforme. Un módulo agrupa todo lo necesario para una entidad de negocio gestionada en el panel (y, si aplica, en API futura).

El panel admin vive en el namespace **`App\Filament\Admin\`** (recursos, páginas y widgets), según `AdminPanelProvider`.

## Plantilla visual (referencia “Máquinas”)

Muchos módulos del sistema legacy combinan:

1. **Página de módulo / inicio**: KPIs arriba (tarjetas), gráficos de tendencia, rankings con pestañas, gráficos de proporción abajo.
2. **CRUD en modales**: listado en tabla; crear, editar y (si aplica) ver en **ventanas emergentes**, no en pantallas completas separadas.

En Filament esto se traduce así:

| Pieza legacy | En Filament |
|--------------|-------------|
| Tarjetas de totales | `StatsOverviewWidget` (uno o varios por módulo) |
| Gráfico de líneas / barras | `ChartWidget` o paquete de charts compatible con v5 |
| Tablas tipo ranking | `TableWidget` o recurso/listado con vista compacta |
| Donut / proporción | widget de chart o custom view |
| CRUD en modales | recurso **simple** (`--simple`) o acciones de tabla en modal |

**Convención CRUD:** salvo excepción acordada, generar recursos con:

```bash
php artisan make:filament-resource NombreModelo --simple --panel=admin --model-namespace=App\\Models
```

(`--simple` = una sola página de listado, formularios e infolist en **modales** y esquemas embebidos; añade `--view` si necesitas ver detalle en modal.)

Los **submódulos** (pestañas “Usuarios / Máquinas / Productos” dentro del mismo contexto) pueden ser: recursos distintos bajo el mismo **navigation group**, `RelationManager`, pestañas en una página custom, o un `TableWidget` con tabs; se decide caso a caso en la captura.

## 1. Nomenclatura

| Concepto | Convención | Ejemplo |
|----------|------------|---------|
| **Nombre del módulo** | Inglés, **plural**, PascalCase. Es el nombre “de carpeta” y de agrupación. | `VendingMachines` |
| **Modelo principal** | Inglés, singular, PascalCase, coincide con una tabla. | `VendingMachine` → tabla `vending_machines` |
| **Recurso Filament** | `{Modelo}Resource` | `VendingMachineResource` |
| **Policy** | `{Modelo}Policy` | `VendingMachinePolicy` |
| **Servicio** | Verbo o dominio + `Service`, una clase por responsabilidad clara | `VendingMachineProvisioningService` |

**Etiquetas de navegación** (Filament): en **inglés** en el panel admin actual (cliente US): `->navigationLabel()`, `->modelLabel()`, textos de formulario/tablas, etc. El código y nombres técnicos siguen en inglés.

## 2. Estructura de carpetas

```
app/
├── Models/
│   └── VendingMachine.php
├── Policies/
│   └── VendingMachinePolicy.php
├── Services/
│   └── VendingMachines/                    # coincide con el nombre del módulo (plural)
│       └── VendingMachineProvisioningService.php
└── Filament/
    └── Admin/
        ├── Resources/
        │   └── VendingMachines/            # un subdirectorio por módulo
        │       └── VendingMachineResource.php
        ├── Pages/                          # dashboard del módulo, informes, etc.
        │   └── VendingMachines/
        └── Widgets/
            └── VendingMachines/            # KPIs y charts del módulo
                └── VendingMachinesOverviewStats.php
database/
├── factories/
│   └── VendingMachineFactory.php
└── migrations/
    └── xxxx_xx_xx_create_vending_machines_table.php
tests/
└── Feature/
    └── Filament/
        └── VendingMachines/
            └── ManageVendingMachinesTest.php   # o un archivo por flujo crítico
```

**Reglas:**

- Todo lo específico del módulo bajo `Filament/Admin/Resources/{Modulo}/`, y widgets relacionados bajo `Filament/Admin/Widgets/{Modulo}/`.
- Los servicios compartidos entre módulos pueden vivir en `app/Services/Shared/` (solo si aparece duplicación real).

## 3. Modelo Eloquent

- Clase **`final`**.
- `declare(strict_types=1);` al inicio del archivo.
- Atributos: `#[Fillable(...)]` o `$guarded` según ya use el proyecto; mantener **una sola** estrategia en todo el código.
- Relaciones con **tipo de retorno** explícito (`BelongsTo`, `HasMany`, etc.).
- `casts()` para fechas, enums, JSON y valores sensibles (`encrypted` si aplica).
- **Sin** lógica de negocio pesada: delegar en servicios.

## 4. Policy y autorización

- Una policy por modelo expuesto en Filament (crear, ver, actualizar, eliminar, `restore`, `forceDelete` si existen).
- Registrar la policy si no hay descubrimiento automático (convención Laravel).
- En el recurso Filament, usar `authorize()` / métodos de policy alineados con las acciones de la tabla y del formulario.

## 5. Servicios

- Clases **`final`**.
- Contienen reglas de negocio, integraciones, orquestación entre modelos.
- Los recursos Filament deben permanecer **delgados**: validación de UI, formularios y tablas; la mutación compleja va al servicio (inyección por constructor en el recurso o acciones).

## 6. Filament (recurso)

- **Grupo de navegación**: usar el mismo texto categoría para todos los recursos del mismo área funcional (ej. “Machines”, “Products”, “System”), alineado al menú lateral del legacy o al idioma del cliente.
- **Icono y orden**: `->navigationIcon()` y `->navigationSort()` para mantener el menú estable entre módulos.
- **CRUD en modal**: preferir recurso **simple** (`make:filament-resource ... --simple`). Si hace falta página completa por complejidad, documentar la excepción en el PR/commit.
- **Tabla**: columnas con el mismo criterio de nombres que el dominio; filtros reutilizables en traits solo si hay 3+ recursos iguales.
- **Formulario**: secciones (`Section`) alineadas con la captura del sistema legacy cuando exista referencia visual.
- **RelationManagers**: solo para relaciones que el usuario debe gestionar desde la ficha del padre.
- **Widgets del módulo**: `php artisan make:filament-widget NombreWidget --stats-overview` (u otros tipos); fijar `protected static ?int $sort` para el orden en el dashboard o en una página dedicada del módulo.

## 7. Base de datos

- Migraciones con nombres descriptivos: `create_vending_machines_table`, `add_serial_to_vending_machines_table`.
- Índices en columnas de filtrado, búsqueda y claves foráneas usadas en listados.
- **Soft deletes** solo si el negocio lo requiere (histórico, anulaciones recuperables).

## 8. Datos de prueba y tests

- **Factory** por modelo principal del módulo.
- **Tests de feature** para: listar, crear, validación fallida y (si aplica) policy denegada. Colocarlos bajo `tests/Feature/Filament/{Modulo}/`.
- Tras cambios en un módulo, ejecutar como mínimo los tests de ese módulo (`php artisan test --compact tests/Feature/Filament/VendingMachines`).

## 9. Checklist al crear un módulo nuevo

1. [ ] Migración(es) y modelo `final` con relaciones y `casts`.
2. [ ] Factory (y seeder solo si aporta demos o datos base).
3. [ ] Policy y comprobación en Filament.
4. [ ] Servicio(s) si hay lógica que no sea CRUD trivial.
5. [ ] `*Resource` en `Filament/Admin/Resources/{Modulo}/` (**`--simple`** si el CRUD es por modales) con etiquetas en inglés (panel admin US).
6. [ ] Widgets del módulo en `Filament/Admin/Widgets/{Modulo}/` si la captura incluye KPIs o gráficos.
7. [ ] Tests de feature mínimos.
8. [ ] `vendor/bin/pint --dirty` en archivos PHP tocados.

## 10. Orden sugerido al implementar desde una captura

1. Extraer **entidades** y **relaciones** de la pantalla.
2. Diseñar **tabla(s)** y migrar.
3. Modelo + factory.
4. Policy.
5. Recurso Filament (preferir **`--simple`** y modales) alineado a la captura.
6. Servicio y extracción de lógica si el formulario crece demasiado.
7. Tests y pint.

---

*Este estándar es el contrato del equipo: los módulos nuevos deben seguirlo para que la ubicación de archivos, nombres y responsabilidades sean predecibles.*
