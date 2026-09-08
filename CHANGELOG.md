# Changelog

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
