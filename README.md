# Xform (CakePHP Plugin)

[![GitHub License](https://img.shields.io/github/license/pieceofcake2/xform?label=License)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/pieceofcake2/xform?label=Packagist)](https://packagist.org/packages/pieceofcake2/xform)
![PHP](https://img.shields.io/packagist/dependency-v/pieceofcake2/xform/php?logo=php&logoColor=%23FFFFFF&label=PHP&labelColor=%23777BB4&color=%23FFFFFF)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%205-brightgreen?label=PHPStan)](https://github.com/pieceofcake2/xform/actions/workflows/CI.yml)
![CakePHP](https://img.shields.io/packagist/dependency-v/pieceofcake2/xform/pieceofcake2/cakephp?logo=cakephp&logoColor=%23FFFFFF&label=CakePHP&labelColor=%23D33C43&color=%23FFFFFF)
[![CI](https://img.shields.io/github/actions/workflow/status/pieceofcake2/xform/CI.yml?label=CI)](https://github.com/pieceofcake2/xform/actions/workflows/CI.yml)
[![Codecov](https://img.shields.io/codecov/c/gh/pieceofcake2/xform?label=Coverage)](https://codecov.io/gh/pieceofcake2/xform)

pieceofcake2/xform extends CakePHP's `FormHelper` for input → confirmation → complete form
flows: on the confirmation screen, the same view code outputs the submitted values as text
instead of form controls. Originally developed by Yasushi Ichikawa.

## Documentation

- Website: https://pieceofcake2.github.io/xform/
- Markdown: [docs/README.md](docs/README.md) (for AI tools and offline reading)

The Markdown documentation is also included in the Composer package, in the plugin's directory: `plugins/Xform/docs/README.md` (the location depends on your `installer-paths`). Install the package with `--dev` if you only need it for development.

## Requirements

* PHP 8.0+
* pieceofcake2/cakephp 2.12+

## Installation

```bash
composer require pieceofcake2/xform
```

Then load the plugin and add the `Xform.Xform` helper to your controller.
See [Installation](docs/README.md#installation) and [Usage](docs/README.md#usage).

## Contributing

See the [contribution guidelines](https://github.com/pieceofcake2/.github/blob/master/CONTRIBUTING.md).

## License

MIT License. See [LICENSE](LICENSE).
