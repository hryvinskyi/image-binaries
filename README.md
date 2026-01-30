# Image Binaries

Composer plugin that provides pre-compiled image processing binaries for WebP and AVIF conversion.

## Included Binaries

| Binary | Description | Source |
|--------|-------------|--------|
| `cwebp` | WebP encoder from libwebp | [Google WebP](https://developers.google.com/speed/webp) |
| `cavif` | AVIF encoder | [cavif-rs](https://github.com/kornelski/cavif-rs) |

## Supported Platforms

- `linux-x64` - Linux x86_64
- `linux-arm64` - Linux ARM64/aarch64 (cwebp only, cavif needs compilation)
- `darwin-x64` - macOS Intel
- `darwin-arm64` - macOS Apple Silicon

## Installation

### Via Composer

```bash
composer require hryvinskyi/image-binaries
```

The binaries are automatically installed to `vendor/bin/` during composer install/update.

### Manual Installation

If automatic installation doesn't work, run:

```bash
vendor/bin/install-binaries
```

Options:
- `--force` - Reinstall even if binaries already exist
- `--binary=NAME` - Install only specific binary (cwebp or cavif)
- `--help` - Show help message

## Usage

After installation, binaries are available at:

```
vendor/bin/cwebp
vendor/bin/cavif
```

### WebP Conversion

```bash
vendor/bin/cwebp input.png -q 85 -o output.webp
```

### AVIF Conversion

```bash
vendor/bin/cavif input.png -Q 80 -o output.avif
```

## For Package Maintainers

### Downloading/Updating Binaries

To download the latest binaries for all platforms:

```bash
cd app/code/Hryvinskyi/ImageBinaries
./scripts/download-binaries.sh
```

This will populate the `binaries/` directory with platform-specific executables.

### Directory Structure

```
binaries/
├── linux-x64/
│   ├── cwebp
│   └── cavif
├── linux-arm64/
│   └── cwebp
├── darwin-x64/
│   ├── cwebp
│   └── cavif
└── darwin-arm64/
    ├── cwebp
    └── cavif
```

## Binary Versions

| Binary | Version |
|--------|---------|
| libwebp (cwebp) | 1.5.0 |
| cavif-rs | 1.5.5 |

## License

MIT License

## Author

Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
