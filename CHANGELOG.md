# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Add new configuration (UnNormalizedForm, CaseSensitive) from Xml and Json mapping

### Fixed

- Fix tag not transformed from array to string
- Fix PHP8 attribute not read correctly
- Fix Json mapping not working because of Xml mapping
- Fix add document to search not working (empty document in Redis)
- (dev) Coverage with XDebug 3

### Changed

- (dev) Add compatibility with PHP for QA tools
- Set the minimum version of `macfja/redisearch` to `2.1.0`

## [2.0.0]

Rework to be compatible with `macfja/redisearch`=`^2.0`

### Added

- A simple ObjectFactory

### Changed

- Remove `TemplateXxxxMapper`
- Refactoring to avoid duplicated code
- Rename a bunch of class

### Removed

- Suggestion type attribute

## [1.0.0]

First version

[Unreleased]: https://github.com/MacFJA/php-redisearch-integration/compare/2.0.0...HEAD
[2.0.0]: https://github.com/MacFJA/php-redisearch-integration/releases/tag/2.0.0
[1.0.0]: https://github.com/MacFJA/php-redisearch-integration/releases/tag/1.0.0