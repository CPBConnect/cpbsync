# Changelog

All notable changes to CPB Sync are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows Semantic Versioning.

## [1.0.0] - 2026-09-10

### Added

* Initial public release of CPB Sync.
* CSV external source support.
* Configurable product source management.
* Source creation, editing, and deletion.
* Configurable field mapping between external catalogs and PrestaShop.
* Product field validation.
* Data transformations:

    * Price normalization.
    * Stock normalization.
    * Text normalization.
    * Text replacement.
* Dry Run functionality to preview product changes before synchronization.
* Product creation and update synchronization.
* Product change detection to skip products without changes.
* Product image synchronization.
* Product synchronization history.
* Detailed synchronization results.
* Error handling per product during synchronization.
* Scheduled synchronization through cron.
* Support for manual, hourly, every 6 hours, and daily synchronization frequencies.
* Cron execution lock to prevent concurrent executions.
* Separate application, domain, and infrastructure layers.
* Composer PSR-4 autoloading.
* Basic source and product data validation.
* PrestaShop Back Office integration.

### Documentation

* Added English README.
* Added Spanish README.
* Added project documentation and installation instructions.
* Added initial project roadmap.

### Notes

CPB Sync 1.0.0 is the first public release and is provided free of charge.

The initial release focuses on CSV-based product synchronization and establishes the foundation for future integrations and synchronization capabilities.

[1.0.0]: https://github.com/CPBConnect/cpbsync/releases/tag/v1.0.0
