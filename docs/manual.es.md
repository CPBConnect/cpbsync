# Manual de usuario de CPB Sync

**Versión del módulo:** 1.4.0
**Compatible con:** PrestaShop 8.0 o posterior (probado en PrestaShop 9.0.0 con PHP 8.4)

CPB Sync conecta tu tienda con los catálogos de tus proveedores, tu ERP o tu
propia web de fabricante. El módulo lee el catálogo, lo traduce a los campos de
PrestaShop con las reglas que tú decidas y crea o actualiza los productos, sin
tocar nada que no le hayas dicho que toque.

Este manual explica el uso diario: está escrito para quien administra la tienda,
no para quien programa el módulo. Si buscas la documentación técnica, está en el
`README.md` del repositorio.

Dos ediciones comparten el mismo código y el mismo número de versión:

| | Versión gratuita | Edición de pago |
| --- | --- | --- |
| Catálogos CSV | Sí | Sí |
| Catálogos XML, JSON y API REST | No | Sí |
| Catálogos anidados y transformaciones avanzadas | No (4 transformaciones) | Sí (19 transformaciones) |
| Varias imágenes por producto | Sí | Sí |
| Opciones de sincronización por fuente | Sí | Sí |
| Programación por cron | Sí (4 frecuencias) | Sí (10 frecuencias) |
| Descarga de imágenes en paralelo | No | Sí |
| Panel de monitorización | No | Sí |

Cuando una función sea exclusiva de la edición de pago, este manual lo indica.

---

## Índice

