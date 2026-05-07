# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

SilverStripe 6 vendor module that adds an SVG file type. The framework intentionally omits SVG support (see [silverstripe/silverstripe-framework#7299](https://github.com/silverstripe/silverstripe-framework/issues/7299)); this module reintroduces it with sanitisation on upload.

PHP `^8.3`, SilverStripe `^6`. Module is `silverstripe-vendormodule` namespaced under `WeDevelop\SvgImage\` (`src/`).

## Development environment

All development is containerised. The `Makefile` is the front door — never run `phpunit`/`phpstan`/`rector` on the host.

```sh
make up            # Start FrankenPHP + MySQL stack (auto-generates .docker/.env)
make test          # PHPUnit
make coverage      # PHPUnit with coverage (HTML in coverage/html, clover for CI)
make analyse       # PHPStan (level: max, type_coverage 100%)
make rector-dry    # Rector preview
make rector        # Rector apply
make dev-build     # sake dev/build flush=1
make flush         # sake flush
make sh            # Shell into the app container
make destroy       # Stop and drop volumes (wipes DB)
```

Running a single test:
```sh
docker compose -f .docker/compose.yml exec app vendor/bin/phpunit --filter testGetWidthIsParsedFromSvgContent
docker compose -f .docker/compose.yml exec app vendor/bin/phpunit vendor/wedevelopnl/silverstripe-svg-image/tests/Assets/SvgTest.php
```

CI matrix runs PHPUnit on PHP 8.3 / 8.4 / 8.5 plus static analysis (`make analyse`) and `make rector-dry` — keep all four green.

### How the container is wired

- `.docker/env.sh` derives `WEB_PORT`/`DB_PORT` from a hash of the working-directory name, so multiple worktrees don't collide. The generated `.docker/.env` is gitignored.
- `.docker/compose.yml` mounts the repo's `src/`, `tests/`, `_config/`, and `composer.json` into a stub SilverStripe project at `/module`. The project's own composer setup (`.docker/app/composer.json`) installs `silverstripe/recipe-cms` and pulls the module in via a `path` repository at `/module` with `symlink: true`.
- `.docker/entrypoint.sh` runs `composer install`, replaces FrankenPHP's default `index.php` with the SilverStripe bootstrap, manually creates the `_resources/` directory the vendor-plugin can't (because it tries `realpath()` across the symlink to `/module`), then runs `dev/build flush=1`.
- `Page` / `PageController` stubs are generated in the `Dockerfile` rather than committed, to avoid duplicate-class detection when the module is symlinked into `vendor/`.
- PHPUnit/PHPStan/Rector configs live under `.docker/app/` and target paths inside the container (`/module/src`, `vendor/wedevelopnl/silverstripe-svg-image/tests`). Edit those, not host-relative paths.

## Architecture

Two source files do all the work; the rest is YAML configuration and container plumbing.

### `src/Assets/Svg.php` — the SVG file type

Subclass of `SilverStripe\Assets\Image`. Key behaviours:

- **Sanitisation runs on every write**, not on upload. `onBeforeWrite()` re-reads the file content, runs `enshrined/svg-sanitize`, and writes the cleaned bytes back. Tests rely on this — see `testOnBeforeWriteSanitizesUnsafeContent`.
- **Width/height come from parsing the SVG body** (`meyfa/php-svg`), not from filesystem image headers. Parsing is lazy (`parseSvg()`) so the framework has time to inject the `LoggerInterface` dependency before a parse failure tries to log.
- **`manipulate()` is a no-op** — every variant returns the original `DBFile`. By design (`docs/DECISIONS.md`): no rasterisation, no scaling, no thumbnailing. SVGs scale via CSS. Don't add manipulation without revisiting that decision.
- **`getTag()` returns the raw SVG markup**, not an `<img>` tag. Templates inline the SVG.
- `lazy_loading_enabled` is `false` because the file is inlined, not referenced by `src=`.

### `src/Task/MigrateCurrentSvgsTask.php`

`BuildTask` (SilverStripe 6 API — `execute(InputInterface, PolyOutput): int`). One-shot SQL update flipping `File.ClassName` to `Svg::class` for any row whose `Name` ends in `.svg`. Marked `@internal` and named `migrate-svg-files`. Used to migrate sites previously storing SVGs as plain `File`s (notably alongside `wedevelopnl/silverstripe-icon-manager`).

### `_config/` — wires the type into the framework

- `filetypes.yml` registers `svg` as an extension, adds it to the `image`/`image/supported` app categories, and maps the extension to `WeDevelop\SvgImage\Assets\Svg`.
- `mimevalidator.yml` is gated on `silverstripe/mimevalidator` being installed and accepts both `image/svg` and `image/svg+xml`.
- `model.yml` adds the same MIME types to `DBFile::supported_images`.

If you add config, follow the existing `After: '#…'` ordering so framework defaults are in place first.

## Testing notes

- Tests extend `SapphireTest`. `SvgTest` uses `TestAssetStore` and `$usesDatabase = true`.
- Fixtures live in `tests/fixtures/`. `sample.svg` is a valid 100×50 rect; `malicious.svg` carries `<script>`/`onclick`/`javascript:` payloads the sanitiser must strip; `invalid.svg` is garbage used to exercise the parse-error log path.
- `testInvalidSvgContentIsLoggedAndDoesNotThrow` writes via `File` (bypassing the `Svg` sanitiser), then `SQLUpdate`s the `ClassName` to reload as `Svg` — that's how you stage broken content past the on-write sanitiser. The `@$svg->getWidth()` suppresses warnings from `meyfa/php-svg`'s `SimpleXMLElement` call on garbage; don't unsuppress.
- `MigrateCurrentSvgsTaskTest` invokes `execute()` directly via reflection because `BuildTask::run()` adds bookkeeping that's irrelevant to the unit test.

## Conventions

- `declare(strict_types=1)` on every PHP file.
- `#[Override]` on every method that overrides a parent — PHPStan type-coverage is at 100%, Rector enforces this on `make rector-dry`.
- Comments explain *why*, not *what*. The existing inline comments in `Svg.php` and the tests (e.g. the `parseSvg()` deferral note, the reflection rationale in the task test) are the model.
- The Rector config skips three rules (`ChangeOrIfContinueToMultiContinueRector`, `FlipTypeControlToUseExclusiveTypeRector`, `PostIncDecToPreIncDecRector`) — leave those skipped unless you have a reason.
