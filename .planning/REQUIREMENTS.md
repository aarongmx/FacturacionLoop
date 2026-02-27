# Requirements: FacturacionLoop

**Defined:** 2026-02-27
**Core Value:** El usuario puede crear una factura CFDI 4.0 en Filament, timbrarla con Finkok, y obtener el XML timbrado con UUID válido ante el SAT.

## v1 Requirements

Requirements for initial release. Each maps to roadmap phases.

### Catálogos SAT

- [ ] **CAT-01**: Sistema tiene catálogo c_RegimenFiscal con ~20 regímenes fiscales del SAT
- [ ] **CAT-02**: Sistema tiene catálogo c_UsoCFDI con ~30 usos de CFDI válidos
- [ ] **CAT-03**: Sistema tiene catálogo c_FormaPago con ~30 formas de pago del SAT
- [ ] **CAT-04**: Sistema tiene catálogo c_MetodoPago con PUE y PPD
- [ ] **CAT-05**: Sistema tiene catálogo c_TipoDeComprobante con los 6 tipos (I, E, T, N, P, nomina)
- [ ] **CAT-06**: Sistema tiene catálogo c_ClaveProdServ con ~53,000 claves de producto/servicio
- [ ] **CAT-07**: Sistema tiene catálogo c_ClaveUnidad con ~2,000 unidades SAT
- [ ] **CAT-08**: Sistema tiene catálogo c_Impuesto con IVA, ISR e IEPS
- [ ] **CAT-09**: Sistema tiene catálogo c_TipoFactor con Tasa, Cuota y Exento
- [ ] **CAT-10**: Sistema tiene catálogo c_TasaOCuota con las tasas de IVA válidas
- [ ] **CAT-11**: Sistema tiene catálogo c_ObjetoImp con los 3 valores (01, 02, 03)
- [ ] **CAT-12**: Sistema tiene catálogo c_TipoRelacion con ~10 tipos de relación entre CFDIs
- [ ] **CAT-13**: Sistema tiene catálogo c_ClaveProdServCP con ~17,000 claves para Carta Porte
- [ ] **CAT-14**: Sistema tiene catálogo c_TipoPermiso con ~20 permisos SCT
- [ ] **CAT-15**: Sistema tiene catálogo c_SubTipoRem con ~20 subtipos de remolque
- [ ] **CAT-16**: Sistema tiene catálogo c_TipoLicencia con ~10 tipos de licencia
- [ ] **CAT-17**: Sistema tiene catálogo c_TipoFigura con ~10 tipos de figura de transporte
- [ ] **CAT-18**: Sistema tiene catálogo c_Municipio con ~2,500 municipios
- [ ] **CAT-19**: Sistema tiene catálogo c_ClavePedimento con ~50 claves de pedimento
- [ ] **CAT-20**: Sistema tiene catálogo c_MotivoTraslado con ~5 motivos

### CSD (Certificado de Sello Digital)

- [ ] **CSD-01**: Usuario puede subir archivo .cer desde Filament
- [ ] **CSD-02**: Usuario puede subir archivo .key desde Filament
- [ ] **CSD-03**: Sistema almacena contraseña del .key encriptada con Laravel Crypt
- [ ] **CSD-04**: Sistema almacena archivo .key encriptado (nunca en storage público)
- [ ] **CSD-05**: Sistema extrae NoCertificado y fechas de vigencia del .cer al subir
- [ ] **CSD-06**: Sistema valida que el CSD no esté expirado antes de cada firmado
- [ ] **CSD-07**: Sistema muestra alerta cuando el CSD está próximo a expirar (3 meses)

### Emisor y Receptor

- [ ] **ENT-01**: Usuario puede configurar datos del emisor (RFC, nombre, régimen fiscal, domicilio fiscal)
- [ ] **ENT-02**: Usuario puede crear y gestionar catálogo de receptores (clientes)
- [ ] **ENT-03**: Receptor almacena RFC, nombre fiscal, domicilio fiscal CP, régimen fiscal y uso CFDI predeterminado
- [ ] **ENT-04**: Sistema valida formato de RFC al registrar receptor (12 chars persona moral, 13 persona física)
- [ ] **ENT-05**: Usuario puede buscar y seleccionar receptor existente al crear factura

