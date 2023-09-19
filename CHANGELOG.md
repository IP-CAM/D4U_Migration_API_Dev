# Changelog 📝

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

* 

## [version] - 2023-09-19

### Added
## upload/admin/model/extention/module/api4u_migrations.php
+ Functions have arrays of (`{$category}`, `{$column}`, `{$query}`).
+ Function runQueries() -> create an overall function that runs all the queries.
+ Function that runs per each query and check if column exists. Run only in case that column doesn't exists or it's a drop query. 
+ Usleep on each $SQL query, protecting MySQL from getting down/broke.
* 

### Changed

* 

### Fixed

* 

### Deprecated

* 

### Removed

* 

### Security

* 