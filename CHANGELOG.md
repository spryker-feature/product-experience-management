# Changelog

## 5.1.0 - 2026-10-30

### Changed

- `AbstractProductCsvImportStep` implements `Spryker\Zed\DataExchangeExtension\Dependency\Plugin\CacheResettableImportStepInterface` with a no-op `resetCache()`; the 15 product import steps override it and clear their static lookup caches (`sku => id`, locale, store, currency, tax set, price type, stock, shipment type, merchant, category and entity caches) there. The engine calls it after every batch, so the step caches stay bounded per batch and an entity cached as missing in one batch is found again once a later batch created it.
- Requires `spryker/data-exchange-extension` `^0.2.0`.

## 5.0.0 - 2026-10-28

### Changed

- The CSV import/export engine (import jobs, job runs, run errors, the Back Office Import/Export/Template pages, the `import:job:run` console command, the `spy_import_job*` tables and the `ImportJob*` transfers) moved to the new `spryker/data-exchange` module; the plugin contracts moved to `spryker/data-exchange-extension`. This module now ships the product plugin only: `ProductCsvImportSchemaPlugin` (job type `products-csv-import`), the import and export steps and `ProductCsvExportPlaceholderProvider`.
- `ProductCsvImportSchemaPlugin` implements `Spryker\Zed\DataExchangeExtension\Dependency\Plugin\ImportSchemaPluginInterface` and `ExportSchemaPluginInterface`, and exposes `getPlaceholderProvider()`.
- The product import steps implement `Spryker\Zed\DataExchangeExtension\Dependency\Plugin\ImportStepInterface` through the module's own `AbstractProductCsvImportStep`; the module depends on `spryker/data-exchange-extension` only.
- Back Office URLs moved from `/product-experience-management/{job,run,export,template}` to `/data-exchange/...`; the file systems are now `data-exchange-imports` and `data-exchange-exports`.

### Removed

- Facade methods `getImportJobCollection()`, `createImportJobCollection()`, `createImportJobRunCollection()`, `getImportJobRunCollection()`, `getImportJobRunErrorCollection()`, `exportData()`, `processNextPendingRun()` (now on `DataExchangeFacadeInterface`).
- `Business\Dependency\Plugin\*` interfaces, `Business\Export\PlaceholderProvider\ExportPlaceholderProviderInterface`, the engine config getters (`getImportStatus*()`, `getImportFileSystemName()`, `getExportFileSystemName()`, `getImportCsvBatchSize()`, `getExportCsvBatchSize()`, `getImportMaxFileSizeString()`, `getAllowedMimeTypesWithExtensions()`, `getImportErrorDisplayThreshold()`, `getImportDefaultContentType()`), the dependency provider constants `SERVICE_FILE_SYSTEM`, `FACADE_EVENT`, `SERVICE_UTIL_DATE_TIME`, `PLUGINS_IMPORT_SCHEMA`, `PLUGINS_IMPORT_PRE_PROCESSOR`, `PLUGINS_IMPORT_POST_PROCESSOR`, the `ImportJob*` transfers, the `spy_import_job` schema and the `ImportJobHelper` test helper.
- Dependencies `spryker/event`, `spryker/file-system`, `spryker/log`, `spryker/util-date-time`, `spryker/util-text`.

## 4.0.0 - 2026-09-04

### Added

- Backend API resource `/products` (Get, GetCollection, Post, Patch) for concrete product CRUD, covering attributes, localized attributes, prices, image sets, stocks, product bundles, product classes and shipment types.
- Automatic parent abstract product creation on POST when `abstractSku` is omitted.
- Abstract-level `stores`, `taxSet`, `categories`, `newFrom` and `newTo` on the `/products` resource, readable and writable through the concrete endpoint.
- `ProductExperienceManagementConfig::getAbstractSkuPattern()` to customize the generated abstract product SKU format.
- Propel schemas activating the optional UUID references the API relies on: `uuid` columns and behaviors on `spy_category`, `spy_price_product_store`, `spy_product_image_set` and `spy_tax_set`, plus the `uuid` behavior on `spy_stock`.
- `spryker/api-platform` as an optional dependency (`require-dev` + `suggest`) — install it to enable the products backend API resources.

## 0.1.0 - 2026-03-26

### Added

- Initial release of the ProductExperienceManagement module.
- Product attribute visibility management (PDP, PLP, Cart, None).
- Backoffice attribute list with visibility column and filter.
- Storage publisher and synchronization plugins.
- Storefront widgets: ProductAttributeVisibilityPdpWidget, ProductAttributeVisibilityPlpWidget, ProductAttributeVisibilityCartWidget.
- Client layer for reading attribute visibility from Redis.
- Data import support for attribute visibility.