### Productos y Servicios

- [ ] **PROD-01**: Usuario puede crear catálogo de productos/servicios con ClaveProdServ, ClaveUnidad, descripción y precio unitario
- [ ] **PROD-02**: Usuario puede buscar y seleccionar producto existente al agregar concepto a factura
- [ ] **PROD-03**: Producto almacena configuración de impuestos (IVA, ISR, IEPS) y ObjetoImp

### CFDI Base

- [ ] **CFDI-01**: Usuario puede crear CFDI tipo Ingreso desde Filament
- [ ] **CFDI-02**: Usuario puede crear CFDI tipo Egreso (nota de crédito) desde Filament
- [ ] **CFDI-03**: Factura incluye Serie y Folio con secuencia automática
- [ ] **CFDI-04**: Usuario puede configurar múltiples series de facturación (A, B, etc.)
- [ ] **CFDI-05**: Factura incluye FormaPago, MetodoPago, Moneda, TipoCambio y LugarExpedicion
- [ ] **CFDI-06**: Usuario puede agregar múltiples conceptos (líneas) con ClaveProdServ, cantidad, ClaveUnidad, descripción, valor unitario e impuestos
- [ ] **CFDI-07**: Sistema calcula automáticamente SubTotal, Descuento, impuestos trasladados, impuestos retenidos y Total
- [ ] **CFDI-08**: Sistema agrega nodo CfdiRelacionados cuando aplica (notas de crédito, sustitución)
- [ ] **CFDI-09**: Usuario puede guardar factura como borrador antes de timbrar
- [ ] **CFDI-10**: Sistema genera XML CFDI 4.0 válido usando eclipxe/cfdiutils
- [ ] **CFDI-11**: Sistema sella XML con el CSD del emisor
- [ ] **CFDI-12**: Sistema valida estructura XML contra XSD del SAT antes de enviar al PAC

### Timbrado y PAC

- [ ] **PAC-01**: Sistema timbra CFDI con Finkok vía SOAP API
- [ ] **PAC-02**: Sistema almacena UUID, NoCertificadoSAT, FechaTimbrado y SelloSAT del timbre
- [ ] **PAC-03**: Timbrado se ejecuta de forma asíncrona (queued job) para no bloquear la UI
- [ ] **PAC-04**: Sistema implementa interfaz PacServiceInterface para permitir agregar otros PACs
- [ ] **PAC-05**: Sistema maneja error Finkok 307 (ya timbrado) recuperando el XML exitoso del error

### Post-Timbrado

- [ ] **POST-01**: Sistema almacena XML timbrado en filesystem/storage
- [ ] **POST-02**: Sistema genera PDF de la factura con datos fiscales y código QR
- [ ] **POST-03**: Sistema envía email al receptor con XML y PDF adjuntos
- [ ] **POST-04**: Usuario puede descargar XML y PDF desde Filament
- [ ] **POST-05**: Usuario puede descargar masivamente XMLs de un período

### Cancelación

- [ ] **CANC-01**: Usuario puede cancelar CFDI timbrado ante el SAT vía Finkok
- [ ] **CANC-02**: Cancelación requiere seleccionar motivo (01, 02, 03, 04)
- [ ] **CANC-03**: Cancelación con motivo 01 requiere UUID del CFDI sustituto (FolioSustitucion)
- [ ] **CANC-04**: Sistema firma solicitud de cancelación con CSD
- [ ] **CANC-05**: Sistema registra y muestra estado de cancelación (aceptada, en proceso, rechazada)

### Complemento Pagos 2.0

- [ ] **PAG-01**: Usuario puede crear CFDI de tipo Pago para facturas PPD existentes
- [ ] **PAG-02**: Pago incluye FechaPago, FormaDePagoP, MonedaP, TipoCambioP y Monto
- [ ] **PAG-03**: Pago referencia documentos relacionados (UUID de factura PPD, NumParcialidad, ImpSaldoAnt, ImpPagado, ImpSaldoInsoluto)
- [ ] **PAG-04**: Sistema calcula impuestos proporcionales del pago (TrasladosP, RetencionesP) usando bcmath
- [ ] **PAG-05**: Sistema genera nodo Totales con agregados de impuestos de todos los pagos
- [ ] **PAG-06**: Un CFDI de pago puede incluir múltiples pagos y cada pago múltiples documentos relacionados

