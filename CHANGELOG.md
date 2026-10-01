# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html). From 6.0.0 onwards the major
version follows the supported Silverstripe major version.

This changelog starts at 6.0.0. For earlier versions, see the
[GitHub releases](https://github.com/wedevelopnl/silverstripe-svg-image/releases).

## [6.0.1] - 2026-10-01

### Fixed

- Requires `meyfa/php-svg` ^0.16.1, which removes the "Implicitly marking parameter `$index` as
  nullable is deprecated" notice raised on PHP 8.4 and newer.

## [6.0.0] - 2026-10-01

### Changed

- **Breaking:** Requires Silverstripe 6 and PHP 8.3 or newer.
- **Breaking:** `MigrateCurrentSvgsTask` is now namespaced as
  `WeDevelop\SvgImage\Task\MigrateCurrentSvgsTask` and uses the Silverstripe 6 `BuildTask` API.
  Update any references to the old global class name. The task is still named `migrate-svg-files`
  (`sake tasks:migrate-svg-files`).
- SVG dimensions are now parsed the first time `getWidth()` or `getHeight()` is called, instead of
  every time an `Svg` record is instantiated.

### Removed

- Support for Silverstripe 5 and PHP 8.1/8.2. Stay on 2.x if you need either.

### Fixed

- A malformed SVG no longer crashes `Svg` instantiation. The parse error is logged and the width
  and height fall back to `0`.
- `MigrateCurrentSvgsTask` quotes its table and column identifiers, so it also works on databases
  that need ANSI-quoted identifiers.

[6.0.1]: https://github.com/wedevelopnl/silverstripe-svg-image/compare/6.0.0...6.0.1
[6.0.0]: https://github.com/wedevelopnl/silverstripe-svg-image/compare/2.1.1...6.0.0
