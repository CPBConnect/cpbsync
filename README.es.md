# CPB Sync

**Sincronización de productos para PrestaShop mediante catálogos externos, mapeo configurable de campos, transformaciones de datos, procesamiento por lotes y sincronización programada.**

CPB Sync es un módulo para PrestaShop desarrollado por **CPBConnect** que permite a los administradores sincronizar información de productos desde catálogos externos con su tienda PrestaShop.

La versión **1.2.0** sincroniza catálogos CSV con mapeos configurables, transformaciones, validación, Dry Run, procesamiento por lotes, historial de sincronizaciones y ejecución automática mediante cron. La edición de pago añade fuentes XML, JSON y REST, sincronización incremental, catálogos anidados, transformaciones avanzadas y un panel de monitorización.

## ❤️ Apoya CPB Sync

CPB Sync es gratuito y de código abierto.

Si CPB Sync te resulta útil, considera apoyar su desarrollo continuo.

[☕ Apoyar CPB Sync vía PayPal](https://paypal.me/cpbconnet)

## Características

- 📥 Importación de catálogos de productos desde fuentes CSV
- 🔗 Mapeo configurable entre campos externos y campos de PrestaShop, con el campo destino propuesto para los nombres habituales
- 🔄 Transformaciones de datos configurables
- 🧪 Dry Run antes de aplicar cambios
- 📦 Creación y actualización de productos
- ⏭️ Omisión automática de productos sin cambios
- 🖼️ Sincronización de imágenes de productos, con varias imágenes por producto
- ⚙️ Opciones de sincronización por fuente: sólo crear, rellenar sólo los campos vacíos, no tocar el stock y no importar imágenes
- 📊 Resultados e historial de sincronizaciones, con duración, pico de memoria y tiempo por fase
- 📈 Procesamiento por lotes con seguimiento del progreso
- ⏰ Sincronización programada mediante cron
- ✅ Validación de datos de productos
- ⚠️ Manejo individual de errores por producto
- 🗂️ Múltiples fuentes de datos configurables
- 🧹 Limpieza automática de archivos temporales de importación

## Fuentes compatibles

### CSV

La versión gratuita lee catálogos en formato CSV.

Ejemplo:

```csv
sku,name,description,price,stock,category,brand,image,ean
ABC001,Product One,Product description,25.99,10,Category A,Brand A,https://example.com/image.jpg,1234567890123
ABC002,Product Two,Another description,49.99,5,Category B,Brand B,https://example.com/image2.jpg,1234567890124
```

Los campos del CSV pueden mapearse a los campos de producto compatibles con PrestaShop.

Las fuentes XML, JSON y REST forman parte de la edición de pago.

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

La versión gratuita incluye:

- Sin transformación
- Normalización de precios (con símbolo de moneda y las dos convenciones decimales: `1.234,56` y `1,234.56`)
- Normalización de stock
- Normalización de texto
- Reemplazo de texto

Las transformaciones permiten adaptar los datos del catálogo externo antes de sincronizarlos con PrestaShop.

La edición de pago añade quince más: tablas de equivalencias, valores por defecto, campos alternativos, unión de campos, prefijos y sufijos, operaciones aritméticas con redondeo, extracción y reemplazo con expresiones regulares, conversión a sí/no, acortar textos, slugs, mayúsculas y minúsculas, quitar HTML, tomar una parte del valor y quedarse sólo con los números.

## Imágenes

El campo de imagen admite una lista, así que un producto puede importar varias imágenes: separadas por comas, punto y coma, barras verticales o una por línea, hasta 20 por producto.

- Sólo la primera imagen es la portada; el resto se importan sin portada, como hace PrestaShop.
- Las imágenes se descargan antes de borrar las anteriores, así que un proveedor que deja de responder no deja al producto sin imágenes.
- Un enlace roto no impide el resto: el producto se guarda si al menos se pudo traer una imagen.
- Los catálogos anidados (edición de pago) exponen las imágenes como `images.0`, `images.1`…, que se pueden unir con la transformación «Unir campos».

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

- Crear nuevos productos
- Actualizar productos existentes
- Omitir productos que no presentan cambios
- Validar los datos de los productos
- Reportar errores individuales
- Continuar procesando el resto de productos cuando un producto presenta un error

Los resultados de cada sincronización se almacenan en el historial del módulo.

Cada fuente decide qué puede escribir la sincronización. Las opciones se marcan en el formulario de la fuente y se guardan con ella:

- **Sólo crear productos nuevos**: los productos que ya existen no se tocan, aunque el catálogo haya cambiado. Cuentan como omitidos.
- **Rellenar sólo los campos vacíos**: la referencia, el nombre, la descripción, el precio y el EAN sólo se escriben cuando el producto no tiene nada ahí, así que los valores editados a mano se conservan. El stock tiene su propia opción, porque un stock vacío en un catálogo suele significar "agotado" y rellenarlo vaciaría la cantidad de la tienda.
- **No sincronizar el stock**: la cantidad de la tienda se queda como está.
- **No importar imágenes**: no se descarga ninguna imagen y se conservan las que el producto ya tiene. Las descargas no se preparan siquiera, así que tampoco se gasta red en comprobarlas.

Sin ninguna opción marcada la sincronización se comporta como antes: se actualiza todo lo mapeado, el stock y las imágenes.

Cada sincronización registra:

- Total de productos
- Productos creados
- Productos actualizados
- Productos omitidos
- Errores
- Duración, pico de memoria y tiempo de cada fase (lectura de la fuente, aplicación del mapeo y escritura de productos)
- Detalles de los resultados

El historial guarda el resumen de cada ejecución y una muestra de los productos procesados (200 por defecto), de modo que un cron horario con un catálogo grande no hace crecer la base de datos sin límite.

## Cron

CPB Sync incluye soporte para sincronización programada mediante cron.

Las frecuencias disponibles son:

- Manual
- Cada hora
- Cada 6 horas
- Diaria

La edición de pago añade cada 15 y 30 minutos, cada 12 horas, todos los días a una hora fija, una vez a la semana (eligiendo el día) y una vez al mes (eligiendo el día). Las horas se leen en la zona horaria de la tienda y cada fuente se ejecuta una vez por periodo: las ejecuciones que se pierden no se acumulan.

El listado de fuentes muestra la frecuencia de cada una y cuándo le toca la siguiente ejecución.

El cron solamente ejecuta las fuentes activas que tengan configurada una frecuencia programada.

El ejecutor de cron utiliza el mismo sistema de procesamiento por lotes que las importaciones manuales.

Esto permite que las sincronizaciones manuales y programadas utilicen la misma lógica de procesamiento de productos.

El comando puede programarse con más frecuencia de la que necesitan las fuentes (cada cinco minutos, por ejemplo): cada fuente sólo se ejecuta cuando le toca.

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

- PrestaShop 8.0 o superior (verificado en PrestaShop 9.0.0 con PHP 8.4)
- Versión de PHP compatible con la versión instalada de PrestaShop
- MySQL/MariaDB compatible con PrestaShop
- Dependencias de Composer incluidas en el paquete del módulo

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

### Manual de usuario

La guía paso a paso para el uso diario (fuentes, mapeo, transformaciones, opciones de sincronización, programación, historial y resolución de problemas) está en [`docs/manual.es.md`](docs/manual.es.md), con su versión en inglés en [`docs/manual.en.md`](docs/manual.en.md).

### Después de actualizar el módulo

Limpia la caché de PrestaShop (**Parámetros avanzados > Rendimiento**) para que se carguen las traducciones y plantillas nuevas. Desde la línea de comandos:

```bash
php bin/console cache:clear --env=prod
```

Ejecuta los comandos de consola de PrestaShop como el usuario del servidor web (`www-data`), no como root: los directorios de caché que crea deben pertenecer al usuario que sirve la tienda.

## Estructura del proyecto

```text
cpbsync/
├── controllers/
├── src/
│   ├── Application/
│   │   ├── Import/
│   │   ├── Mapping/
│   │   ├── Product/
│   │   ├── Source/
│   │   ├── Sync/
│   │   ├── Transform/
│   │   └── Validation/
│   ├── Infrastructure/
│   │   ├── Persistence/
│   │   ├── PrestaShop/
│   │   └── Source/
│   └── Presentation/
│       └── Admin/
│           └── Handler/
├── docs/
│   ├── manual.en.md
│   └── manual.es.md
├── tests/
├── tools/
├── translations/
├── views/
├── composer.json
├── composer.lock
├── cpbsync.php
└── cron.php
```

El módulo utiliza una estructura por capas que separa la lógica de aplicación, la infraestructura y la integración con PrestaShop:

- **Application** contiene los casos de uso: fuentes, mapping, transformaciones, sincronización de productos e historial.
- **Infrastructure** contiene la persistencia, los lectores de catálogos y los adaptadores de PrestaShop.
- **Presentation** contiene las acciones del back office y su enrutado. `cpbsync.php` sólo conserva el ciclo de vida del módulo y delega cada `cpbsync_action` en la capa de presentación.

## Pruebas

La suite de pruebas se ejecuta sin una instalación de PrestaShop:

```bash
php tests/run.php
```

o, con Composer:

```bash
composer test
```

## Traducciones

CPB Sync utiliza el sistema de traducción nuevo de PrestaShop (dominios de traducción) y no depende de los ficheros de diccionario clásicos.

- Todos los textos pertenecen al dominio `Modules.Cpbsync.Admin`.
- El código PHP traduce con `trans()` / `getTranslator()->trans()`; las plantillas Smarty usan `{l s='...' d='Modules.Cpbsync.Admin'}`.
- El módulo declara `isUsingNewTranslationSystem()`, por lo que aparece en **Internacional > Traducciones > Modificar traducciones**.

Los catálogos de traducción se distribuyen como ficheros XLIFF:

```text
translations/
├── en-US/
│   └── ModulesCpbsyncAdmin.en-US.xlf
├── es-ES/
│   └── ModulesCpbsyncAdmin.es-ES.xlf
└── translations-to-do.csv
```

Los textos originales están en inglés y el español se ofrece como traducción.

Para añadir otro idioma, copia uno de los ficheros XLIFF a `translations/<locale>/ModulesCpbsyncAdmin.<locale>.xlf`, actualiza `target-language` y traduce los elementos `<target>`. PrestaShop carga estos ficheros durante la instalación del módulo; si los editas después, reinstala el módulo o limpia la caché.

`translations/translations-to-do.csv` es un glosario legible con todos los textos y su traducción al español.

Para comprobar que no falta ningún texto en los catálogos:

```bash
php tools/check-translations.php
```

o, con Composer:

```bash
composer check-translations
```

## Versión actual

**Versión:** 1.2.0

La versión 1.2.0 completa el flujo de sincronización de productos y corrige los problemas que aparecieron al probarlo contra una tienda PrestaShop 9 real. Desde la 1.0.0:

- Las fuentes, el mapeo, las transformaciones, el Dry Run y el historial viven en capas separadas, así que el módulo puede crecer sin tocar el motor de sincronización.
- Cada ejecución registra su duración, su pico de memoria y el desglose por fases, y el historial guarda una muestra acotada de productos en lugar de todos.
- Toda la interfaz es traducible (el módulo incluye los catálogos en inglés y español).
- Los productos se crean con URL amable y se pueden crear categorías desde una fuente.
- La importación de imágenes admite varias imágenes por producto.
- Las transformaciones de precio, stock y texto aceptan los formatos que usan los catálogos reales de proveedores.

Consulta el archivo `CHANGELOG.md` para la lista completa.

## Roadmap

La edición de pago cubre las fuentes XML, JSON y REST, la sincronización incremental, los catálogos anidados, las transformaciones avanzadas, varias imágenes por producto, más programación y el panel de monitorización. Las opciones de sincronización forman parte de la versión gratuita, así que el roadmap anunciado está completo: lo siguiente se decidirá a partir de los comentarios de los usuarios y las necesidades reales de las tiendas.

## Versión gratuita

CPB Sync 1.2.0 se proporciona de forma gratuita.

La versión gratuita incluye el flujo completo de sincronización CSV: fuentes, mapeo, transformaciones, Dry Run, procesamiento por lotes, importación de imágenes, opciones de sincronización por fuente, historial y ejecución programada mediante cron.

## Edición de pago

La edición de pago añade, sobre la versión gratuita:

- **Fuentes XML, JSON y REST**, con los catálogos anidados aplanados en columnas que se pueden mapear (`price.value`, `categories.0.name`, `Цены.Цена.ЦенаЗаЕдиницу`).
- **Sincronización incremental**: los productos sin cambios se omiten. Medido en PrestaShop 9.0.0 con 200 productos, una resincronización pasa de 3,5 ms y 4,1 consultas por producto a 0,5 ms y 0,2 consultas.
- **Descarga de imágenes en paralelo**: 3,5 veces más rápida con 8 descargas a la vez.
- **Quince transformaciones avanzadas**: tablas de equivalencias, valores por defecto, campos alternativos, unión de campos, prefijos y sufijos, operaciones aritméticas con redondeo, expresiones regulares, conversión a sí/no, acortar textos, slugs, mayúsculas y minúsculas, quitar HTML, tomar una parte del valor y quedarse sólo con los números.
- **Un panel de monitorización**: periodos, contadores, errores más frecuentes, tamaño del historial y retención.
- **Más programación**: cada 15 o 30 minutos, cada 12 horas, todos los días a una hora fija, una vez a la semana o una vez al mes.

Las dos ediciones comparten el mismo código: el paquete gratuito se genera a partir de él y no contiene código de pago.

## Contribuir

Las sugerencias, reportes de errores y contribuciones son bienvenidos.

Si encuentras un problema o tienes una idea para mejorar CPB Sync, puedes abrir un issue o enviar un pull request.

## Licencia

Consulta el archivo `LICENSE` incluido en este repositorio.

## Sobre CPBConnect

CPB Sync es desarrollado por **CPBConnect**, un proyecto de software enfocado en integraciones, automatización y herramientas para plataformas de comercio electrónico.

---

**CPB Sync — Simplifica la sincronización de tu catálogo en PrestaShop.**
