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

Changes for the next version will be documented here.

[1.0.0]: https://github.com/CPBConnect/cpbsync/releases/tag/v1.0.0
