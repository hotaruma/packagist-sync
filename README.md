# Packagist Sync Action

[![Build and Test](https://github.com/hotaruma/packagist-sync/actions/workflows/test.yml/badge.svg)](https://github.com/hotaruma/packagist-sync/actions/workflows/test.yml)
[![License](https://img.shields.io/github/license/hotaruma/packagist-sync.svg)](https://github.com/hotaruma/packagist-sync/blob/master/LICENSE)

This action automates the synchronization and updating of package information on Packagist.

## Example usage

Minimal setup:

```yaml
uses: hotaruma/packagist-sync@v1.0.0
with:
  api-token: ${{ secrets.packagist_token }}

  # Optional Parameters
  packagist-username: 'username'
  package-name: 'vendor/package'
  github-repository-url: 'https://github.com/vendor/package'
  packagist-domain: 'https://packagist.org'
  composer-json-path: '/path/to/'
```

## Inputs

### `api-token`

**Required** - The API token for Packagist.

### `packagist-username`

The username on Packagist.

> This parameter is **optional** if the package name is set in the `composer.json` file. If the package name is set, the
> vendor part will be used as the username.

### `package-name`

The name of the package.

> This **parameter** is optional if the package name is already set in the `composer.json` file.

### `github-repository-url`

The URL of the GitHub repository.

> This parameter is optional if the package already exists.

### `packagist-domain`

**Optional** - The domain of Packagist.

### `composer-json-path`

**Optional** - The custom path to the `composer.json` file.

> The path should be relative to `$GITHUB_WORKSPACE`.
