# CPB Sync

**Sincronización de productos para PrestaShop mediante catálogos externos, mappings configurables y transformaciones de datos.**

CPB Sync es un módulo para PrestaShop desarrollado por **CPBConnect** que permite a los administradores de tiendas sincronizar información de productos desde fuentes externas con su catálogo de PrestaShop.

La primera versión está enfocada en la sincronización mediante archivos CSV, mappings configurables, transformaciones básicas, Dry Run, historial de sincronizaciones y ejecución programada.

## Características

* 📥 Importación de catálogos de productos desde fuentes CSV
* 🔗 Mapping configurable entre campos externos y PrestaShop
* 🔄 Transformaciones de datos
* 🧪 Dry Run antes de aplicar cambios
* 📦 Creación y actualización de productos
* 🖼️ Sincronización de imágenes de productos
* 📊 Resultados e historial de sincronizaciones
* ⏰ Sincronización programada mediante cron
* ✅ Validación de datos de productos
* 🗂️ Múltiples fuentes de datos configurables

## Fuente soportada

### CSV

Actualmente CPB Sync permite utilizar catálogos en formato CSV.

Ejemplo:

```csv
sku,name,description,price,stock,category,brand,image,ean
ABC001,Product One,Product description,25.99,10,Category A,Brand A,https://example.com/image.jpg,1234567890123
ABC002,Product Two,Another description,49.99,5,Category B,Brand B,https://example.com/image2.jpg,1234567890124
```

Los campos del CSV pueden asociarse con los campos de producto soportados por PrestaShop.

## Mapping

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

Cada campo puede tener además una transformación aplicada antes de la sincronización.

## Transformaciones

La versión actual incluye:

* Sin transformación
* Normalización de precios
* Normalización de stock
* Normalización de texto
* Reemplazo de texto

Las transformaciones permiten adaptar los datos del catálogo externo antes de enviarlos a PrestaShop.

## Dry Run

Antes de realizar una sincronización real, CPB Sync proporciona un **Dry Run**.

El Dry Run procesa una muestra de productos y muestra los datos originales junto con los datos transformados.

Esto permite verificar el mapping y las transformaciones antes de modificar el catálogo de la tienda.

## Sincronización

Después de validar el mapping, el administrador puede ejecutar una sincronización.

CPB Sync puede:

* Crear productos nuevos
* Actualizar productos existentes
* Omitir productos que no tengan cambios
* Reportar errores de validación o sincronización

Los resultados de las sincronizaciones quedan registrados en el historial del módulo.

## Cron

CPB Sync incluye soporte para sincronizaciones programadas mediante cron.

Las frecuencias disponibles son:

* Manual
* Cada hora
* Cada 6 horas
* Diaria

El proceso cron solamente ejecuta fuentes activas configuradas con una frecuencia programada.

Ejemplo:

```bash
php modules/cpbsync/cron.php
```

El script cron está diseñado para ejecutarse desde la línea de comandos.

## Requisitos

* PrestaShop 8.0 o superior
* Versión de PHP compatible con la versión instalada de PrestaShop
* MySQL/MariaDB compatible con PrestaShop
* Dependencias de Composer incluidas en el paquete del módulo

## Instalación

1. Descarga el módulo CPB Sync.
2. Abre el Back Office de PrestaShop.
3. Ve a **Módulos > Gestor de módulos**.
4. Selecciona **Subir un módulo**.
5. Sube el archivo ZIP de CPB Sync.
6. Instala el módulo.
7. Abre la página de configuración de CPB Sync.

Después de instalarlo, configura una fuente CSV externa y crea el mapping correspondiente.

## Flujo básico

```text
CSV externo
     │
     ▼
Configuración de fuente
     │
     ▼
Mapping de campos
     │
     ▼
Transformaciones
     │
     ▼
Dry Run
     │
     ▼
Sincronización
     │
     ▼
Catálogo PrestaShop
```

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

Esta versión está enfocada en proporcionar una base sólida para la sincronización de productos mediante CSV.

## Roadmap

Las próximas versiones podrían incluir:

* Fuentes XML
* Fuentes JSON
* Integraciones mediante APIs REST
* Sincronización incremental
* Reglas avanzadas de transformación
* Más opciones de sincronización
* Mejoras en el sistema de logs
* Opciones adicionales de programación
* Funcionalidades premium

El roadmap puede evolucionar según los comentarios de los usuarios y las necesidades reales.

## Versión gratuita

CPB Sync 1.0.0 se distribuye gratuitamente.

El proyecto está siendo desarrollado por CPBConnect con el objetivo de crear una solución práctica de sincronización para tiendas PrestaShop.

## Contribuciones

Las sugerencias, reportes de errores y contribuciones son bienvenidos.

Si encuentras un problema o tienes una idea para mejorar CPB Sync, puedes abrir un issue o enviar un pull request.

## Licencia

Consulta el archivo `LICENSE` incluido en este repositorio.

## Sobre CPBConnect

CPB Sync es desarrollado por **CPBConnect**, un proyecto de software enfocado en integraciones, automatización y herramientas para plataformas de comercio electrónico.

---

**CPB Sync — Simplifica la sincronización de tu catálogo PrestaShop.**
