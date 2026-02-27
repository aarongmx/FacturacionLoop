# Modelo Entidad-Relación — CFDI 4.0 + Comercio Exterior 2.0

Diagrama de base de datos normalizado (3NF) basado en los esquemas XSD oficiales del SAT:
`cfdiv40.xsd` y `ComercioExterior20.xsd`.

```mermaid
erDiagram

    %% =============================================
    %% CATALOGOS SAT — Tablas de referencia
    %% =============================================

    cat_regimen_fiscal {
        string clave PK "Ej: 601, 603, 612"
        string descripcion
    }

    cat_uso_cfdi {
        string clave PK "Ej: G01, G03, I01"
        string descripcion
    }

    cat_forma_pago {
        string clave PK "Ej: 01, 02, 03"
        string descripcion
    }

    cat_metodo_pago {
        string clave PK "PUE o PPD"
        string descripcion
    }

    cat_moneda {
        string clave PK "ISO 4217: MXN, USD"
        string descripcion
        int decimales
    }

    cat_tipo_comprobante {
        string clave PK "I, E, T, N, P"
        string descripcion
    }

    cat_clave_prod_serv {
        string clave PK "Ej: 01010101"
        string descripcion
    }

    cat_clave_unidad {
        string clave PK "Ej: E48, H87, ACT"
        string descripcion
    }

    cat_impuesto {
        string clave PK "001=ISR 002=IVA 003=IEPS"
        string descripcion
    }

    cat_tipo_factor {
        string clave PK "Tasa, Cuota, Exento"
        string descripcion
    }

    cat_pais {
        string clave PK "ISO 3166-1 alfa-3: MEX, USA"
        string descripcion
    }

    cat_estado {
        string clave PK "ISO 3166-2: AGU, BCN"
        string descripcion
        string pais_clave FK
    }

    cat_codigo_postal {
        string clave PK "Ej: 06600"
        string estado_clave FK
        string municipio_clave FK
        string localidad_clave FK
    }

    cat_colonia {
        bigint id PK "Surrogate por clave compuesta"
        string clave "Clave SAT de colonia"
        string codigo_postal_clave FK
        string descripcion
    }

    cat_localidad {
        string clave PK "Clave SAT compuesta"
        string estado_clave FK
        string descripcion
    }

    cat_municipio {
        string clave PK "Clave SAT compuesta"
        string estado_clave FK
        string descripcion
    }

    cat_exportacion {
        string clave PK "01, 02, 03, 04"
        string descripcion
    }

    cat_tipo_relacion {
        string clave PK "Ej: 01, 02, 03"
        string descripcion
    }

    cat_objeto_imp {
        string clave PK "01, 02, 03, 04"
        string descripcion
    }

    cat_periodicidad {
        string clave PK "01-05"
        string descripcion
    }

    cat_meses {
        string clave PK "01-18"
        string descripcion
    }

    cat_fraccion_arancelaria {
        string clave PK "Fraccion arancelaria"
        string descripcion
        string unidad_aduana_clave FK
    }

    cat_unidad_aduana {
        string clave PK "Unidad de medida en aduana"
        string descripcion
    }

    cat_incoterm {
        string clave PK "Ej: CFR, CIF, FOB"
        string descripcion
    }

    cat_motivo_traslado {
        string clave PK "Ej: 01-05"
        string descripcion
    }

    cat_clave_pedimento {
        string clave PK "Clave de pedimento"
        string descripcion
    }

    %% =============================================
    %% CORE — Emisor y Receptor maestros
    %% =============================================

    empresas {
        bigint id PK
        string rfc UK "RFC del emisor"
        string nombre "Razon social"
        string regimen_fiscal_clave FK "c_RegimenFiscal"
        string codigo_postal "Lugar de expedicion por defecto"
        string fac_atr_adquirente "Nullable, 10 digitos"
        timestamp created_at
        timestamp updated_at
    }

    clientes {
        bigint id PK
        bigint empresa_id FK "Pertenece a una empresa"
        string rfc "RFC del receptor"
        string nombre "Razon social"
        string domicilio_fiscal_receptor "CP 5 digitos"
        string residencia_fiscal_clave FK "Nullable, c_Pais"
        string num_reg_id_trib "Nullable, ID fiscal extranjero"
        string regimen_fiscal_clave FK "c_RegimenFiscal"
        timestamp created_at
        timestamp updated_at
    }

    %% =============================================
    %% CFDI — Comprobante raiz y nodos directos
    %% =============================================

    comprobantes {
        bigint id PK
        bigint empresa_id FK "Emisor"
        bigint cliente_id FK "Receptor"
        string version "Fijo 4.0"
        string serie "Nullable, max 25"
        string folio "Nullable, max 40"
        datetime fecha "AAAA-MM-DDThh:mm:ss"
        text sello "Sello digital Base64"
        string forma_pago_clave FK "Nullable, c_FormaPago"
        string no_certificado "20 digitos"
        text certificado "Certificado Base64"
        string condiciones_de_pago "Nullable, max 1000"
        decimal sub_total "Importe >= 0"
        decimal descuento "Nullable, >= 0"
        string moneda_clave FK "c_Moneda"
        decimal tipo_cambio "Nullable, 6 decimales"
        decimal total "Importe >= 0"
        string tipo_de_comprobante_clave FK "c_TipoDeComprobante"
        string exportacion_clave FK "c_Exportacion"
        string metodo_pago_clave FK "Nullable, c_MetodoPago"
        string lugar_expedicion_clave FK "c_CodigoPostal"
        string confirmacion "Nullable, 5 chars"
        string uso_cfdi_clave FK "c_UsoCFDI, por factura"
        decimal total_impuestos_trasladados "Nullable, resumen"
        decimal total_impuestos_retenidos "Nullable, resumen"
        timestamp created_at
        timestamp updated_at
    }

    informacion_global {
        bigint id PK
        bigint comprobante_id FK "UK, 1:1 con comprobante"
        string periodicidad_clave FK "c_Periodicidad"
        string meses_clave FK "c_Meses"
        smallint anio ">= 2019"
    }

    comprobante_relaciones {
        bigint id PK
        bigint comprobante_id FK "N por comprobante"
        string tipo_relacion_clave FK "c_TipoRelacion"
    }

    comprobante_relacion_cfdis {
        bigint id PK
        bigint comprobante_relacion_id FK
        string uuid UK "UUID 36 chars del CFDI relacionado"
    }

    %% =============================================
    %% CONCEPTOS — Lineas de detalle
    %% =============================================

    conceptos {
        bigint id PK
        bigint comprobante_id FK
        string clave_prod_serv_clave FK "c_ClaveProdServ"
        string no_identificacion "Nullable, max 100"
        decimal cantidad "6 decimales, > 0"
        string clave_unidad_clave FK "c_ClaveUnidad"
        string unidad "Nullable, texto libre max 20"
        string descripcion "max 1000"
        decimal valor_unitario "Importe >= 0"
        decimal importe "Importe >= 0"
        decimal descuento "Nullable, >= 0"
        string objeto_imp_clave FK "c_ObjetoImp"
        string tercero_rfc "Nullable, ACuentaTerceros"
        string tercero_nombre "Nullable"
        string tercero_regimen_fiscal_clave FK "Nullable, c_RegimenFiscal"
        string tercero_domicilio_fiscal "Nullable, CP 5 digitos"
    }

    concepto_impuestos {
        bigint id PK
        bigint concepto_id FK
        string tipo "traslado o retencion"
        decimal base "6 decimales, > 0"
        string impuesto_clave FK "c_Impuesto"
        string tipo_factor_clave FK "c_TipoFactor"
        decimal tasa_o_cuota "Nullable, 6 decimales"
        decimal importe "Nullable para traslados exentos"
    }

    concepto_informacion_aduanera {
        bigint id PK
        bigint concepto_id FK
        string numero_pedimento "21 chars, formato SAT"
    }

    concepto_cuentas_prediales {
        bigint id PK
        bigint concepto_id FK
        string numero "max 150, alfanumerico"
    }

    partes {
        bigint id PK
        bigint concepto_id FK
        string clave_prod_serv_clave FK "c_ClaveProdServ"
        string no_identificacion "Nullable, max 100"
        decimal cantidad "6 decimales, > 0"
        string unidad "Nullable, texto libre max 20"
        string descripcion "max 1000"
        decimal valor_unitario "Nullable"
        decimal importe "Nullable"
    }

    parte_informacion_aduanera {
        bigint id PK
        bigint parte_id FK
        string numero_pedimento "21 chars, formato SAT"
    }

    %% =============================================
    %% IMPUESTOS TOTALES — Nivel comprobante
    %% =============================================

    comprobante_traslados {
        bigint id PK
        bigint comprobante_id FK
        decimal base "Importe >= 0"
        string impuesto_clave FK "c_Impuesto"
        string tipo_factor_clave FK "c_TipoFactor"
        decimal tasa_o_cuota "Nullable, 6 decimales"
        decimal importe "Nullable"
    }

    comprobante_retenciones {
        bigint id PK
        bigint comprobante_id FK
        string impuesto_clave FK "c_Impuesto"
        decimal importe "Importe >= 0"
    }

    %% =============================================
    %% COMERCIO EXTERIOR 2.0 — Complemento
    %% =============================================

    comercio_exterior {
        bigint id PK
        bigint comprobante_id FK "UK, 1:1 con comprobante"
        string version "Fijo 2.0"
        string motivo_traslado_clave FK "Nullable, c_MotivoTraslado"
        string clave_de_pedimento_clave FK "c_ClavePedimento"
        int certificado_origen "0 o 1"
        string num_certificado_origen "Nullable, 6-40 chars"
        string numero_exportador_confiable "Nullable, max 50"
        string incoterm_clave FK "Nullable, c_INCOTERM"
        string observaciones "Nullable, max 300"
        decimal tipo_cambio_usd "MXN por 1 USD"
        decimal total_usd "Importe total en USD"
    }

    ce_emisores {
        bigint id PK
        bigint comercio_exterior_id FK "UK, 1:1"
        string curp "Nullable, CURP persona fisica"
        string calle "max 100"
        string numero_exterior "Nullable"
        string numero_interior "Nullable"
        string colonia_clave FK "Nullable, c_Colonia"
        string localidad_clave FK "Nullable, c_Localidad"
        string referencia "Nullable"
        string municipio_clave FK "Nullable, c_Municipio"
        string estado_clave FK "c_Estado"
        string pais_clave FK "c_Pais, debe ser MEX"
        string codigo_postal_clave FK "c_CodigoPostal"
    }

    ce_propietarios {
        bigint id PK
        bigint comercio_exterior_id FK
        string num_reg_id_trib "ID fiscal, 6-40 chars"
        string residencia_fiscal_clave FK "c_Pais"
    }

    ce_receptores {
        bigint id PK
        bigint comercio_exterior_id FK "UK, 1:1"
        string num_reg_id_trib "Nullable, 6-40 chars"
        string calle "Nullable, domicilio texto libre"
        string numero_exterior "Nullable"
        string numero_interior "Nullable"
        string colonia "Nullable, texto libre max 120"
        string localidad "Nullable, texto libre max 120"
        string referencia "Nullable"
        string municipio "Nullable, texto libre max 120"
        string estado "Nullable, texto libre max 30"
        string pais_clave FK "Nullable, c_Pais"
        string codigo_postal "Nullable, texto libre max 12"
    }

    ce_destinatarios {
        bigint id PK
        bigint comercio_exterior_id FK
        string num_reg_id_trib "Nullable, 6-40 chars"
        string nombre "Nullable, max 300"
    }

    ce_destinatario_domicilios {
        bigint id PK
        bigint ce_destinatario_id FK
        string calle "max 100"
        string numero_exterior "Nullable"
        string numero_interior "Nullable"
        string colonia "Nullable, texto libre max 120"
        string localidad "Nullable, texto libre max 120"
        string referencia "Nullable"
        string municipio "Nullable, texto libre max 120"
        string estado "Texto libre, max 30"
        string pais_clave FK "c_Pais"
        string codigo_postal "Texto libre, max 12"
    }

    ce_mercancias {
        bigint id PK
        bigint comercio_exterior_id FK
        string no_identificacion "max 100"
        string fraccion_arancelaria_clave FK "Nullable, c_FraccionArancelaria"
        decimal cantidad_aduana "Nullable, 3 decimales"
        string unidad_aduana_clave FK "Nullable, c_UnidadAduana"
        decimal valor_unitario_aduana "Nullable, 6 decimales"
        decimal valor_dolares "USD, 4 decimales"
    }

    ce_descripciones_especificas {
        bigint id PK
        bigint ce_mercancia_id FK
        string marca "max 35"
        string modelo "Nullable, max 80"
        string sub_modelo "Nullable, max 50"
        string numero_serie "Nullable, max 40"
    }

    %% =============================================
    %% RELACIONES — Core
    %% =============================================

    empresas ||--o{ clientes : "tiene"
    empresas ||--o{ comprobantes : "emite"
    clientes ||--o{ comprobantes : "recibe"

    %% =============================================
    %% RELACIONES — CFDI nodos directos
    %% =============================================

    comprobantes ||--o| informacion_global : "tiene"
    comprobantes ||--o{ comprobante_relaciones : "tiene"
    comprobante_relaciones ||--|{ comprobante_relacion_cfdis : "contiene"

    %% =============================================
    %% RELACIONES — Conceptos
    %% =============================================

    comprobantes ||--|{ conceptos : "tiene"
    conceptos ||--o{ concepto_impuestos : "tiene"
    conceptos ||--o{ concepto_informacion_aduanera : "tiene"
    conceptos ||--o{ concepto_cuentas_prediales : "tiene"
    conceptos ||--o{ partes : "tiene"
    partes ||--o{ parte_informacion_aduanera : "tiene"

    %% =============================================
    %% RELACIONES — Impuestos totales comprobante
    %% =============================================

    comprobantes ||--o{ comprobante_traslados : "tiene"
    comprobantes ||--o{ comprobante_retenciones : "tiene"

    %% =============================================
    %% RELACIONES — Comercio Exterior
    %% =============================================

    comprobantes ||--o| comercio_exterior : "tiene"
    comercio_exterior ||--o| ce_emisores : "tiene"
    comercio_exterior ||--o{ ce_propietarios : "tiene"
    comercio_exterior ||--o| ce_receptores : "tiene"
    comercio_exterior ||--o{ ce_destinatarios : "tiene"
    ce_destinatarios ||--|{ ce_destinatario_domicilios : "tiene"
    comercio_exterior ||--|{ ce_mercancias : "tiene"
    ce_mercancias ||--o{ ce_descripciones_especificas : "tiene"

    %% =============================================
    %% RELACIONES — Catalogos a Core
    %% =============================================

    cat_regimen_fiscal ||--o{ empresas : "clasifica"
    cat_regimen_fiscal ||--o{ clientes : "clasifica"
    cat_pais ||--o{ clientes : "clasifica"

    %% =============================================
    %% RELACIONES — Catalogos a Comprobantes
    %% =============================================

    cat_forma_pago ||--o{ comprobantes : "clasifica"
    cat_moneda ||--o{ comprobantes : "clasifica"
    cat_tipo_comprobante ||--o{ comprobantes : "clasifica"
    cat_exportacion ||--o{ comprobantes : "clasifica"
    cat_metodo_pago ||--o{ comprobantes : "clasifica"
    cat_codigo_postal ||--o{ comprobantes : "clasifica"
    cat_uso_cfdi ||--o{ comprobantes : "clasifica"
    cat_periodicidad ||--o{ informacion_global : "clasifica"
    cat_meses ||--o{ informacion_global : "clasifica"
    cat_tipo_relacion ||--o{ comprobante_relaciones : "clasifica"

    %% =============================================
    %% RELACIONES — Catalogos a Conceptos
    %% =============================================

    cat_clave_prod_serv ||--o{ conceptos : "clasifica"
    cat_clave_unidad ||--o{ conceptos : "clasifica"
    cat_objeto_imp ||--o{ conceptos : "clasifica"
    cat_regimen_fiscal ||--o{ conceptos : "clasifica"
    cat_clave_prod_serv ||--o{ partes : "clasifica"

    %% =============================================
    %% RELACIONES — Catalogos a Impuestos
    %% =============================================

    cat_impuesto ||--o{ concepto_impuestos : "clasifica"
    cat_tipo_factor ||--o{ concepto_impuestos : "clasifica"
    cat_impuesto ||--o{ comprobante_traslados : "clasifica"
    cat_tipo_factor ||--o{ comprobante_traslados : "clasifica"
    cat_impuesto ||--o{ comprobante_retenciones : "clasifica"

    %% =============================================
    %% RELACIONES — Catalogos a Comercio Exterior
    %% =============================================

    cat_motivo_traslado ||--o{ comercio_exterior : "clasifica"
    cat_clave_pedimento ||--o{ comercio_exterior : "clasifica"
    cat_incoterm ||--o{ comercio_exterior : "clasifica"
    cat_fraccion_arancelaria ||--o{ ce_mercancias : "clasifica"
    cat_unidad_aduana ||--o{ ce_mercancias : "clasifica"

    %% =============================================
    %% RELACIONES — Catalogos a CE Emisor (domicilio con catalogos SAT)
    %% =============================================

    cat_colonia ||--o{ ce_emisores : "clasifica"
    cat_localidad ||--o{ ce_emisores : "clasifica"
    cat_municipio ||--o{ ce_emisores : "clasifica"
    cat_estado ||--o{ ce_emisores : "clasifica"
    cat_pais ||--o{ ce_emisores : "clasifica"
    cat_codigo_postal ||--o{ ce_emisores : "clasifica"

    %% =============================================
    %% RELACIONES — Catalogos a CE Propietarios, Receptores, Destinatarios
    %% (Domicilios receptor/destinatario usan texto libre segun XSD)
    %% =============================================

    cat_pais ||--o{ ce_propietarios : "clasifica"
    cat_pais ||--o{ ce_receptores : "clasifica"
    cat_pais ||--o{ ce_destinatario_domicilios : "clasifica"

    %% =============================================
    %% RELACIONES — Entre catalogos geograficos
    %% =============================================

    cat_pais ||--o{ cat_estado : "contiene"
    cat_estado ||--o{ cat_municipio : "contiene"
    cat_estado ||--o{ cat_localidad : "contiene"
    cat_estado ||--o{ cat_codigo_postal : "contiene"
    cat_codigo_postal ||--o{ cat_colonia : "contiene"
```

