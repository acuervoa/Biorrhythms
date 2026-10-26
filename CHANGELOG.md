# Changelog

Notable changes to Biorrhythms are recorded here.

## [Unreleased]

### Added

- Added the Nginx virtual host and setup instructions for routing `biorrhythms.local` to the local Docker app through Avahi and Nginx.

## [1.7.0] — 2026-10-26

### Changed

- Reorganized the application into four destinations: Lectura, Explorar, Diario and Herramientas.
- Moved calendar, event horizons and cycle extremes into Explorar; grouped comparison, chronotype, symbolic context, sharing and integrations in Herramientas.
- Replaced long simultaneous tool lists with focused disclosures and a bounded cycle-extreme preview.
- Added responsive bottom navigation on compact screens, date stepping, destination-aware deep links, and focus/scroll management when changing destinations.
- Preserved API responses, calculations, query parameters, embed mode, browser-local data and export behavior.
- Updated the product specification, visual system, implementation plan and Biorrhythms agent skill.

## [1.6.0] — 2026-10-26

### Changed

- Replaced the dashboard-first presentation with an individual daily-reading flow centered on the selected date.
- Added an editorial paper-and-ink visual identity with consistent physical, emotional, and intellectual cycle colors in light and dark themes.
- Clarified that cycle percentages are mathematical model outputs, not measurements or validated predictions.
- Improved the first-visit form and responsive chart; mobile charts show no more than five temporal labels on the X axis.
- Preserved existing calculations, URL parameters, API behavior, local storage, export, embed mode, and secondary tools.
- Added `PRODUCT.md`, `DESIGN.md`, and the `biorrhythms-app` agent skill.
