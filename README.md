# CPB Sync

**Product synchronization for PrestaShop using external catalogs, configurable field mappings, and data transformations.**

CPB Sync is a PrestaShop module developed by **CPBConnect** that allows store administrators to synchronize product information from external sources with their PrestaShop catalog.

The first version focuses on CSV-based synchronization, configurable mappings, basic transformations, dry runs, synchronization history, and scheduled execution.

## Features

* 📥 Import product catalogs from CSV sources
* 🔗 Configurable source-to-PrestaShop field mapping
* 🔄 Data transformations
* 🧪 Dry Run before applying changes
* 📦 Product creation and updates
* 🖼️ Product image synchronization
* 📊 Synchronization results and history
* ⏰ Scheduled synchronization through cron
* ✅ Product data validation
* 🗂️ Multiple configurable data sources

## Supported source

### CSV

CPB Sync currently supports CSV catalogs.

Example:

```csv
sku,name,description,price,stock,category,brand,image,ean
ABC001,Product One,Product description,25.99,10,Category A,Brand A,https://example.com/image.jpg,1234567890123
ABC002,Product Two,Another description,49.99,5,Category B,Brand B,https://example.com/image2.jpg,1234567890124
```

The CSV fields can be mapped to supported PrestaShop product fields.

## Mapping

CPB Sync allows administrators to configure how fields from the external catalog are mapped to PrestaShop.

Example:

| Source field | PrestaShop field |
| ------------ | ---------------- |
| sku          | reference        |
| name         | name             |
| description  | description      |
| price        | price            |
| stock        | quantity         |
| category     | category         |
| brand        | manufacturer     |
| image        | image            |
| ean          | ean13            |

A source field can also have a transformation applied before synchronization.

## Transformations

The current version includes:

* No transformation
* Price normalization
* Stock normalization
* Text normalization
* Text replacement

Transformations allow external catalog data to be adapted before it is sent to PrestaShop.

## Dry Run

Before performing a real synchronization, CPB Sync provides a **Dry Run**.

The Dry Run processes a sample of products and displays the original data alongside the transformed data.

This allows administrators to verify their mapping and transformations before modifying the store catalog.

## Synchronization

After validating the mapping, administrators can execute a synchronization.

CPB Sync can:

* Create new products
* Update existing products
* Skip products without changes
* Report validation or synchronization errors

Synchronization results are stored in the module history.

## Cron

CPB Sync includes scheduled synchronization support through cron.

Supported frequencies include:

* Manual
* Hourly
* Every 6 hours
* Daily

The cron process only executes active sources configured with a scheduled frequency.

Example:

```bash
php modules/cpbsync/cron.php
```

The cron script is intended to be executed from the command line.

## Requirements

* PrestaShop 8.0 or later
* PHP version compatible with the installed PrestaShop version
* MySQL/MariaDB supported by PrestaShop
* Composer dependencies included in the module package

## Installation

1. Download the CPB Sync module.
2. Open the PrestaShop Back Office.
3. Go to **Modules > Module Manager**.
4. Select **Upload a module**.
5. Upload the CPB Sync ZIP file.
6. Install the module.
7. Open the CPB Sync configuration page.

After installation, configure an external CSV source and create the required field mapping.

## Basic workflow

```text
External CSV
     │
     ▼
Source configuration
     │
     ▼
Field mapping
     │
     ▼
Transformations
     │
     ▼
Dry Run
     │
     ▼
Synchronization
     │
     ▼
PrestaShop catalog
```

## Project structure

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

The module follows a layered structure separating application logic, domain logic, infrastructure, and PrestaShop integration.

## Current version

**Version:** 1.0.0

CPB Sync 1.0.0 is the first public version of the project.

This version is focused on providing a reliable foundation for CSV-based product synchronization.

## Roadmap

Future versions may include:

* XML sources
* JSON sources
* REST API integrations
* Incremental synchronization
* Advanced transformation rules
* More synchronization options
* Improved logging
* Additional scheduling options
* Premium features

The roadmap may evolve according to user feedback and real-world requirements.

## Free version

CPB Sync 1.0.0 is provided free of charge.

The project is being developed by CPBConnect with the goal of building a practical synchronization solution for PrestaShop stores.

## Contributing

Suggestions, bug reports, and contributions are welcome.

If you find a problem or have an idea for improving CPB Sync, please open an issue or submit a pull request.

## License

See the `LICENSE` file included in this repository.

## About CPBConnect

CPB Sync is developed by **CPBConnect**, a software project focused on integrations, automation, and tools for e-commerce platforms.

---

**CPB Sync — Simplify your PrestaShop catalog synchronization.**
