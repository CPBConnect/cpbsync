# CPB Sync

**Sincronización de productos para PrestaShop mediante catálogos externos, mapeo configurable de campos, transformaciones de datos, procesamiento por lotes y sincronización programada.**

CPB Sync es un módulo para PrestaShop desarrollado por **CPBConnect** que permite a los administradores sincronizar información de productos desde catálogos externos con su tienda PrestaShop.

La versión **1.0.0** se centra en una sincronización confiable basada en archivos CSV, con mapeos configurables, transformaciones, validación, Dry Run, procesamiento por lotes, historial de sincronizaciones y ejecución automática mediante cron.

## Características

* 📥 Importación de catálogos de productos desde fuentes CSV
* 🔗 Mapeo configurable entre campos externos y campos de PrestaShop
* 🔄 Transformaciones de datos configurables
* 🧪 Dry Run antes de aplicar cambios
* 📦 Creación y actualización de productos
* ⏭️ Omisión automática de productos sin cambios
* 🖼️ Sincronización de imágenes de productos
* 📊 Resultados e historial de sincronizaciones
* 📈 Procesamiento por lotes con seguimiento del progreso
* ⏰ Sincronización programada mediante cron
* ✅ Validación de datos de productos
* ⚠️ Manejo individual de errores por producto
* 🗂️ Múltiples fuentes de datos configurables
* 🧹 Limpieza automática de archivos temporales de importación

## Fuentes compatibles

### CSV

CPB Sync 1.0.0 actualmente admite catálogos en formato CSV.

Ejemplo:

```csv
sku,name,description,price,stock,category,brand,image,ean
ABC001,Product One,Product description,25.99,10,Category A,Brand A,https://example.com/image.jpg,1234567890123
ABC002,Product Two,Another description,49.99,5,Category B,Brand B,https://example.com/image2.jpg,1234567890124
```

Los campos del CSV pueden mapearse a los campos de producto compatibles con PrestaShop.

## Mapeo

CPB Sync permite configurar cómo los campos del catálogo externo se relacionan con los campos de PrestaShop.

Ejemplo:

| Campo de origen | Campo de PrestaShop |
| --------------- | ------------------- |
| sku             | reference           |
| name            | name                |
| description     | description         |
| price           | price               |
| stock           | quantity            |
| category        | category            |
| brand           | manufacturer        |
| image           | image               |
| ean             | ean13               |

Un campo de origen también puede tener una transformación aplicada antes de la sincronización.

## Transformaciones

La versión actual incluye:

* Sin transformación
* Normalización de precios
* Normalización de stock
* Normalización de texto
* Reemplazo de texto

Las transformaciones permiten adaptar los datos del catálogo externo antes de sincronizarlos con PrestaShop.

## Dry Run

Antes de realizar una sincronización real, CPB Sync proporciona una función **Dry Run**.

El Dry Run procesa productos y muestra los datos originales junto con los datos después de aplicar el mapeo y las transformaciones.

Esto permite verificar la configuración antes de modificar el catálogo de la tienda.

## Procesamiento por lotes

Los catálogos CSV grandes pueden procesarse por lotes en lugar de procesarse en una única solicitud.

CPB Sync procesa las importaciones en bloques de productos y mantiene el progreso de la importación.

Esto ayuda a reducir el riesgo de alcanzar los límites de tiempo de ejecución del servidor al importar catálogos grandes.

Las importaciones manuales muestran información del progreso mientras se ejecuta la sincronización.

Los archivos CSV temporales subidos durante una importación se eliminan automáticamente después de completar correctamente el proceso para evitar un uso innecesario de almacenamiento.

## Sincronización

Después de validar el mapeo, los administradores pueden ejecutar una sincronización.

CPB Sync puede:

* Crear nuevos productos
* Actualizar productos existentes
* Omitir productos que no presentan cambios
* Validar los datos de los productos
* Reportar errores individuales
* Continuar procesando el resto de productos cuando un producto presenta un error

Los resultados de cada sincronización se almacenan en el historial del módulo.

Cada sincronización registra:

* Total de productos
* Productos creados
* Productos actualizados
* Productos omitidos
* Errores
* Detalles de los resultados

## Cron

CPB Sync incluye soporte para sincronización programada mediante cron.

Las frecuencias disponibles son:

* Manual
* Cada hora
* Cada 6 horas
* Diaria

El cron solamente ejecuta las fuentes activas que tengan configurada una frecuencia programada.

