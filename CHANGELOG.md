# Changelog

All notable changes to CPB Sync are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project follows Semantic Versioning.

## [Unreleased]

### Added

* Scheduling registry (`ScheduleInterface`, `AbstractSchedule`, `IntervalSchedule`, `ScheduleRegistry`, `ScheduleFactory`): a frequency only declares its name, its label, the fields it needs (a time, a weekday, a day of the month) and when the source is due, so the cron no longer carries a hardcoded list of frequencies.
* `schedule` column on `cpbsync_source`, with the `upgrade-1.3.0.php` migration, for the calendar settings of a source.
* The paid edition adds six frequencies on top of the four basic ones: every 15 minutes, every 30 minutes, every 12 hours, every day at a fixed time, once a week (choosing the day) and once a month (choosing the day).
* The source list shows the translated frequency and when the next run is due, resolved for the whole list in a single query.
* Calendar times are read in the shop's time zone, not in the server's.
* Synchronization options, chosen per source and stored in the new `options` column, with the `upgrade-1.4.0.php` migration: **only create new products**, **only fill empty fields**, **do not synchronise stock** and **do not import images**.
* Sync option registry (`SyncOptions`, `SyncOptionRegistry`, `SyncOptionFactory`): an option declares its name, its label and its hint, and the source form builds the checkboxes from that registry, the same way the mapping and the scheduling forms do. An additional edition can register more options in it.
* `fill_empty` only touches the reference, the name, the description, the price and the EAN: the stock keeps its own option, because an empty stock in a catalog usually means "sold out" and writing it would empty the shop's quantity.
* User manual in Spanish and English (`docs/manual.es.md`, `docs/manual.en.md`): a step-by-step guide for the merchant, from the installation and the first source to the mapping, the transformations, the synchronization options, the cron, the history and the everyday problems, with the exact wording of every screen, the limits of the system and the messages the module shows. It ships inside both packages.

### Changed

* The source form builds the frequency list and its calendar fields from that registry: choosing a frequency shows only the fields it needs, without reloading the page.
* Source validation asks the chosen schedule to check its own settings, so an invalid time, weekday or day of the month is reported before saving.
* The fields a functionality declares (name, label, hint, type, default value and options) are now read, saved and translated through a single helper (`DescribedFields`) shared by the transformation, the scheduling and the synchronization option forms.
* Synchronizing a source that has no options selected behaves exactly as before: the four options default off, so an existing source keeps updating every field, the stock and the images.

### Fixed

* The batch import failed with a fatal error (`Class "CPBConnect\Application\Import\SyncOptions" not found`) as soon as the first batch was processed: `ImportBatchProcessor` used the class without importing it. It broke the file import from the back office and the scheduled import alike, in both editions. The test suite now runs the real `ImportBatchProcessor` (the doubles overrode `process()`, so the broken line never executed) and `build/e2e/import-e2e.php` imports a 55-product catalog in two batches against a real PrestaShop.
* The Spanish catalogue repeated the English words in four wordings: «No se puede crear un producto sin name.» and «...sin reference.» now say «sin nombre» and «sin referencia», and the two failures of the source and mapping inserts are translated instead of showing the English text.

## [1.2.0] - 2026-09-16

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
* `SyncMetrics`, which measures how long a run takes, its peak memory and the time spent in each phase (reading the source, applying the mapping and writing products).
* Every synchronization run stores its duration, peak memory, phase breakdown and total number of processed products, with the `upgrade-1.2.0.php` migration.
* Duration column in the history list, and duration, memory, phase breakdown and a truncation notice in the run detail.
* The paid edition adds a monitoring dashboard with a period selector, summary counters, the most frequent errors, the size of the stored history and a retention purge.
* `AdminActionsInterface`, the extension point the main router consults before treating an unknown action as non-existent.
* Transformation registry (`TransformerInterface`, `AbstractTransformer`, `TransformerRegistry`, `TransformerFactory`): a transformation only declares its name, its label, the fields it needs and how it converts a value, and the synchronization engine no longer carries a hardcoded list of transformations.
* The mapping form builds the transformation list and its configuration fields from that registry, and offers each field only the transformations that apply to it, without reloading the page.
* The paid edition adds fifteen transformations on top of the four basic ones: value maps, default values, fallback fields, joining fields, prefixes and suffixes, arithmetic with rounding, regular expression extraction and replacement, yes/no conversion, shortening, slugs, capitalisation, HTML removal, taking one part of a value, and keeping only digits.
* Nested catalog records are flattened into dot paths, so every value can be mapped: `{"price":{"value":10.5}}` becomes `price.value`, a list becomes `categories.0.id`, and an XML element becomes `price.value` or `attrs.color`. A CommerceML catalog now exposes its price and its attributes instead of three usable columns with the rest buried in an XML blob.
* Repeated values (several images, several attributes) are numbered from zero and the first one is also kept without a number, so mapping `image` works whether the supplier sends one image or five.
* An XML element that has both children and its own text keeps its markup (`<description>Texto <b>con</b> marcado</description>`), because that is how HTML descriptions arrive.
* The mapping form suggests the PrestaShop field for the usual column names, including nested ones (`price.value` → price, `images.0` → image) and the Spanish equivalents (`precio`, `marca`, `categoría`). It only suggests, and only while the source has no mapping saved, so saving never maps a field the user did not choose.
* A product can now import several images. The image field is read as a list (separated by commas, semicolons, pipes or line breaks, up to 20 per product), a single URL with commas in its parameters is not split, and every image is downloaded and thumbnailed. Nested catalogs expose `images.0`, `images.1`… and the paid edition can join them with the "Join fields" transformation.
* Only the first image of a product is the cover, as PrestaShop does.

