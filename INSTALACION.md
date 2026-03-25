# Guía de Instalación — BPID Suite

> **Gobernación de Nariño · Secretaría de TIC, Innovación y Gobierno Abierto**

---

## Requisitos previos

- WordPress 6.0 o superior
- PHP 8.1 o superior
- MySQL 5.7+ o MariaDB 10.3+
- Acceso de administrador al panel de WordPress
- API key del BPID (proporcionada por la Secretaría de TIC)

---

## Paso 1: Descargar el plugin

### Opción A: Desde GitHub (recomendado)

```bash
cd /ruta/a/wordpress/wp-content/plugins/
git clone https://github.com/GobernaciondeNarino/bpid-suite.git
```

### Opción B: Descarga manual

1. Descargar el archivo ZIP desde [GitHub Releases](https://github.com/GobernaciondeNarino/bpid-suite/releases).
2. Descomprimir el archivo.
3. Subir la carpeta `bpid-suite` a `/wp-content/plugins/` vía FTP o el administrador de archivos.

---

## Paso 2: Activar el plugin

1. Ir a **WordPress Admin → Plugins → Plugins instalados**.
2. Buscar **"BPID Suite"** en la lista.
3. Hacer clic en **"Activar"**.

> Al activar, el plugin crea automáticamente la tabla `wp_bpid_suite_contratos` en la base de datos.

---

## Paso 3: Configurar la API key

1. Ir a **BPID Suite → Configuración** en el menú lateral.
2. En la sección **"API Key BPID"**, ingresar la clave proporcionada.
3. Hacer clic en **"Probar conexión"** para verificar que la API responde correctamente.
4. Si la prueba es exitosa, se mostrará el número total de contratos disponibles.
5. Hacer clic en **"Guardar Configuración"**.

---

## Paso 4: Primera importación

1. Ir a **BPID Suite → Importación**.
2. Hacer clic en **"Iniciar Importación"**.
3. Esperar a que la barra de progreso llegue al 100%.
4. Verificar los resultados: contratos insertados, actualizados y errores.

> La importación descarga todos los contratos de la API BPID y los almacena localmente. Esto permite consultas rápidas sin depender de la API en cada visita.

---

## Paso 5: Programar importación automática (opcional)

1. Ir a **BPID Suite → Configuración**.
2. En **"Programación Cron"**, seleccionar la frecuencia deseada:
   - **Diario**: actualiza datos cada 24 horas.
   - **Semanal**: actualiza datos cada 7 días.
   - **Mensual**: actualiza datos cada 30 días.
   - **Desactivado**: solo importación manual.
3. Guardar la configuración.

---

## Paso 6: Crear una gráfica

1. Ir a **BPID Suite → Gráficos → Añadir nuevo**.
2. Escribir un título para la gráfica.
3. Configurar el tipo de gráfico (15 tipos disponibles).
4. Seleccionar las columnas para los ejes X, Y, agrupación y color.
5. Publicar la gráfica.
6. Copiar el shortcode mostrado, por ejemplo: `[bpid_chart id="123"]`.
7. Pegar el shortcode en cualquier página o entrada de WordPress.

---

## Paso 7: Crear un filtro

1. Ir a **BPID Suite → Filtros → Añadir nuevo**.
2. Escribir un título para el filtro.
3. Seleccionar las columnas a incluir y el tipo de campo para cada una.
4. Publicar el filtro.
5. Copiar el shortcode: `[bpid_filter id="456"]`.
6. Pegar el shortcode en la página deseada.

---

## Paso 8: Crear un visualizador de proyectos

1. Ir a **BPID Suite → Visualizadores → Añadir nuevo**.
2. Escribir un título.
3. Configurar las opciones: estadísticas, filtros, colores, columnas del grid, etc.
4. Publicar el visualizador.
5. Usar el shortcode: `[bpid_grid_visualizador id="789"]`.

O usar directamente sin configuración previa:
```
[bpid_grid_visualizador]
```

---

## Verificación

Después de la instalación, verificar en **BPID Suite → Configuración**:

- **Versión del plugin**: 1.0.0
- **Estado de la tabla**: Existente con X registros
- **Conexión API**: Exitosa

---

## Troubleshooting

### Error "No se pudo conectar con la API"

- Verificar que la API key es correcta.
- Verificar que el servidor puede realizar conexiones HTTPS salientes.
- Comprobar que no hay un firewall bloqueando `bpid.narino.gov.co`.

### Error "La tabla no existe"

- Ir a **Configuración → Mantenimiento** y hacer clic en **"Regenerar tabla"**.
- Si el error persiste, desactivar y reactivar el plugin.

### Los gráficos no se muestran

- Verificar que la librería D3plus carga correctamente (inspeccionar la consola del navegador).
- Verificar que hay datos importados (ir a **BPID Suite → Registros**).
- Comprobar que el shortcode tiene el ID correcto.

### La importación se queda pausada

- Verificar el tiempo máximo de ejecución PHP (`max_execution_time`).
- Verificar los logs en **BPID Suite → Logs**.
- Intentar la importación vía WP-CLI: `wp bpid import`.

### Error de permisos en logs

- Verificar que el directorio `bpid-suite/logs/` tiene permisos de escritura (755 o 775).
- Verificar que el propietario del directorio es el usuario del servidor web.

---

## Desinstalación

Para eliminar completamente el plugin y todos sus datos:

1. Ir a **Plugins → Plugins instalados**.
2. **Desactivar** BPID Suite.
3. Hacer clic en **"Eliminar"**.

Esto eliminará:
- La tabla `wp_bpid_suite_contratos`
- Todas las opciones del plugin en `wp_options`
- Todos los CPTs (gráficos, filtros, visualizadores)
- Todos los transients y tareas cron
- Los archivos de log

---

*Gobernación de Nariño · Secretaría de TIC, Innovación y Gobierno Abierto*