El ejecutor de cron utiliza el mismo sistema de procesamiento por lotes que las importaciones manuales.

Esto permite que las sincronizaciones manuales y programadas utilicen la misma lógica de procesamiento de productos.

### Comando del cron

El script de cron está diseñado para ejecutarse desde la línea de comandos:

```bash
php modules/cpbsync/cron.php
```

Por ejemplo, un servidor puede ejecutar CPB Sync cada hora mediante:

```bash
0 * * * * php /ruta/a/prestashop/modules/cpbsync/cron.php
```

El proceso de cron incluye un mecanismo de bloqueo para evitar que varias ejecuciones de CPB Sync se ejecuten simultáneamente.

## Flujo de sincronización

```text
CSV externo
     │
     ▼
Configuración de fuente
     │
     ▼
Mapeo de campos
     │
     ▼
Transformaciones
     │
     ▼
Dry Run
     │
     ▼
Procesamiento por lotes
     │
     ▼
Validación de productos
     │
     ▼
Crear / Actualizar / Omitir
     │
     ▼
Historial de sincronización
     │
     ▼
Catálogo de PrestaShop
```

La sincronización programada sigue el mismo flujo:

```text
Cron del servidor
     │
     ▼
CronRunner
     │
     ▼
Fuente activa programada
     │
     ▼
ImportBatchProcessor
     │
     ▼
Sincronización de productos
     │
     ▼
Historial de sincronización
```

## Requisitos

* PrestaShop 8.0 o superior
* Versión de PHP compatible con la versión instalada de PrestaShop
* MySQL/MariaDB compatible con PrestaShop
* Dependencias de Composer incluidas en el paquete del módulo

## Instalación

1. Descarga el paquete ZIP del módulo CPB Sync.
2. Abre el Back Office de PrestaShop.
3. Ve a **Módulos > Gestor de módulos**.
4. Selecciona **Subir un módulo**.
5. Sube el archivo ZIP de CPB Sync.
6. Instala el módulo.
7. Abre la página de configuración de CPB Sync.

Después de instalarlo:

1. Crea una fuente CSV externa.
2. Configura los parámetros de la fuente.
3. Configura el mapeo de campos.
4. Configura las transformaciones si son necesarias.
5. Ejecuta un Dry Run.
6. Ejecuta la sincronización.

Para utilizar la sincronización automática, configura la frecuencia deseada y agrega el comando de cron de CPB Sync al programador del servidor.

## Estructura del proyecto

```text
cpbsync/
├── config/
├── controllers/
├── src/
│   ├── Application/
│   ├── Domain/
│   └── Infrastructure/
├── tests/
├── translations/
├── views/
├── composer.json
├── composer.lock
├── cpbsync.php
└── cron.php
```

El módulo utiliza una estructura por capas que separa la lógica de aplicación, dominio, infraestructura e integración con PrestaShop.

## Versión actual

**Versión:** 1.0.0

CPB Sync 1.0.0 es la primera versión pública del proyecto.

Esta versión proporciona un flujo completo de sincronización de productos mediante CSV, incluyendo importaciones manuales, procesamiento por lotes, validación, Dry Run, historial de sincronizaciones y ejecución programada mediante cron.

## Roadmap

Las futuras versiones pueden incluir:

* Fuentes XML
* Fuentes JSON
* Integraciones con APIs REST
* Sincronización incremental
* Reglas avanzadas de transformación
* Opciones adicionales de sincronización
* Mejoras en logging y monitoreo
* Opciones adicionales de programación
* Funcionalidades premium

El roadmap puede evolucionar según los comentarios de los usuarios y las necesidades reales de las tiendas.

## Versión gratuita

CPB Sync 1.0.0 se proporciona de forma gratuita.

La versión gratuita incluye el flujo completo de sincronización CSV disponible en la versión 1.0.0.

Las futuras versiones podrán incorporar funcionalidades adicionales premium manteniendo las funcionalidades incluidas en la versión gratuita.

## Contribuir

Las sugerencias, reportes de errores y contribuciones son bienvenidos.

Si encuentras un problema o tienes una idea para mejorar CPB Sync, puedes abrir un issue o enviar un pull request.

## Licencia

Consulta el archivo `LICENSE` incluido en este repositorio.

## Sobre CPBConnect

CPB Sync es desarrollado por **CPBConnect**, un proyecto de software enfocado en integraciones, automatización y herramientas para plataformas de comercio electrónico.

---

**CPB Sync — Simplifica la sincronización de tu catálogo en PrestaShop.**
