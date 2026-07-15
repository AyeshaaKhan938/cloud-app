# VMFS Cloud — Gap Analysis & Implementation Progress

Auditoría realizada el 2026-05-12 comparando el Manual VMFS v1.0 contra el código actual.

---

## Leyenda
- ✅ Completado
- 🔄 En progreso
- ❌ Pendiente
- ⚠️ Parcial / necesita revisión

---

## CRÍTICO — Funcionalidad core del manual ausente

### Sales → Order List
- ✅ Resource `OrderResource` — tabla completa con filtros (máquina, estado, método de pago, rango de fechas), vista de detalle en modal, read-only con edición de notas y estado
- ✅ Sales → Refund Records — página con tabla filtrada a `status=refunded`, filtros por máquina y rango de fechas
- ✅ Sales → Recharge Record — nav item en grupo Sales apuntando al mismo `RechargeRecordResource` (que también permanece en Wallet)

### Dashboard
- ✅ Métrica: Cumulative sales this month (`DashboardBusinessStats`)
- ✅ Métrica: Cumulative sales this year (`DashboardBusinessStats`)
- ✅ Widget: User Sales Rank (`UserSalesRankTable` — top 10 por revenue, join orders→machines→users)
- ✅ Widget: New device monthly trend (`NewDeviceMonthlyTrendChart` — line chart últimos 6 meses)

### System → Notification Configuration
- ✅ Account email notification
- ✅ Inventory shortage notification
- ✅ Equipment offline notification
- ✅ Slot failure notification
- ✅ Network anomaly alert
- ✅ Campo de email destino de notificaciones
- ✅ Migración `notification_settings` + modelo `NotificationSetting`

### Theme Management (Sección 10 del manual — completamente ausente)
- ❌ Modelo + migración para temas
- ❌ Selección de orientación (Portrait / Landscape)
- ❌ Plantillas (12+ layouts)
- ❌ Nombre del tema
- ❌ Personalización de colores (tema, fondo, fuente, tag background)
- ❌ Personalización de botones (Add to cart, Buy, Delete, Cart)
- ❌ Animaciones (Slider)
- ❌ Iconos (Call, Bus)
- ❌ Página de pago customizable
- ❌ Homepage / welcome screen de la máquina

---

## MEDIO — Módulos parcialmente implementados

### Products
- ✅ Resource para gestionar **Product Types** (`ProductTypeResource` — sort 13 en Products)
- ✅ Resource para gestionar **Product Tags** (`ProductTagResource` — sort 14 en Products)
- ❌ Library (galería/biblioteca de imágenes) — placeholder en navegación

### Machines
- ✅ Estado **Online / Offline** real — campo `last_seen_at` en BD. Los controllers `MachineSlotController` y `AdvertisementController` actualizan el timestamp en cada llamada del kiosko. Badge en tabla: Online (verde) / Offline (gris) con `diffForHumans()` como descripción. Umbral: 15 minutos.
- ✅ **Inventario color-coded** en lista de máquinas — badge con icono: Stocked (verde), Low stock (amarillo), Out of stock (rojo), No slots (gris). Sin N+1 (eager load de slots).

### Coupons
- ✅ Asignación de cupones a **grupos de máquinas** — tabla pivot `coupon_machine_group`, relación `BelongsToMany`, `CheckboxList` en form con "Leave empty = all groups", badge column en tabla.

### Advertising
- ✅ **Posición/slot** — se gestiona correctamente a nivel de grupo (tabs Screensaver/Top/External en `AdvertisementGroupResource`), no en el anuncio individual. Arquitectura correcta.
- ✅ **Límite de tamaño** corregido a 100 MB (imagen y video) — el código tenía 3 MB para imágenes. Helper text ya documenta las dimensiones recomendadas.

---

## BAJO — Reports y Applications opcionales

### Reports
- ✅ Device Income — tabla con revenue por máquina, filtro por rango de fechas
- ✅ Product Income — tabla con revenue por producto, filtro por rango de fechas
- ✅ User Income — tabla con revenue por usuario (join orders→machines→users), filtro por fechas
- ✅ Date Income — tabla con revenue por día, filtros por rango de fechas y máquina
- ✅ Statistics — embeds DashboardBusinessStats + UserSalesRankTable
- ✅ Data Dashboard — embeds RevenueTrendChart, SalesMixChart, OrdersByDayChart, NewDeviceMonthlyTrendChart, RecentDemoOrdersTable

### Applications (todos placeholder excepto Advertising y Method of Payment)
- ❌ Theme Management (ya listado en Crítico)
- ❌ Merging of cargo lanes
- ❌ Employee IC card
- ❌ IC Card Swipe
- ❌ BankID Discern
- ❌ Machine Temperature
- ❌ Bouncer Age Identification
- ❌ Custom Age Recognition

---

## ✅ YA IMPLEMENTADO (referencia)

### Login / Acceso
- ✅ Pantalla de login (Filament built-in)
- ✅ Remember me
- ✅ Forgot your password

### Dashboard
- ✅ Trade Order Sales Daily Trend (`RevenueTrendChart`)
- ✅ Cumulative sales today (`DashboardBusinessStats`)
- ✅ Daily trend of trade order volume (`OrdersByDayChart`)
- ✅ Active Machines stat
- ✅ Active Alarms stat
- ✅ Fill Alerts (slots vacíos y bajo stock)

### Machines
- ✅ Machine List (`MachineResource`)
- ✅ Editar máquina (nombre, grupo, finance group, scenario, hotline, enable)
- ✅ Machine Groups (`MachineGroupResource`)
- ✅ Finance Groups (`FinanceGroupResource`)
- ✅ Machine Label Groups (`MachineLabelGroupResource`)
- ✅ Machine Alarms (`MachineAlarmResource`)
- ✅ Machine Map (`MachineMap` page)
- ✅ Manage Slots (sub-página de máquina)

### Products
- ✅ Crear/editar producto con imagen, precio, moneda, descripción
- ✅ Archivo 3D (.glb, .fbx)
- ✅ Specifications / Categories (`SpecificationResource`)
- ✅ Product Lotteries (`ProductLotteryResource`) con códigos y premios

### Coupons
- ✅ Crear cupón (nombre, monto mínimo, tipo fixed/percentage, validez, cantidad)
- ✅ Generación automática de códigos
- ✅ Vista de códigos generados con QR

### Advertising
- ✅ Crear anuncio (tipo, imagen/video, título, fechas)
- ✅ Advertisement Groups con tabs por slot (Screensaver, Top, External)
- ✅ Advertisement Tags

### Users
- ✅ Gestión de usuarios (`UserResource`)
- ✅ Roles (SuperAdmin, Admin, Agency, Operator, Customer)

### Brand
- ✅ Brand Settings (logos, imágenes, footer HTML)

### Wallet
- ✅ Recharge Wallet
- ✅ Recharge Records
- ✅ Collection Account Config (payment gateways)
- ✅ Renewal Center (equipos y historial)

### System Maintenance
- ✅ Work Orders
- ✅ My Work Orders
- ✅ Push Records
- ✅ Information Storage Records

### API (kiosko Flutter)
- ✅ `GET machines/{machineNo}/slots`
- ✅ `GET machines/{machineNo}/advertisements`
- ✅ `POST dispense`
- ✅ `POST lottery-codes/lookup`
- ✅ `POST product-lottery-draw/{token}`
- ✅ Admin API: dashboard, slots, orders, products (controllers creados, pendiente commit)