## Notas de Normalización

### 1NF — Eliminación de grupos repetitivos
- Conceptos, impuestos, relaciones CFDI, propietarios, destinatarios y mercancías en tablas separadas
- Descripciones específicas de mercancías en su propia tabla

### 2NF — Sin dependencias parciales
- Todos los atributos no-clave dependen de la PK completa
- `uso_cfdi` va en `comprobantes` (no en `clientes`) porque cambia por factura

### 3NF — Sin dependencias transitivas
- Todos los catálogos SAT en tablas de referencia separadas
- Domicilio del emisor CE usa FKs a catálogos (`c_Colonia`, `c_Estado`, etc.)
- Domicilios del receptor/destinatario CE usan texto libre (según XSD, para direcciones internacionales)

### ACuentaTerceros
- Embebido como columnas nullable en `conceptos` (relación 0..1) para evitar tabla adicional
- Campos: `tercero_rfc`, `tercero_nombre`, `tercero_regimen_fiscal_clave`, `tercero_domicilio_fiscal`

### Impuestos a dos niveles
- **Nivel concepto**: `concepto_impuestos` con discriminador `tipo` (traslado/retención) — estructura similar entre ambos
- **Nivel comprobante**: tablas separadas `comprobante_traslados` y `comprobante_retenciones` — estructura diferente (retenciones solo tienen impuesto + importe)

### Catálogos geográficos
- Jerarquía: `cat_pais` → `cat_estado` → `cat_municipio` / `cat_localidad` → `cat_codigo_postal` → `cat_colonia`
- `cat_colonia` usa surrogate PK por clave compuesta (clave + código postal)
