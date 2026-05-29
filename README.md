# Vermieter

A WordPress plugin for private landlords to manage rental properties, tenants, payments and create German utility statements (*Nebenkostenabrechnungen*).
Made for my privat purpeses, it worked but is not perfect. 

## Features

### Property Management

* Manage properties
* Manage apartments and parking spaces
* Manage tenants
* Support tenant changes during the billing period

### Rental Management

* Define cold rent and advance payments
* Store rent adjustments over time
* Calculate monthly target rents
* Track tenant payments
* Record additional payments and credits

### Cost Management

* Define custom cost categories
* Allocate costs by distribution keys
* Support apartment and garage allocations
* Support special costs (*Sonderkosten*)
* Support heating costs based on external billing statements (e.g. Brunata)

### Utility Statements

* Generate annual utility statements
* Automatic proration for tenant changes
* Detailed cost breakdown per unit
* Separate display of apartment and garage allocations
* PDF export

### Tax Reporting (§35a EStG)

* Household-related services (*Haushaltsnahe Dienstleistungen*)
* Craftsman services (*Handwerkerleistungen*)
* Separate tax information tables
* Tenant-specific tax summaries

## Screenshots

*Add screenshots here.*

## Requirements

* WordPress 6.0+
* PHP 8.0+
* MySQL 5.7+ or MariaDB equivalent

## Installation

1. Upload the plugin to the `/wp-content/plugins/` directory.
2. Activate the plugin through the WordPress admin panel.
3. Open the **Vermieter** menu in the WordPress, you need to be logedin.
4. Create your property and start entering tenants and costs.

## Workflow

1. Create property
2. Create apartments and parking spaces
3. Add tenants
4. Define rent and advance payment terms
5. Enter operating costs
6. Record payments
7. Generate annual utility statement
8. Export PDF

## Current Version

### Version 0.15.1

* Added support for household-related services (§35a EStG)
* Added support for craftsman services (§35a EStG)
* Added tax information tables to utility statements
* Improved multi-unit allocation display
* Fixed duplicate tax total calculations
* Various PDF and reporting improvements

## Roadmap

Planned features:

* Email delivery of utility statements
* storage from documents at the webserver for single source of truth.
* pdf analyses for new bils. 

## License

GPL v2 or later
