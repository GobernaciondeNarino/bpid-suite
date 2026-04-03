#Banco de proyectos de Inversion Publica - BPID
API — Consulta Contratos con Ejecución Contractual BPID 

## Endpoint

```
GET https://bpid.narino.gov.co/bpid/publico/consulta_contratos_con_ejecucion_contractual.php
```

## Autenticación

Se requiere una API Key enviada como encabezado HTTP:

| Header  | Valor                                         |
|---------|-----------------------------------------------|
| `apikey` | `P4zLX3O5ve3rdYobBTd1pzlO3L001mSUrJ9Mtc49HbgmE` |

## Ejemplo de solicitud

### cURL

```bash
curl -X GET \
  'https://bpid.narino.gov.co/bpid/publico/consulta_contratos_con_ejecucion_contractual.php' \
  -H 'apikey: P4zLX3O5ve3rdYobBTd1pzlO3L001mSUrJ9Mtc49HbgmE'
```

## Respuesta

### Estructura general

```json
{
  "total": 472,
  "contratos": [ ... ]
}
```

### Descripción de campos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `total` | `integer` | Número total de contratos en la respuesta |
| `contratos` | `array` | Arreglo de contratos con ejecución dentro de BPID; cada objeto representa un contrato |
| `dependencia` | `string` | Secretaría o dependencia responsable en BPID |
| `numeroProyecto` | `string` | Código BPIN del proyecto en BPID |
| `nombreProyecto` | `string` | Nombre del proyecto en BPID |
| `entidadEjecutora` | `string` | Entidad ejecutora del proyecto en BPID |
| `odss` | `array` | Arreglo de ODS relacionados a metas seleccionadas en la ejecución dentro de BPID |
| `numero` | `string` | Código único del contrato obtenido de SYSMAN o DATOS ABIERTOS |
| `objeto` | `string` | Objeto contractual obtenido de SYSMAN o DATOS ABIERTOS |
| `descripcion` | `string` | Descripción de la ejecución dentro de BPID |
| `valor` | `string` | Valor del contrato obtenido de SYSMAN o DATOS ABIERTOS |
| `avanceFisico` | `string` | Porcentaje de avance físico de la ejecución dentro de BPID |
| `esOps` | `string` | Indica si el contrato es OPS (`"Si"` / `"No"`) |
| `municipios` | `array` | Arreglo de municipios seleccionados en la ejecución dentro de BPID |
| `imagenes` | `array` | URLs de imágenes de la ejecución dentro de BPID |

### Ejemplo de objeto contrato

```json
{
  "dependencia": "Dirección Administrativa de Cultura",
  "numeroProyecto": "2024003520088",
  "nombreProyecto": "Fortalecimiento del sector artístico y cultural del Departamento de Nariño",
  "entidadEjecutora": "GOBERNACIÓN DE NARIÑO",
  "odss": [
    "10. Reducción de las desigualdades",
    "16. Paz, Justicia e Instituciones sólidas",
    "8. Trabajo decente y crecimiento económico"
  ],
  "numero": "-",
  "objeto": "RESOLUCIÓN No. 037 22 DE SEPTIEMBRE DE 2025 \"Por medio de la cual se acoge la recomendación de los jurados designados para seleccionar los ganadores en el marco de la Convocatoria Pública del Programa Departamental de Estímulos Nariño 2025 Fase 2, Mundos CultuDiversos\".",
  "descripcion": "Estímulos fase II entregados",
  "valor": "4000000",
  "avanceFisico": "100",
  "esOps": "Si",
  "municipios": [],
  "imagenes": []
}
```

## Notas

- Los campos `valor` y `avanceFisico` se devuelven como cadenas de texto (`string`), aunque representan valores numéricos.
- El campo `numero` puede contener `"-"` cuando el contrato no tiene un código asociado en SYSMAN o DATOS ABIERTOS.
- Los campos `municipios` e `imagenes` pueden ser arreglos vacíos (`[]`).
- La fuente de los campos `numero`, `objeto` y `valor` puede ser **SYSMAN** o **DATOS ABIERTOS** según el origen del registro.
