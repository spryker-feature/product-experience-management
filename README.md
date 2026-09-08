# ProductExperienceManagement Module

[![Latest Stable Version](https://poser.pugx.org/spryker-feature/product-experience-management/v/stable.svg)](https://packagist.org/packages/spryker-feature/product-experience-management)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg)](https://php.net/)

Provides the backend API for product CRUD and manages product attribute visibility across storefront pages. Exposes concrete products at `/products` on the Glue Backend API, and controls which product attributes are displayed on Product Detail Pages (PDP), Product Listing Pages (PLP), and Cart pages through configurable visibility types.

Key features:

- Backend API for product CRUD via `/products` (Get, GetCollection, Post, Patch), covering prices, stocks, image sets, bundles, product classes and shipment types in one resource
- Automatic parent abstract product creation when a concrete is posted without one, including the abstract-level stores, tax set, categories and new-from/new-to
- Attribute visibility management per page type (PDP, PLP, Cart, None)
- Backoffice UI for configuring attribute visibility with filtering
- Storage publishing and synchronization of attribute visibility data
- Storefront widgets rendering attributes as badges (PLP/Cart) or structured data (PDP)
- Batch attribute preloading for optimal performance on listing pages

## Installation

```
composer require spryker-feature/product-experience-management
```

## Documentation

[Spryker Documentation](https://docs.spryker.com)