### Fixed

* The module did not follow HTTP redirects, so a source answering 301/302/307/308 was reported as "the source is empty". Redirects are now followed (up to three hops) and every hop is validated with the same rules as the original URL.
* Images behind an HTTP redirect could not be downloaded, because the image downloader had its own HTTP code that ignored redirects. It now shares `HttpFetcher`.
* Thumbnail generation passed an extra argument to `ImageManager::resize()` that the method does not accept, raising a PHP warning for every image type and every product. In development mode that warning text could be injected into the AJAX responses of the import.
* `DatabaseInstaller` was missing its `use Db;` import, so the module could not be installed at all.
* `install()` now creates the database tables before registering the module and rolls back if registration fails, so a failed installation no longer leaves the module marked as installed without its tables.
* `tools/check-translations.php` no longer scans the generated `build/dist/` folder.
* An empty list at `record_path` means "no records" instead of an error.
* The thumbnail error message was cut in the catalogues (`The thumbnail "`), so it could never match the message thrown at runtime. The message is now complete and translatable: `The thumbnail could not be generated.`
* The phase breakdown of a run (reading the source, applying the mapping, writing products) was never translated: the wordings were looked up from an array, where the translation check could not see them. A run of a few milliseconds also showed every phase as `0 s`, so phases now use three decimals.
* Text normalization was applied in the Dry Run but not in the real synchronization: the transformation dispatch was written twice, in `MappingApplier::apply()` and `MappingApplier::applyWithOriginals()`, and the first one had no case for it. Both paths now share a single dispatch, so the Dry Run can no longer promise a result that the synchronization does not produce.
* Price normalization rejected any price carrying a currency symbol or letters (`10,50 €`, `10.50 EUR`), which is how most supplier catalogs send them, so the whole product failed. It now strips anything that is not part of the number and detects whether the decimal separator is the comma or the dot (the last one wins when both appear), with `decimal_separator` and `thousands_separator` settings for catalogs that need them spelled out.
* Creating a category from a source always failed with `Call to undefined method Tools::link_rewrite()`, which does not exist in PrestaShop 8 and 9, so any product mapped to a new category was reported as an error (and left half created). It now uses `Tools::str2url()`.
* Synced products were created without `link_rewrite`, so they had no friendly URL: PrestaShop does not generate it when the product is created from code. It is now built from the product name, and only when it is missing, so URLs that the shop has already customised are left alone.
* Importing a second image for the same product failed with `Duplicate entry ... for key 'id_product'`: `image_shop` keeps one row per image and shop with a unique index on (product, shop, cover), and PrestaShop stores `NULL` —not `0`— for the images that are not the cover. Every image tried to be the cover, so the product ended up with one image and an error.
* Replacing an image from cron (or any command line run) aborted the product with `Call to a member function get() on null`: `Image::delete()` reads the watermark setting from the Symfony container, which only exists inside the back office, so the deletion was left half done with the thumbnails still on disk. The module now deletes the rows and the files itself when PrestaShop's own deletion is not available, and renumbers the positions like PrestaShop does.
* `history.tpl` and `history-detail.tpl` contained hardcoded text with no translation tags; every wording is now wrapped in `{l}`.
* Missing `</strong>` closing tag in `sync-result.tpl`.
* Product image downloader reported a stale Spanish wording.

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
* The synchronization history keeps the summary of every run plus a limited sample of the processed products (200 by default) instead of every one of them, so an hourly cron on a large catalog no longer grows the database without bound: 50,000 products used to add about 4.3 MB per run, roughly 103 MB a day.
* `tools/check-translations.php` also extracts wordings that carry `sprintf=`/`js=` parameters and messages thrown from `switch` branches, which were invisible to it before.
* The transformation configuration is now generic (`transformation_config[field][option]`), so a new transformation needs no change in the validator, the saver, the handler or the template. Existing mappings keep working: `replace_text` still stores `search` and `replace`.
* `tools/check-translations.php` also reads the labels and hints declared by the transformation fields.

### Removed

* Dead code in `cpbsync.php`: the unused `createImport()` and `renderImportProgress()` methods.
* Development scratch file `src/Infrastructure/Source/test-source.php`.
* The unused `module_name` Smarty variable assigned by the sources page.

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

[1.0.0]: https://github.com/CPBConnect/cpbsync/releases/tag/v1.0.0
[1.2.0]: https://github.com/CPBConnect/cpbsync/releases/tag/v1.2.0
