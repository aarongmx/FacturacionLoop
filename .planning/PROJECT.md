# FacturacionLoop — Sistema de Facturación CFDI 4.0

## What This Is

Sistema de facturación electrónica CFDI 4.0 para una empresa única, construido sobre Laravel 12 y Filament v5. Permite crear, timbrar, enviar y cancelar comprobantes fiscales digitales, incluyendo complementos de Pagos, Carta Porte y Comercio Exterior. Se conecta con Finkok como PAC principal, con arquitectura preparada para soportar múltiples PACs.

## Core Value

El usuario puede crear una factura CFDI 4.0 en Filament, timbrarla con Finkok, y obtener el XML timbrado con UUID válido ante el SAT.

## Requirements

### Validated

<!-- Shipped and confirmed valuable. -->

- ✓ Catálogo de monedas (Currency) — existing
- ✓ Catálogo de países (Country) — existing
- ✓ Catálogo de unidades aduaneras (CustomUnit) — existing
- ✓ Catálogo de fracciones arancelarias (TariffClassification) — existing
- ✓ Catálogo de estados (State) — existing
- ✓ Catálogo de Incoterms — existing
- ✓ Autenticación de usuarios — existing
- ✓ Panel administrativo Filament en `/admin` — existing

### Active

- [ ] Gestión de CSD (subir .cer, .key y contraseña desde Filament)
- [ ] Catálogos SAT faltantes (régimen fiscal, uso CFDI, forma de pago, método de pago, tipo de comprobante, clave producto/servicio, unidad SAT)
- [ ] Gestión de emisor y receptor (datos fiscales)
- [ ] Creación de CFDI 4.0 base (ingreso, egreso, traslado)
- [ ] Generación de XML con eclipxe/cfdiutils
- [ ] Sellado del XML con CSD del emisor
- [ ] Timbrado con Finkok (integración API)
- [ ] Arquitectura multi-PAC (interfaz/contrato para conectar otros PACs)
- [ ] Generación de PDF de la factura
- [ ] Envío de factura por email (XML + PDF)
- [ ] Cancelación de CFDI ante el SAT vía PAC
- [ ] Complemento de Pagos (Recepción de Pagos 2.0)
- [ ] Complemento de Carta Porte 3.1
- [ ] Complemento de Comercio Exterior 2.0
- [ ] Almacenamiento y consulta de XMLs timbrados
- [ ] Listado y búsqueda de facturas en Filament

### Out of Scope

- Multi-tenancy / SaaS — es para empresa única
- OAuth / login social — autenticación estándar es suficiente
- App móvil — web-first con Filament
- Contabilidad electrónica — solo facturación
- Nómina digital — fuera del alcance de este sistema
- Integración con ERP externo — no requerido en v1

## Context

- Proyecto Laravel 12 existente con Filament v5, PostgreSQL 18, PHP 8.5
- Existen catálogos base del SAT (monedas, países, unidades aduaneras, fracciones arancelarias, estados, incoterms) ya migrados y con seeders
- Se usará `eclipxe/cfdiutils` como librería principal para generación y validación de XML CFDI
- Finkok como PAC para timbrado en v1, pero la arquitectura debe permitir agregar otros PACs
- Los CSD se suben desde el panel Filament (archivos .cer, .key + contraseña)
- Post-timbrado: almacenar XML, generar PDF, enviar por correo electrónico

## Constraints

- **SAT**: Cumplir con especificaciones técnicas del Anexo 20 para CFDI 4.0
- **PAC**: API de Finkok para timbrado y cancelación
- **Librería**: eclipxe/cfdiutils para generación de XML
- **Stack**: Laravel 12 + Filament v5 + PostgreSQL 18 (ya establecido)
- **Seguridad**: Los CSD (.key, contraseña) deben almacenarse de forma segura (encriptados)

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| eclipxe/cfdiutils para XML | Librería PHP más madura y mantenida para CFDI | — Pending |
| Finkok como PAC v1 | PAC seleccionado por el usuario para primera versión | — Pending |
| Arquitectura multi-PAC | Permitir cambiar/agregar PACs sin reescribir lógica de facturación | — Pending |
| Todos los complementos en v1 | Pagos, Carta Porte y Comercio Exterior desde el inicio | — Pending |
| CSD subidos desde Filament | Usuarios gestionan sus certificados directamente en el panel | — Pending |

---
*Last updated: 2026-02-27 after initialization*
