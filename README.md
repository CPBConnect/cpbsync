# CPB Sync

**Product synchronization for PrestaShop using external catalogs, configurable field mappings, data transformations, batch processing, and scheduled synchronization.**

CPB Sync is a PrestaShop module developed by **CPBConnect** that allows store administrators to synchronize product information from external catalogs with their PrestaShop store.

Version **1.0.0** focuses on reliable CSV-based product synchronization with configurable mappings, transformations, validation, Dry Run, batch processing, synchronization history, and automated execution through cron.

## ❤️ Support CPB Sync

CPB Sync is free and open source.

If CPB Sync is useful to you, consider supporting its continued development.

[☕ Support CPB Sync via PayPal](https://paypal.me/cpbconnet)

## Features

- 📥 Import product catalogs from CSV sources
- 🔗 Configurable source-to-PrestaShop field mapping
- 🔄 Configurable data transformations
- 🧪 Dry Run before applying changes
- 📦 Product creation and updates
- ⏭️ Automatically skip products without changes
- 🖼️ Product image synchronization
- 📊 Synchronization results and history
- 📈 Batch processing with progress tracking
- ⏰ Scheduled synchronization through cron
- ✅ Product data validation
- ⚠️ Individual product error handling
- 🗂️ Multiple configurable data sources
- 🧹 Automatic cleanup of temporary import files

## Supported sources

### CSV

CPB Sync 1.0.0 currently supports CSV catalogs.

Example:

```csv
sku,name,description,price,stock,category,brand,image,ean
ABC001,Product One,Product description,25.99,10,Category A,Brand A,https://example.com/image.jpg,1234567890123
ABC002,Product Two,Another description,49.99,5,Category B,Brand B,https://example.com/image2.jpg,1234567890124
```

CSV fields can be mapped to supported PrestaShop product fields.

## Mapping

CPB Sync allows administrators to configure how fields from an external catalog are mapped to PrestaShop.

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

- No transformation
- Price normalization
- Stock normalization
- Text normalization
- Text replacement

Transformations allow external catalog data to be adapted before it is synchronized with PrestaShop.

## Dry Run

Before performing a real synchronization, CPB Sync provides a **Dry Run**.

The Dry Run processes products and displays the original data alongside the transformed data.

This allows administrators to verify their mapping and transformations before modifying the store catalog.

## Batch processing

Large CSV catalogs can be processed in batches instead of being handled in a single request.

CPB Sync processes imports in batches of products and keeps track of the current progress.

This helps reduce the risk of server execution-time limitations when importing larger catalogs.

Manual imports provide progress information while the synchronization is running.

Temporary uploaded CSV files are automatically removed after a successful import to avoid unnecessary storage usage.

## Synchronization

After validating the mapping, administrators can execute a synchronization.

CPB Sync can:

- Create new products
- Update existing products
- Skip products without changes
- Validate product data
- Report individual product errors
- Continue processing when individual products fail

Synchronization results are stored in the module history.

Each synchronization records:

- Total products
- Created products
- Updated products
- Skipped products
- Errors
- Detailed synchronization results

## Cron

CPB Sync includes scheduled synchronization support through cron.

Supported frequencies include:

- Manual
- Hourly
- Every 6 hours
- Daily

The cron process only executes active sources configured with a scheduled frequency.

The cron runner uses the same batch processing system as manual imports.

This means manual and scheduled synchronization share the same product synchronization logic.

### Cron command

The cron script is intended to be executed from the command line:

```bash
php modules/cpbsync/cron.php
```

For example, a server cron job can execute CPB Sync every hour:

```bash
0 * * * * php /path/to/prestashop/modules/cpbsync/cron.php
```

The cron process includes a lock mechanism to prevent multiple CPB Sync cron executions from running simultaneously.

## Synchronization workflow

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
Batch processing
     │
     ▼
Product validation
     │
     ▼
Create / Update / Skip
     │
     ▼
Synchronization history
     │
     ▼
PrestaShop catalog
```

Scheduled synchronization follows the same processing flow:

```text
Server Cron
     │
     ▼
CronRunner
     │
     ▼
Active scheduled source
     │
     ▼
ImportBatchProcessor
     │
     ▼
Product synchronization
     │
     ▼
Synchronization history
```

## Requirements

- PrestaShop 8.0 or later
- PHP version compatible with the installed PrestaShop version
- MySQL/MariaDB supported by PrestaShop
- Composer dependencies included in the module package

## Installation

1. Download the CPB Sync module ZIP package.
2. Open the PrestaShop Back Office.
3. Go to **Modules > Module Manager**.
4. Select **Upload a module**.
5. Upload the CPB Sync ZIP file.
6. Install the module.
7. Open the CPB Sync configuration page.

After installation:

1. Create an external CSV source.
2. Configure the source settings.
3. Configure the field mapping.
4. Configure transformations if required.
5. Run a Dry Run.
6. Execute the synchronization.

For automatic synchronization, configure the desired frequency and add the CPB Sync cron command to the server's scheduler.

## Project structure

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
├── tests/
├── tools/
├── translations/
├── views/
├── composer.json
├── composer.lock
├── cpbsync.php
└── cron.php
```

The module follows a layered structure separating application logic, infrastructure, and PrestaShop integration:

- **Application** contains the use cases: sources, mapping, transformations, product synchronization and history.
- **Infrastructure** contains persistence, catalog readers and the PrestaShop adapters.
- **Presentation** contains the back office actions and their routing. `cpbsync.php` only keeps the module lifecycle and delegates every `cpbsync_action` to the presentation layer.

## Tests

The test suite runs without a PrestaShop installation:

```bash
php tests/run.php
```

or, with Composer:

```bash
composer test
```

## Translations

CPB Sync uses the new PrestaShop translation system (translation domains) and does not rely on the classic dictionary files.

- Every wording belongs to the `Modules.Cpbsync.Admin` translation domain.
- PHP code translates through `trans()` / `getTranslator()->trans()`; Smarty templates use `{l s='...' d='Modules.Cpbsync.Admin'}`.
- The module declares `isUsingNewTranslationSystem()`, so it is listed under **International > Translations > Modify translations**.

Translation catalogues ship as XLIFF files:

```text
translations/
├── en-US/
│   └── ModulesCpbsyncAdmin.en-US.xlf
├── es-ES/
│   └── ModulesCpbsyncAdmin.es-ES.xlf
└── translations-to-do.csv
```

Wordings are written in English, and Spanish is provided as a translation.

To add another language, copy one of the XLIFF files to `translations/<locale>/ModulesCpbsyncAdmin.<locale>.xlf`, update `target-language` and translate the `<target>` elements. PrestaShop loads these files during the module installation; after editing them, reinstall the module or clear the cache.

`translations/translations-to-do.csv` is a human-readable glossary listing every wording with its Spanish translation.

To verify that no wording is missing from the catalogues:

```bash
php tools/check-translations.php
```

or, with Composer:

```bash
composer check-translations
```

## Current version

**Version:** 1.0.0

CPB Sync 1.0.0 is the first public release of the project.

This version provides a complete CSV-based product synchronization workflow, including manual imports, batch processing, validation, Dry Run, synchronization history, and scheduled execution through cron.

## Roadmap

Future versions may include:

- XML sources
- JSON sources
- REST API integrations
- Incremental synchronization
- Advanced transformation rules
- Additional synchronization options
- Improved logging and monitoring
- Additional scheduling options
- Premium features

The roadmap may evolve according to user feedback and real-world requirements.

## Free version

CPB Sync 1.0.0 is provided free of charge.

The free version provides the complete CSV synchronization workflow available in the 1.0.0 release.

Future versions may introduce additional premium features while maintaining the functionality included in the free version.

## Contributing

Suggestions, bug reports, and contributions are welcome.

If you find a problem or have an idea for improving CPB Sync, please open an issue or submit a pull request.

## License

See the `LICENSE` file included in this repository.

## About CPBConnect

CPB Sync is developed by **CPBConnect**, a software project focused on integrations, automation, and tools for e-commerce platforms.

---

**CPB Sync — Simplify your PrestaShop catalog synchronization.**