1. [Antes de empezar](#1-antes-de-empezar)
2. [Instalación](#2-instalación)
3. [Actualizar el módulo](#3-actualizar-el-módulo)
4. [Cómo se entra al módulo](#4-cómo-se-entra-al-módulo)
5. [La pantalla de fuentes](#5-la-pantalla-de-fuentes)
6. [Crear una fuente](#6-crear-una-fuente)
7. [Probar la conexión](#7-probar-la-conexión)
8. [Mapear los campos](#8-mapear-los-campos)
9. [Transformaciones](#9-transformaciones)
10. [Dry Run: ver el resultado antes de tocar nada](#10-dry-run-ver-el-resultado-antes-de-tocar-nada)
11. [Sincronizar](#11-sincronizar)
12. [Programación y cron](#12-programación-y-cron)
13. [Historial](#13-historial)
14. [Monitorización (edición de pago)](#14-monitorización-edición-de-pago)
15. [Cómo se comporta la sincronización](#15-cómo-se-comporta-la-sincronización)
16. [Recetas](#16-recetas)
17. [Problemas frecuentes](#17-problemas-frecuentes)
18. [Preguntas frecuentes](#18-preguntas-frecuentes)
19. [Glosario](#19-glosario)
20. [Anexos](#20-anexos)

---

## 1. Antes de empezar

Necesitas:

- **La URL del catálogo** de tu proveedor: un enlace `http` o `https` que
  devuelva el archivo (`.csv`, `.xml`, `.json`) o el punto de una API. También
  sirve un archivo que subas tú a mano desde el módulo.
- **Saber qué columna identifica al producto** de forma única. Casi siempre es
  la referencia del proveedor (*SKU*). CPB Sync la necesita: sin ella no puede
  saber si un producto es nuevo o ya existe en la tienda.
- **PrestaShop 8.0 o posterior** y la versión de PHP que pida tu PrestaShop.

Un catálogo de productos de ejemplo, en CSV:

```csv
sku,name,description,price,stock,category,brand,image,ean
ABC001,Producto Uno,Descripción del producto,25.99,10,Categoría A,Marca A,https://ejemplo.com/imagen.jpg,1234567890123
ABC002,Producto Dos,Otra descripción,49.99,5,Categoría B,Marca B,https://ejemplo.com/imagen2.jpg,1234567890124
```

---

## 2. Instalación

1. Descarga el ZIP del módulo.
2. Entra en el back office de PrestaShop.
3. Ve a **Módulos → Gestor de módulos**.
4. Pulsa **Subir un módulo**.
5. Sube el archivo ZIP de CPB Sync.
6. Instálalo.
7. Abre su pantalla de configuración.

La instalación crea las tablas del módulo y los catálogos de traducción. Si algo
falla, el módulo **no** queda marcado como instalado: vuelve a intentarlo y, si
persiste, revisa los permisos de escritura de la carpeta del módulo.

Para desinstalarlo, usa el botón habitual de PrestaShop. La desinstalación borra
las tablas del módulo (fuentes, mapeos, historial), así que **los productos que
haya creado en la tienda se quedan donde están**: el módulo no borra productos.

---

## 3. Actualizar el módulo

1. Sube el ZIP de la versión nueva como en la instalación anterior.
2. PrestaShop aplica las migraciones de la base de datos.
3. **Limpia la caché**: **Parámetros avanzados → Rendimiento → Vaciar caché**.

Sin el paso 3 pueden verse plantillas antiguas o textos sin traducir. Si copias
los archivos a mano en lugar de subir el ZIP, entra en **Módulos → Gestor de
módulos** para que PrestaShop aplique la actualización.

Desde la línea de comandos:

```bash
php bin/console cache:clear --env=prod
```

Ejecuta los comandos de PrestaShop como el usuario del servidor web
(`www-data`), no como `root`: los directorios de caché que crea tienen que
pertenecer al usuario que sirve la tienda.

---

## 4. Cómo se entra al módulo

**Módulos → Gestor de módulos → CPB Sync → Configurar**.

No hay pestaña propia en el menú. Todas las pantallas del módulo se abren desde
esta página: la navegación se hace con los botones de cada pantalla.

| Pantalla | Cómo se llega |
| --- | --- |
| Fuentes de datos | Es la página inicial del módulo |
| Agregar fuente / Editar | Botones **Agregar fuente** y **Editar** |
| Vista previa de la fuente | Botón **Probar conexión** de una fuente |
| Mapear campos | Botón **Mapear campos** de una fuente |
| Dry Run | Botón **Ejecutar Dry Run** dentro del mapeo |
| Resultado de sincronización | Botón **Ejecutar Sync** dentro del mapeo |
| Importar CSV | Botón **Importar CSV** |
| Historial | Botón **Historial** |
| Monitorización (pago) | Botón **Monitorización** |

---

## 5. La pantalla de fuentes

Es el punto de partida. Cada fuente se muestra en su propio panel con:

- El **nombre** de la fuente, como título.
- **Tipo:** `csv`, `xml`, `json` o `rest`.
- **URL:** la dirección del catálogo.
- **Frecuencia:** cada cuánto se ejecuta, y **Próxima ejecución:** con la fecha
  y la hora previstas. Con la frecuencia *Manual* no se muestra próxima
  ejecución, porque no le toca sola nunca.
- **Estado:** **Activa** (verde) o **Inactiva** (gris).

Y cuatro botones:

| Botón | Qué hace |
| --- | --- |
| **Probar conexión** | Lee el catálogo y muestra sus columnas y sus primeros registros, sin guardar nada. |
| **Mapear campos** | Abre la tabla de mapeo y desde ahí se lanza el Dry Run y la sincronización. |
| **Editar** | Cambia los datos de la fuente. |
| **Eliminar** | Borra la fuente y su mapeo, tras confirmar. **No borra los productos** que haya creado. |

En la esquina superior derecha están **Importar CSV**, **Historial**,
**Agregar fuente** y, con la edición de pago, **Monitorización**.

Si todavía no hay fuentes, la pantalla lo dice: «Aún no tienes fuentes
configuradas.»

---

## 6. Crear una fuente

Pulsa **Agregar fuente**. El formulario pide:

| Campo | Para qué |
| --- | --- |
| **Nombre** | Cómo quieres llamar a la fuente. Por ejemplo, *Proveedor ABC*. |
| **Tipo de fuente** | Cómo se lee el catálogo: `CSV`, `XML`, `JSON` o `REST API (JSON)`. |
| **URL de la fuente** | La dirección del catálogo, o la ruta del archivo que hayas subido. |
| **Configuración adicional (JSON)** | Opcional. Ajustes del lector (ver más abajo). |
| **Frecuencia** | *Manual*, *Cada hora*, *Cada 6 horas*, *Diariamente* y, con la edición de pago, *Cada 15 minutos*, *Cada 30 minutos*, *Cada 12 horas*, *Todos los días a una hora fija*, *Una vez a la semana* y *Una vez al mes*. |
| **Fuente activa** | Marcada por defecto. Una fuente inactiva no se ejecuta por cron. |
| **Opciones de sincronización** | Qué puede escribir la sincronización (ver más abajo). |

El nombre y la URL son obligatorios, y la URL tiene que ser válida (`http` o
`https`). Si algo no cuadra, el formulario se vuelve a mostrar con un aviso en
rojo: por ejemplo, «El nombre de la fuente es obligatorio.», «La URL de la
fuente es obligatoria.» o «La configuración adicional debe ser un JSON válido.».

> Al editar una fuente, la cabecera del formulario sigue diciendo *Nueva
> fuente*: es el mismo formulario para las dos cosas. Los datos que ves son los
> de la fuente que estás editando.

### 6.1 Configuración adicional por tipo de catálogo

El campo **Configuración adicional (JSON)** sólo lo usan los lectores XML, JSON
y REST. Se escribe en formato JSON.

**CSV:** no necesita nada. El módulo detecta solo si las columnas van separadas
por coma, por punto y coma o por tabulador.

**XML** (edición de pago): `record_path` con la ruta XPath del elemento que se
repite. Si se deja vacío, el módulo busca el elemento que se repite entre
hermanos.

```json
{"record_path": "/catalog/products/product"}
```

**JSON** (edición de pago): `record_path` con la ruta al listado, con puntos
para bajar de nivel. Si se deja vacío, se usa la lista más larga que contenga el
objeto raíz.

```json
{"record_path": "data.products"}
```

**API REST** (edición de pago): todo se configura aquí.

```json
{
  "record_path": "data.items",
  "method": "GET",
  "params": {"lang": "es"},
  "headers": {"Accept": "application/json"},
  "auth": {"type": "bearer", "token": "..."},
  "pagination": {
    "type": "page",
    "param": "page",
    "size_param": "per_page",
    "page_size": 100,
    "start": 1,
    "total_path": "meta.total",
    "max_pages": 100
  }
}
```

- `auth.type`: `none`, `bearer` (con `token`), `basic` (con `username` y
  `password`) o `header` (con `header` y `value`).
- `pagination.type`: `none`, `page` (con `param`, `size_param` y `start`) u
  `offset` (con `offset_param` y `limit_param`).
- `total_path` evita descargar el catálogo entero sólo para contarlo.
- `page_size` admite hasta 1000 registros por página y `max_pages` hasta 1000
  páginas: son los topes que protegen a la tienda de una API que nunca termina.

### 6.2 Opciones de sincronización

Cada fuente decide qué se escribe en PrestaShop. Las cuatro opciones están
desmarcadas por defecto, y sin marcar ninguna la sincronización se comporta como
siempre: actualiza todo lo que hayas mapeado.

| Opción | Qué hace |
| --- | --- |
| **Crear sólo productos nuevos** | Los productos que ya existen no se tocan, aunque el catálogo haya cambiado. Cuentan como *Omitido*. |
| **Rellenar sólo los campos vacíos** | Se respetan los valores que ya tiene el producto: útil cuando las descripciones o los precios se editan a mano. |
| **No sincronizar el stock** | La cantidad que hay en la tienda se deja como está. |
| **No importar imágenes** | Se conservan las imágenes que ya tiene el producto y no se descarga ninguna nueva. Tampoco se prepara ninguna descarga, así que no se pierde tiempo de red. |

Detalles que conviene conocer:

- **Rellenar sólo los campos vacíos** mira la referencia, el nombre, la
  descripción, el precio y el EAN. El stock tiene su propia opción porque un
  stock vacío en un catálogo suele significar «agotado», y rellenarlo vaciaría
  la cantidad de la tienda.
- Para el precio, un valor `0` cuenta como vacío.
- Con **No importar imágenes**, si además cambias el mapeo de imágenes, las
  imágenes del producto se quedan como estaban hasta que quites la opción.

Las opciones se guardan con la fuente y afectan por igual a la sincronización
manual, a la importación de archivos y al cron.

---

## 7. Probar la conexión

El botón **Probar conexión** lee el catálogo y abre la **Vista previa de la
fuente**:

- Un aviso verde: «Conexión exitosa.» con el **número de registros
  encontrados**.
- **Columnas detectadas**: la lista de columnas que ha encontrado el lector.
  Estos son los nombres que luego se mapean.
- **Primeros registros**: una tabla con los **5 primeros** registros del
  catálogo, con todas sus columnas.

No guarda nada ni toca ningún producto. Sirve para comprobar que la URL
responde, que el catálogo se interpreta bien y para copiar los nombres exactos
de las columnas antes de mapear.

Si la conexión falla, vuelves al listado con un aviso del tipo «No fue posible
conectar con la fuente: …». Las causas habituales están en
[Problemas frecuentes](#17-problemas-frecuentes).

---

## 8. Mapear los campos

Pulsa **Mapear campos** en la fuente. La pantalla muestra un aviso —«Asigna cada
campo de la fuente al campo correspondiente de PrestaShop.»— y una tabla con
**una fila por cada columna del catálogo** y tres columnas:

| Columna | Qué se elige |
| --- | --- |
| **Campo proveedor** | El nombre de la columna del catálogo. No se edita. |
| **Campo PrestaShop** | A qué campo de la tienda corresponde. |
| **Transformación** | Cómo se limpia o se convierte ese valor antes de guardarlo. |

### 8.1 Campos de PrestaShop disponibles

En **Campo PrestaShop** puedes elegir:

| Campo | Qué es |
| --- | --- |
| `reference` | La referencia del producto. **Es obligatoria**: identifica al producto. |
| `name` | El nombre. |
| `description` | La descripción. Admite HTML. |
| `price` | El precio (sin IVA, como lo guarda PrestaShop). |
| `quantity` | El stock. |
| `category` | La categoría, por su nombre. |
| `manufacturer` | El fabricante o la marca, por su nombre. |
| `image` | La imagen, o varias imágenes. |
| `ean13` | El código de barras EAN-13. |
| *-- No mapear --* | Esa columna del catálogo se ignora. |

Reglas que aplica el módulo:

- **Cada campo de PrestaShop se puede asignar una sola vez.** Si repites un
  destino, al guardar verás «El campo de PrestaShop "…" está asignado más de una
  vez.».
- Si no mapeas ninguna columna a `reference`, no se puede guardar el mapeo:
  «Debes asignar un campo del proveedor a reference.».
- Estos nueve campos son **todos** los que se pueden mapear. El módulo no escribe
  peso, precio de coste, impuestos, campos SEO, etiquetas, combinaciones ni
  segundas categorías: sólo una categoría y un fabricante por producto.
- Los nombres, las descripciones y el resto de textos se guardan en el **idioma
  por defecto de la tienda**.
- La **URL amable** no se mapea: se genera a partir del nombre y sólo cuando el
  producto todavía no tiene una, para no pisar las direcciones que ya hayas
  personalizado.

### 8.2 Sugerencias automáticas

Mientras la fuente **no tenga ningún mapeo guardado**, el módulo propone un
destino para los nombres de columna habituales, en inglés y en español: `sku`,
`referencia`, `name`, `nombre`, `precio`, `price`, `stock`, `cantidad`,
`marca`, `brand`, `imagen`, `image`, `ean`, `barcode`… También entiende columnas
anidadas: `price.value` propone `price` e `images.0` propone `image`.

Las sugerencias se pueden cambiar antes de guardar. En cuanto guardas un mapeo,
el módulo deja de proponer: así nunca guarda un mapeo que no hayas elegido tú.

### 8.3 Elegir transformaciones

En la columna **Transformación** cada fila ofrece sólo las transformaciones que
tienen sentido para el campo destino elegido (por ejemplo, *Normalizar precio*
sólo aparece en las filas que apuntan a `price`). Al elegir una, se despliegan
sus campos de configuración justo debajo, y lo que escribas se guarda con el
mapeo.

Los detalles de cada una están en [Transformaciones](#9-transformaciones).

### 8.4 Guardar

Pulsa **Guardar mapping**. Si todo va bien verás «El mapping se guardó
correctamente.». Los otros botones de la pantalla son **Volver** (al listado de
fuentes), **Ejecutar Dry Run** y **Ejecutar Sync**.

No hace falta guardar antes de lanzar el Dry Run o la sincronización, pero sí
tener un mapeo guardado: si no, verás «La fuente no tiene un mapping
configurado.».

---

## 9. Transformaciones

Una transformación limpia el valor que viene del catálogo antes de escribirlo.
El módulo incluye cuatro en la versión gratuita y quince más en la edición de
pago.

| Transformación | Edición | Para qué sirve | Configuración |
| --- | --- | --- | --- |
| **Normalizar precio** | Gratuita | Quita símbolos de moneda y arregla los separadores (`1.234,56`, `1,234.56`, `10,50 €`, `10.50 EUR`). | *Separador decimal* y *Separador de miles*; en blanco se detectan solos. |
| **Normalizar stock** | Gratuita | Convierte textos como `3 unidades` o `más de 3` en un número. | — |
| **Normalizar texto** | Gratuita | Quita espacios sobrantes. Para nombres, descripciones, marcas y categorías. | — |
| **Reemplazar texto** | Gratuita | Cambia un texto por otro; en blanco, lo quita. | *Buscar* y *Reemplazar por*. |
| **Traducir valores** | Pago | Tabla de equivalencias, una regla por línea con `origen=destino` (por ejemplo `En stock=7`). | *Tabla de valores*. |
| **Valor por defecto** | Pago | Usa un valor cuando el proveedor no envía nada. | *Valor por defecto*. |
| **Usar otro campo** | Pago | Si el campo viene vacío, usa otro de la misma fila. | *Campos alternativos*, por orden de preferencia. |
| **Unir campos** | Pago | Junta varios campos (por ejemplo `marca` + `nombre`, o varias imágenes). | *Campos a unir* y *Separador*. |
| **Añadir prefijo o sufijo** | Pago | Añade texto delante o detrás (por ejemplo el código del proveedor). | *Prefijo* y *Sufijo*. |
| **Operaciones aritméticas** | Pago | Multiplica, divide, suma o resta, con redondeo. Sirve para precios y stock. | *Operación*, *Valor* y *Decimales*. |
| **Extraer con una expresión** | Pago | Saca una parte del valor con una expresión regular. | *Expresión regular* y *Grupo de captura*. |
| **Reemplazar con una expresión** | Pago | Sustituye un patrón por otro texto. | *Expresión regular* y *Reemplazar por*. |
| **Convertir a sí/no** | Pago | Convierte un texto en `1` o `0`. | *Valores que significan sí*. |
| **Acortar el texto** | Pago | Recorta textos largos y añade puntos suspensivos. | *Longitud máxima* y *Añadir al final*. |
| **Crear un slug** | Pago | Construye un texto tipo URL: minúsculas, sin acentos. | *Separador*. |
| **Cambiar mayúsculas y minúsculas** | Pago | TODO EN MAYÚSCULAS, todo en minúsculas, Cada Palabra Con Mayúscula o Sólo la primera letra. | *Capitalización*. |
| **Quitar el HTML** | Pago | Elimina el marcado y conserva el texto. | — |
| **Tomar una parte** | Pago | Toma una parte de un valor con separador, por ejemplo `Ropa\|Zapatos`. | *Separador* y *Número de parte* (desde 1). |
| **Dejar sólo los números** | Pago | De `123-456` saca `123456`. Para EAN y referencias. | — |

Ejemplos:

| Quiero… | Campo destino | Transformación | Configuración |
| --- | --- | --- | --- |
| Un precio que viene como `1.234,56 €` | `price` | Normalizar precio | (en blanco) |
| Un stock que viene como `más de 3` | `quantity` | Normalizar stock | — |
| Añadir un 21 % de IVA a un precio sin IVA | `price` | Operaciones aritméticas | Operación `Multiplicar`, Valor `1.21` |
| Convertir `En stock` / `Agotado` en stock | `quantity` | Traducir valores | `En stock=10` y `Agotado=0` |
| Un EAN que viene como `123-456-789-012-3` | `ean13` | Dejar sólo los números | — |
| Unir varias imágenes anidadas | `image` | Unir campos | Campos `images.1,images.2`, Separador `,` |

Si la configuración no es válida, el mapeo no se guarda y se explica el motivo
(por ejemplo, «Debes indicar el texto que deseas reemplazar para el campo
"…".»).

---

## 10. Dry Run: ver el resultado antes de tocar nada

El **Dry Run** lee la fuente, aplica el mapeo y las transformaciones y te enseña
el resultado **sin modificar ningún producto** ni dejar rastro en el historial.

Se lanza con **Ejecutar Dry Run** desde la pantalla de mapeo. La pantalla
muestra:

- Un aviso: «Se muestran los primeros 5 productos de la fuente.» y «No se
  modificó ningún producto en PrestaShop.».
- Un panel por cada uno de esos 5 productos, con la etiqueta **Válido** (verde) o
  **Con errores** (rojo). Si tiene errores, aparecen listados.
- Una tabla por producto con tres columnas: **Campo PrestaShop**, **Original**
  (lo que venía en el catálogo) y **Resultado** (lo que se guardaría, en negrita
  y con la etiqueta **Cambiado** cuando la transformación ha modificado el
  valor).

Es la forma más rápida de comprobar un mapeo: si el resultado no te gusta, cambia
la transformación, guarda y vuelve a lanzarlo. Al pie hay un botón **Volver al
mapping**.

---

## 11. Sincronizar

### 11.1 Sincronización manual

Pulsa **Ejecutar Sync** en la pantalla de mapeo. El módulo recorre el catálogo
entero, aplica el mapeo y crea o actualiza los productos. Al terminar verás el
**Resultado de sincronización**:

- **Sincronización completada**, y si se ha guardado en el historial, **Ejecución
  #N** con la nota «Esta sincronización quedó registrada en el historial.».
- Cinco contadores:

| Contador | Qué significa |
| --- | --- |
| **Total** | Productos leídos del catálogo. |
| **Creados** | Productos nuevos en PrestaShop. |
| **Actualizados** | Productos que ya existían y han cambiado. |
| **Omitido** | Productos que ya existían y no necesitaban cambios, o que se han saltado por la opción *Crear sólo productos nuevos*. |
| **Errores** | Productos que no se han podido procesar. |

- Una tabla con una fila por producto: **Referencia**, **Estado**, **ID
  PrestaShop** y **Detalle**. En **Detalle** se explica cada error, producto a
  producto; la sincronización **no se detiene** por un producto que falle.
- Un botón **Volver al mapping** al pie.

La sincronización manual procesa el catálogo completo en una sola petición, así
que no tiene tope de productos, pero sí depende del tiempo máximo de ejecución
de PHP y de que no se cierre la pestaña. Para catálogos muy grandes es mejor el
cron (va por lotes) o la importación de archivo, que también avanza por lotes y
muestra el progreso.

### 11.2 Importar un archivo

Si tu proveedor te manda el catálogo por correo o lo descargas a mano, usa
**Importar CSV** en la pantalla de fuentes:

1. Elige la **Fuente** (el mapeo que se aplicará).
2. Elige el **Archivo CSV**.
3. Pulsa **Subir e iniciar importación**.

El módulo sube el archivo, cuenta los registros y lo procesa **por lotes**,
mostrando el progreso: «Procesando importación», la barra con el porcentaje, el
contador `procesados / total`, los **Exitosos** y los **Errores** acumulados. Al
terminar: «Importación completada correctamente.» o «La importación terminó con
errores.».

Detalles útiles:

- Con la edición de pago se admiten también `.xml` y `.json`; sin ella, `.csv`.
- No cierres la pestaña mientras avanza la barra: la importación se hace por
  llamadas sucesivas desde el navegador.
- El archivo temporal se borra del servidor al terminar.

### 11.3 Catálogos grandes

El módulo está pensado para catálogos de miles de productos:

- Los productos **sin cambios se detectan y se omiten**, así que una
  resincronización de un catálogo que no ha cambiado es muy rápida y no reescribe
  nada.
- Cada lectura va por lotes (50 registros en CSV, 200 en XML y JSON, 100 en
  REST).
- Las imágenes de un lote se preparan antes de procesarlo, y con la edición de
  pago se descargan en paralelo (varias a la vez).

---

## 12. Programación y cron

La frecuencia de una fuente dice cada cuánto le toca ejecutarse. Para que eso
ocurra hay que programar el comando del cron en el servidor: el módulo no tiene
un «planificador» propio.

### 12.1 Frecuencias

| Frecuencia | Edición |
| --- | --- |
| Manual | Gratuita |
| Cada hora | Gratuita |
| Cada 6 horas | Gratuita |
| Diariamente | Gratuita |
| Cada 15 minutos | Pago |
| Cada 30 minutos | Pago |
| Cada 12 horas | Pago |
| Todos los días a una hora fija | Pago |
| Una vez a la semana | Pago |
| Una vez al mes | Pago |

Las frecuencias de calendario piden sus datos en el propio formulario: la hora
(en formato `HH:MM`), el día de la semana o el día del mes. Si el mes es más
corto que el día elegido, se usa el último día.

### 12.2 El comando

El script **sólo funciona desde la línea de comandos**: si se llama por HTTP
devuelve `403 Forbidden`. No hay URL de cron ni token de seguridad, y no hace
falta: se ejecuta dentro del servidor.

```bash
php /ruta/a/prestashop/modules/cpbsync/cron.php
```

Una línea de `crontab` para ejecutarlo cada hora:

```bash
0 * * * * php /ruta/a/prestashop/modules/cpbsync/cron.php
```

Puedes programarlo **más a menudo** de lo que necesitas (por ejemplo cada 5
minutos): cada fuente sólo se ejecuta cuando le toca según su frecuencia. Si el
cron no se ejecuta, no hay sincronización automática: la lista de fuentes te
dirá cuándo le tocaría a cada una.

La salida del comando es un resumen por fuente:

```text
Source 3: total=120 created=4 updated=2 skipped=114 errors=0
```

Si ya hay una ejecución en marcha, el comando avisa («CPB Sync cron is already
running.») y termina sin hacer nada: dos crons simultáneos no se pisan.

### 12.3 Cuándo le toca a cada fuente

- Las frecuencias por intervalo (*Cada hora*, *Cada 6 horas*, *Cada 15
  minutos*…) cuentan desde la **última ejecución por cron** de esa fuente. Una
  sincronización a mano **no** reinicia el contador.
- Las frecuencias de calendario (*Todos los días a una hora fija*, *Una vez a la
  semana*, *Una vez al mes*) se ejecutan **una vez por periodo**. Si el cron está
  caído dos días, no se recuperan las ejecuciones que faltaron: no se acumulan.
- La hora se interpreta en la **zona horaria de la tienda**, no en la del
  servidor.
- Sólo se ejecutan las fuentes **activas** y con una frecuencia distinta de
  *Manual*.

---

## 13. Historial

El botón **Historial** abre la lista de las **50 últimas ejecuciones**:

| Columna | Qué muestra |
| --- | --- |
| **Fecha** | Cuándo se ejecutó. |
| **Fuente** | Qué fuente, o «Fuente eliminada» si ya no existe. |
| **Total** | Productos leídos. |
| **Creados** | Productos nuevos. |
| **Actualizados** | Productos modificados. |
| **Sin cambios** | Productos omitidos. |
| **Errores** | Productos con error. |
| **Duración** | Cuánto tardó, en segundos. |
| **Estado** | **Éxito** (sin errores), **Advertencia** (algunos errores) o **Error** (todos fallaron). |
| **Ver detalle** | Abre el detalle de esa ejecución. |

El detalle (**Detalle de sincronización**) muestra la fecha, la fuente, la
**duración**, la **memoria** máxima usada y el desglose por fases —**Leyendo la
fuente**, **Aplicando el mapeo** y **Guardando los productos**—, los cinco
contadores y la tabla de **Productos procesados** con la referencia, el estado,
el ID y los errores de cada uno. Al pie, **Volver al historial**.

Dos cosas que conviene saber:

- El historial guarda el resumen de cada ejecución y una **muestra de 200
  productos**. Si el catálogo es más grande, la propia pantalla lo avisa: «Sólo
  se guardan los primeros 200 registros de N. El resto queda resumido en los
  contadores anteriores.».
- El **Dry Run no aparece** en el historial, porque no modifica nada.

---

## 14. Monitorización (edición de pago)

El botón **Monitorización** resume la actividad de la tienda en un periodo:

- Periodos: **Últimas 24 horas**, **Últimos 7 días** (por defecto), **Últimos 30
  días** y **Últimos 90 días**.
- Contadores del periodo: **Ejecuciones**, **Productos procesados**, **Errores**,
  **Duración media**, **Creados**, **Actualizados**, **Sin cambios** y
  **Ejecución más larga**, con la fecha de la **Última ejecución** (o «Nunca»).
- **Errores más frecuentes**: los cinco mensajes que más se repiten, con el
  número de **Veces**. Se calculan sobre las últimas ejecuciones con errores, no
  sólo sobre el periodo elegido.
- **Almacenamiento del historial**: cuántas ejecuciones hay guardadas y cuánto
  ocupan. El botón **Borrar ejecuciones antiguas** elimina las anteriores al
  periodo de retención (30 días) tras confirmar.
- **Últimas ejecuciones**: las 20 más recientes, con la columna **Origen** que
  distingue las ejecuciones `manual` de las de `cron`, y un botón **Detalle**.

El historial se mantiene pequeño a propósito: guarda el resumen de cada
ejecución y una muestra de productos, así que un cron cada hora con un catálogo
grande no llena la base de datos.

---

## 15. Cómo se comporta la sincronización

Esta sección resume lo que hace el módulo con cada producto, para que no haya
sorpresas.

**Productos nuevos.** Se crean con la referencia y el nombre que vengan del
catálogo, y quedan **activos**. Si falta la referencia o el nombre, el producto
no se crea y se informa del error.

**Productos existentes.** El módulo busca el producto por su **referencia**. Si
no ha cambiado ninguno de los campos mapeados (nombre, descripción, precio,
stock, EAN, fabricante y categoría), el producto se cuenta como **Sin cambios** y
no se toca: es lo que hace rápida una resincronización.

**Stock.** Si mapeas `quantity`, la cantidad de la tienda se sobrescribe con la
del catálogo cuando cambia. Si no quieres que se toque, marca **No sincronizar
el stock**.

**Precio.** Se guarda tal y como lo escribe PrestaShop (sin IVA). Si tu catálogo
trae el precio con IVA o con símbolo de moneda, usa *Normalizar precio* y, si
hace falta, *Operaciones aritméticas*.

**Categorías y fabricantes.** Se buscan **por nombre** en el idioma por defecto
de la tienda y, si no existen, se crean. Un producto puede tener una categoría y
un fabricante asignados por el catálogo; las categorías que ya tuviera el
producto se conservan.

**Imágenes.** El campo de imagen admite **varias URLs** en un mismo valor,
separadas por comas, punto y coma, barras verticales o saltos de línea, hasta 20
por producto. Sólo la **primera** queda como portada. El módulo:

- Descarga las imágenes nuevas **antes** de borrar las anteriores: si el
  proveedor no responde, el producto conserva las que tenía.
- Sustituye todas las imágenes del producto cuando la URL del catálogo cambia.
- Un enlace roto no impide el resto: si al menos una imagen se descarga, el
  producto se guarda.
- Admite JPG, PNG, GIF y WebP de hasta 5 MB por imagen. Sigue hasta 3
  redirecciones (los CDN suelen usarlas).
- No descarga imágenes de direcciones privadas, por seguridad.

**Referencia y URL amable.** La referencia es la clave: si la cambias en el
catálogo, el módulo creará un producto nuevo en lugar de actualizar el anterior.
La URL amable se genera del nombre sólo cuando falta.

---

## 16. Recetas

**Sólo quiero actualizar precios y stock.** Mapea únicamente `reference`, `price`
y `quantity`. Marca **No importar imágenes** si no quieres que el módulo toque
las fotos, y deja los demás campos sin mapear: lo que no se mapea, no se escribe.

**Tengo las descripciones escritas a mano y no quiero perderlas.** Marca
**Rellenar sólo los campos vacíos**: el módulo sólo escribirá donde no haya nada.
Es también la opción para no pisar los precios que revisas a mano.

**El proveedor cambia los precios cada día y quiero controlarlos.** No mapees
`price` y mapea todo lo demás; o marca **Rellenar sólo los campos vacíos** para
que el precio que ya tienes se respete.

**Quiero dar de alta sólo los productos nuevos.** Marca **Crear sólo productos
nuevos**: los que ya existen se dejan como están.

**Los nombres de las columnas no coinciden con los de PrestaShop.** Usa las
transformaciones y el mapeo: por ejemplo *Traducir valores* para pasar
`En stock` a `10`, o *Unir campos* para juntar marca y nombre.

**Tengo un catálogo grande y sólo quiero que se ejecute de noche.** Pon la
frecuencia *Todos los días a una hora fija* (edición de pago) a las 03:00 y
programa el cron del servidor cada hora o cada 15 minutos. La sincronización del
día siguiente será muy rápida porque los productos sin cambios se omiten.

**Quiero probar un catálogo grande sin arriesgarme.** Abre la fuente, lanza
**Ejecutar Dry Run** y mira los cinco primeros productos: verás el valor original
y el resultado final, campo por campo. Cuando te convenza, lanza **Ejecutar
Sync**.

---

## 17. Problemas frecuentes

| Lo que ves | Qué pasa | Qué hacer |
| --- | --- | --- |
| «No fue posible conectar con la fuente: …» | La URL no responde, tarda demasiado o devuelve un error. | Comprueba la URL en el navegador; si el proveedor pide usuario y contraseña, usa una fuente REST con `auth`. |
| «La fuente no contiene registros.» | El catálogo se ha leído pero no se ha encontrado la lista de productos. | En XML y JSON, revisa `record_path` (por ejemplo `data.products`). Usa **Probar conexión** para ver las columnas que detecta. |
| «La fuente no tiene un mapping configurado.» | Se ha lanzado el Sync o el Dry Run sin guardar el mapeo. | Vuelve a **Mapear campos**, asigna al menos `reference` y pulsa **Guardar mapping**. |
| «Debes asignar un campo del proveedor a reference.» | No hay ninguna columna asignada a `reference`. | Asigna a `reference` la columna que identifica al producto (normalmente el SKU). |
| «El campo de PrestaShop "…" está asignado más de una vez.» | Dos columnas apuntan al mismo campo. | Deja una sola; la otra, en *-- No mapear --*. |
| «El precio no tiene un formato válido.» / «El stock no tiene un formato válido.» | El valor del catálogo trae texto o símbolos que la transformación no ha limpiado. | Añade *Normalizar precio* o *Normalizar stock* a esa columna. |
| «El ean13 field must contain exactly 13 digits.» o un aviso en inglés parecido | El EAN no tiene 13 dígitos después de la transformación. | Usa *Dejar sólo los números*; si el proveedor no da EAN válido, deja esa columna sin mapear. |
| Un producto aparece con estado **Error** y su detalle explica el motivo | El producto no ha pasado la validación (falta el nombre, el precio no es un número, la cantidad no es un entero…) o PrestaShop no ha podido guardarlo. | Corrige el dato en el origen o con una transformación. Los demás productos sí se han procesado. |
| «No fue posible descargar la imagen.» | El enlace de la imagen no responde o el servidor la bloquea. | Comprueba el enlace; si son imágenes de un CDN, revisa que sean `https` y accesibles desde el servidor. Un enlace roto no impide las demás imágenes. |
| «La imagen supera el tamaño máximo permitido de 5 MB.» | La imagen es demasiado grande. | Usa una versión más pequeña; conviene que las fotos de catálogo no pasen de 1-2 MB. |
| Las imágenes del producto no se actualizan | La URL de la imagen no ha cambiado, o la opción **No importar imágenes** está marcada. | Revisa la opción en la fuente y que el catálogo traiga otra URL. |
| El stock no cambia | Está marcada la opción **No sincronizar el stock**, o `quantity` no está mapeado. | Revisa la opción y el mapeo. |
| «La fuente se ha resincronizado y no ha cambiado nada» | Es lo esperado: los productos sin cambios se omiten. | Si esperabas cambios, comprueba que el catálogo los trae y que están mapeados. |
| Ninguna página del back office carga y aparece un error de caché | La caché quedó con propietario `root` por haber ejecutado comandos de consola como root. | Devuelve la propiedad a `www-data`: `chown -R www-data:www-data var/` y vacía la caché. |
| «The file type is not allowed.» | El archivo importado no es de un tipo admitido. | Usa `.csv` (o `.xml`/`.json` con la edición de pago). |
| El cron no ejecuta nada | La fuente está inactiva, su frecuencia es *Manual*, o el comando no está programado. | Activa la fuente, elige una frecuencia y comprueba la línea de `crontab`. Recuerda que el script sólo funciona por línea de comandos. |

---

## 18. Preguntas frecuentes

**¿Se borran los productos que desaparecen del catálogo?**
No. CPB Sync crea y actualiza, nunca borra productos. Si un producto deja de
venir en el catálogo, se queda en la tienda tal y como estaba.

**¿Puedo mapear dos veces la misma columna?**
No al mismo destino: cada campo de PrestaShop admite una sola columna, y si dos
apuntan al mismo sitio el mapeo no se guarda. Lo que sí puedes hacer es mapear la
misma columna del catálogo a dos campos distintos (por ejemplo, una columna
`nombre_completo` a `name` y a `manufacturer`).

**¿Se pueden crear productos en varios idiomas?**
No. Los textos se escriben en el idioma por defecto de la tienda.

**¿Y varios idiomas de catálogo o varias tiendas?**
El módulo no distingue tiendas: los ajustes son de la instalación. Sólo la
portada de las imágenes es por tienda, como en PrestaShop.

**¿Qué pasa si el catálogo repite una referencia?**
Se procesa la última fila que se lea para esa referencia.

**¿Cuánto tarda una sincronización?**
Depende del catálogo y del proveedor de las imágenes. Como referencia medida en
PrestaShop 9.0.0 con 200 productos: crear productos ~110 ms cada uno; una
resincronización sin cambios ~0,5 ms por producto; descargar una imagen pública
~0,5 s (menos con la descarga en paralelo de la edición de pago).

**¿Puedo lanzar la sincronización mientras el cron está ejecutándose?**
Sí, la ejecución manual no espera al cron, pero conviene no hacerlo: dos
sincronizaciones a la vez sobre los mismos productos trabajan por duplicado (el
cron sí se protege de sí mismo, no de una ejecución manual).

**¿El módulo guarda el catálogo completo?**
No. Guarda la fuente, el mapeo, una huella de los valores sincronizados para
detectar cambios, y el historial (resumen más una muestra de productos).

**¿Puedo editar un producto sincronizado sin que el módulo lo pise?**
Sí: marca **Rellenar sólo los campos vacíos**, que respeta lo que ya hay escrito,
o deja sin mapear los campos que quieras mantener.

---

## 19. Glosario

| Término | Qué es |
| --- | --- |
| **Fuente** | Un catálogo externo y su configuración: URL, tipo, frecuencia y opciones. |
| **Lector** | El componente que entiende cada formato (CSV, XML, JSON, REST). |
| **Mapeo** | La tabla que dice qué columna del catálogo va a qué campo de PrestaShop. |
| **Transformación** | La regla que limpia o convierte un valor antes de guardarlo. |
| **Dry Run** | Simulación: aplica el mapeo y muestra el resultado sin tocar la tienda. |
| **Lote** | Un bloque de registros (50, 100 o 200) que se procesa de una vez. |
| **Huella** | El valor que el módulo guarda de cada campo sincronizado, para saber si ha cambiado. |
| **Portada** | La imagen principal del producto. Sólo la primera imagen importada es portada. |
| **Cron** | El programador del servidor, que ejecuta el comando del módulo cuando le toca. |
| **Referencia** | El identificador del producto en el catálogo (`reference` en PrestaShop). |

---

## 20. Anexos

### Anexo A. Campos de PrestaShop que se pueden mapear

`reference`, `name`, `description`, `price`, `quantity`, `category`,
`manufacturer`, `image`, `ean13`.

Ninguno más. El módulo no escribe peso, precio de coste, impuestos, etiquetas,
combinaciones, campos SEO ni segundas categorías.

### Anexo B. Límites del sistema

| Límite | Valor |
| --- | --- |
| Imágenes por producto | 20 (las URLs de más se ignoran) |
| Tamaño por imagen | 5 MB |
| Formatos de imagen | JPG, PNG, GIF y WebP |
| Redirecciones seguidas (fuente e imágenes) | 3 |
| Registros por lote | 50 en CSV, 200 en XML y JSON, 100 en REST |
| Productos que muestra el Dry Run y la vista previa | 5 |
| Productos guardados en el historial de cada ejecución | 200 |
| Ejecuciones que muestra el historial | 50 |
| Ejecuciones recientes en monitorización | 20 |
| Retención del historial | 30 días |
| Páginas por API REST | 100 por defecto, 1000 como máximo |
| Registros por página en REST | 100 por defecto, 1000 como máximo |
| Tamaño del archivo de importación | Lo fija PHP (`upload_max_filesize`) |

### Anexo C. Estados y contadores

| Donde aparece | Valores |
| --- | --- |
| Estado de la fuente | **Activa**, **Inactiva** |
| Estado de una ejecución | **Éxito**, **Advertencia**, **Error** |
| Estado de un producto | **Creados**, **Actualizados**, **Omitido**, **Errores** |
| Estado de un producto en el Dry Run | **Válido**, **Con errores** |
| Origen de una ejecución | `manual` (a mano o importación), `cron` |

### Anexo D. Mensajes del módulo

Los mensajes que verás con más frecuencia, tal y como se muestran en español:

| Mensaje | Cuándo aparece |
| --- | --- |
| El nombre de la fuente es obligatorio. | Se guarda una fuente sin nombre. |
| La URL de la fuente es obligatoria. / La URL de la fuente no es válida. | Falta la URL o no tiene un formato correcto. |
| La configuración adicional debe ser un JSON válido. | El JSON de `config` está mal escrito. |
| La hora no es válida. Usa el formato HH:MM. | Una frecuencia de calendario con una hora mal puesta. |
| Debes asignar un campo del proveedor a reference. | El mapeo no lleva ninguna columna a `reference`. |
| El campo de PrestaShop "…" está asignado más de una vez. | Dos columnas apuntan al mismo destino. |
| El mapping se guardó correctamente. | El mapeo se ha guardado. |
| La fuente no tiene un mapping configurado. | Sync o Dry Run sin mapeo guardado. |
| La fuente no contiene registros. | El catálogo se ha leído pero no se han encontrado productos. |
| Sincronización completada. | La sincronización ha terminado. |
| Esta sincronización quedó registrada en el historial. | La ejecución se ha guardado, con su número. |
| Sólo se guardan los primeros 200 registros de N. El resto queda resumido en los contadores anteriores. | El catálogo tenía más productos que el límite del historial. |
| Importación completada correctamente. / La importación terminó con errores. | Final de una importación de archivo. |
| El tipo de archivo no está permitido. | Se ha subido un archivo de un tipo no admitido. |

Los errores de validación de un producto concreto (referencia o nombre vacíos,
precio que no es un número, cantidad que no es entera, EAN que no tiene 13
dígitos, imagen que no es una URL) los escribe el validador del producto y hoy
llegan **sin traducir**, en inglés, tanto en el resultado de la sincronización
como en el detalle del historial. Se corrigen con el mapeo o con una
transformación; el mensaje es el mismo en los dos idiomas a efectos prácticos:
indica el campo del producto que hay que arreglar.

---

*CPB Sync — Simplifica la sincronización de tu catálogo en PrestaShop.*
