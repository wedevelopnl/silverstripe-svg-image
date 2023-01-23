# Silverstripe SVG image
Silverstripe doesn't support SVG to be used out of the box. This module adds a new file type
which supports SVG images in a very basic way. Please note that the absence of SVG support
isn't a fluke but the result of some extended [discussion](https://github.com/silverstripe/silverstripe-framework/issues/7299).

This module provides some sanitation of SVG files on upload but be aware of the potential
risk. 

## Image manipulation
The Silverstripe core supports a extensive set of image manipulation tools. At the moment
image manipulation isn't supported on SVG images. When an manipulation method is called on
an SVG image the source image is simply returned.

## Requirements
* See `composer.json` requirements

## Installation
* `composer require wedevelopnl/silverstripe-svg-image`
* Run a `dev/build`

## License
See [License](LICENSE)

## Maintainers
* [WeDevelop](https://www.wedevelop.nl/) <development@wedevelop.nl>

## Development and contribution
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.
See read our [contributing](CONTRIBUTING.md) document for more information.

### Getting started
We advise to use [Docker](https://docker.com)/[Docker compose](https://docs.docker.com/compose/) for development.\
We also included a [Makefile](https://www.gnu.org/software/make/) to simplify some commands

Our development container contains some built-in tools like `PHPCSFixer`.
