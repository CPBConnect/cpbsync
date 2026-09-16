# Changelog

All notable changes to CPB Sync are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project follows Semantic Versioning.

## [1.0.0] - 2026-09-15

### Added

* CSV catalog sources.
* Multiple configurable data sources.
* Configurable source-to-PrestaShop field mapping.
* Data transformations:

  * Price normalization.
  * Stock normalization.
  * Text normalization.
  * Text replacement.
* Dry Run before synchronization.
* Product creation.
* Product updates.
* Automatic detection of products without changes.
* Product validation.
* Individual product error handling.
* Synchronization history.
* Detailed synchronization results.
* Batch processing for large imports.
* Import progress tracking.
* Scheduled synchronization through cron.
* Cron frequencies:

  * Manual.
  * Hourly.
  * Every 6 hours.
  * Daily.
* Cron execution lock to prevent concurrent executions.
* Resumption of pending batch imports.
* Automatic cleanup of temporary CSV files after successful imports.
* English and Spanish documentation.

### Changed

* Manual and scheduled synchronization now use the same batch processing engine.
* Large CSV imports are processed in batches to reduce server execution-time issues.
* Synchronization errors are handled at the individual product level so other products can continue processing.

### Security

* Cron execution is restricted to the command line interface.

## [Unreleased]

### Added

* `Presentation/Admin` layer with one handler per back office area (sources, mapping, Dry Run and synchronization, history, import), a central action router and an admin URL builder.
* Focused application services: `SourceService`, `SourceValidator`, `MappingInputValidator`, `MappingSaver`, `SourceSyncService`, `SyncHistoryService` and `ImportService`.
* `ModuleAdminShell`, the adapter between the presentation layer and PrestaShop.
* Test suite runnable without a PrestaShop installation, through `php tests/run.php` or `composer test`.
* XLIFF translation catalogues for English (`en-US`) and Spanish (`es-ES`) in the `Modules.Cpbsync.Admin` domain.
* `isUsingNewTranslationSystem()` so the module opts into the new PrestaShop translation interface.
* `translations/translations-to-do.csv` as a human-readable glossary of every wording and its Spanish translation.
* `tools/check-translations.php` to verify that no wording is missing from the catalogues (`composer check-translations`).
* Source reader extension API (`SourceReaderInterface`, `AbstractSourceReader`, `SourceReaderRegistry`, `CsvReader`) so new source types can be added without duplicating the synchronization engine.
* `SourceReaderFactory`, the single place where an installed package registers the readers it ships.
* `config` column on `cpbsync_source`, with the `upgrade-1.1.0.php` migration, for source-specific settings such as the record path of XML or JSON catalogs.
* Manual import now accepts the file extensions declared by the registered readers.
* `HttpSourceReader::request()` accepts a method, headers, query parameters and body, so authenticated APIs can be reached without duplicating the URL validation.
* Source readers declare their own batch size, so a paginated source only fetches the pages a batch needs.
* `ProductStateInterface` with a default implementation, so the synchronization engine can resolve and compare existing products through a replaceable strategy.
* `ProductStateRepository`, which resolves many product references and stored values in a single query.
* `HttpFetcher`, which centralizes the HTTP mechanics (redirects, headers, query parameters) shared by sources and images.
* `ProductImageProviderInterface` with a default implementation, so the image download can be replaced without touching the synchronization engine.

### Fixed

* The module did not follow HTTP redirects, so a source answering 301/302/307/308 was reported as "the source is empty". Redirects are now followed (up to three hops) and every hop is validated with the same rules as the original URL.
* Images behind an HTTP redirect could not be downloaded, because the image downloader had its own HTTP code that ignored redirects. It now shares `HttpFetcher`.
* Thumbnail generation passed an extra argument to `ImageManager::resize()` that the method does not accept, raising a PHP warning for every image type and every product. In development mode that warning text could be injected into the AJAX responses of the import.
* `DatabaseInstaller` was missing its `use Db;` import, so the module could not be installed at all.
* `install()` now creates the database tables before registering the module and rolls back if registration fails, so a failed installation no longer leaves the module marked as installed without its tables.
* `tools/check-translations.php` no longer scans the generated `build/dist/` folder.
* An empty list at `record_path` means "no records" instead of an error.

### Changed

* `SourceValidator`, `SourceService`, `CronRunner` and `ImportBatchProcessor` no longer assume CSV: they resolve the reader from the registry.
* The source form lists every available source type instead of a single hardcoded option.
* `ProductSync` no longer loads each existing product by itself: it asks the product state, which may answer for the whole batch at once.
* `cpbsync.php` now only keeps the module lifecycle and delegates every `cpbsync_action` to the presentation layer.
* Source reading and mapping loading are no longer duplicated between Dry Run and synchronization.
* Mapping form validation and mapping persistence are separated from the controller code.
* Translation and notification now use the public PrestaShop API, since `Module::trans()` and `Module::$context` are `protected`.
* All wordings are written in English and translated through the new translation system; Spanish ships as a translation.
* Back office templates use `{l s='...' d='Modules.Cpbsync.Admin'}` instead of the classic `mod='cpbsync'` syntax.
* Everything shown in the back office is now translatable: source form validation errors, import JSON responses, JavaScript messages, product validation errors in the Dry Run, synchronization results and history details.

### Fixed

* `history.tpl` and `history-detail.tpl` contained hardcoded text with no translation tags; every wording is now wrapped in `{l}`.
* Missing `</strong>` closing tag in `sync-result.tpl`.
* Product image downloader reported a stale Spanish wording.

### Removed

* Dead code in `cpbsync.php`: the unused `createImport()` and `renderImportProgress()` methods.
* Development scratch file `src/Infrastructure/Source/test-source.php`.
* The unused `module_name` Smarty variable assigned by the sources page.

[1.0.0]: https://github.com/CPBConnect/cpbsync/releases/tag/v1.0.0