### Complemento Carta Porte 3.1

- [ ] **CRP-01**: Usuario puede crear CFDI con complemento Carta Porte 3.1 desde Filament
- [ ] **CRP-02**: Carta Porte incluye nodo Ubicaciones con origen y destino (RFC, dirección completa, fecha salida/llegada, distancia recorrida)
- [ ] **CRP-03**: Carta Porte incluye nodo Mercancias con ClaveProdServCP, descripción, cantidad, ClaveUnidad, PesoEnKg y material peligroso
- [ ] **CRP-04**: Carta Porte incluye nodo Autotransporte con PermSCT, NumPermisoSCT, placa del vehículo, año y seguros
- [ ] **CRP-05**: Carta Porte incluye nodo FiguraTransporte con operadores (RFC, tipo licencia, número licencia)
- [ ] **CRP-06**: Carta Porte soporta transporte internacional (TranspInternac, PaisOrigenDestino, ViaEntradaSalida)
- [ ] **CRP-07**: Sistema calcula PesoBrutoTotal y NumTotalMercancias automáticamente

### Complemento Comercio Exterior 2.0

- [ ] **CEX-01**: Usuario puede crear CFDI con complemento Comercio Exterior 2.0 desde Filament
- [ ] **CEX-02**: Comercio Exterior incluye TipoOperacion, ClaveDePedimento, Incoterm, TipoCambioUSD y TotalUSD
- [ ] **CEX-03**: Comercio Exterior incluye datos del emisor y receptor con direcciones completas
- [ ] **CEX-04**: Comercio Exterior incluye mercancías con FraccionArancelaria, CantidadAduana, UnidadAduana, ValorUnitarioAduana y ValorDolares
- [ ] **CEX-05**: Sistema usa catálogos existentes (TariffClassification, CustomUnit, Country, Incoterm, State)

### Listado y Consulta

- [ ] **LIST-01**: Usuario puede ver listado de todas las facturas en Filament con estado (borrador, timbrada, cancelada)
- [ ] **LIST-02**: Usuario puede filtrar facturas por fecha, tipo, serie, receptor y estado
- [ ] **LIST-03**: Usuario puede buscar facturas por UUID, folio o RFC del receptor

## v2 Requirements

Deferred to future release. Tracked but not in current roadmap.

### Validación y Automatización

- **VAL-01**: Sistema valida RFC del receptor contra lista LCO del SAT vía Finkok antes de timbrar
- **VAL-02**: Sistema consulta automáticamente tipo de cambio Banxico del día como sugerencia
- **VAL-03**: Polling automático de estado de cancelación ante el SAT

### Facturación Adicional

- **FAC-01**: Usuario puede crear Factura Global (público en general) con periodicidad
- **FAC-02**: Soporte para CFDI tipo Traslado standalone
- **FAC-03**: Personalización de template PDF (logo, colores de empresa)

### Integración

- **INT-01**: API REST para crear facturas desde sistemas externos
- **INT-02**: Soporte para addendas de comercio (Walmart, FEMSA, etc.)

## Out of Scope

| Feature | Reason |
|---------|--------|
| Multi-tenancy / múltiples empresas | Sistema para empresa única; rediseño completo si se necesita |
| CFDI Nómina | Dominio completamente diferente (IMSS, SAT nómina); fuera del alcance |
| Contabilidad electrónica (COE) | Requiere módulo contable completo; fuera del alcance |
| DIOT | Declaración informativa; requiere agregación contable |
| OAuth / login social | Autenticación estándar suficiente |
| App móvil | Web-first con Filament |
| Catálogo c_ColoniaCP (145K filas) | Usar campo de texto libre para colonia; validar solo Estado/Municipio |
| Integración ERP | No requerida en v1 |
| Regímenes fiscales especiales (REPECOS, RIF) | Solo régimen estándar 601/626; agregar en v2 si se necesita |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| — | — | — |

**Coverage:**
- v1 requirements: 62 total
- Mapped to phases: 0
- Unmapped: 62 ⚠️

---
*Requirements defined: 2026-02-27*
*Last updated: 2026-02-27 after initial definition*
