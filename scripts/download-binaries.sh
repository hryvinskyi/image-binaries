#!/bin/bash
#
# Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
# Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
# GitHub: https://github.com/hryvinskyi
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BINARIES_DIR="$(dirname "$SCRIPT_DIR")/binaries"

# Versions
LIBWEBP_VERSION="1.5.0"
CAVIF_VERSION="1.5.5"

echo "Downloading image binaries..."
echo "Target directory: $BINARIES_DIR"
echo ""

# Create platform directories
mkdir -p "$BINARIES_DIR"/{linux-x64,linux-arm64,darwin-x64,darwin-arm64}

# Function to download and extract cwebp from libwebp
download_cwebp() {
    local platform=$1
    local url=$2
    local archive_path=$3

    echo "Downloading cwebp for $platform..."

    local temp_dir=$(mktemp -d)
    local archive_file="$temp_dir/libwebp.tar.gz"

    curl -sL -o "$archive_file" "$url"
    tar -xzf "$archive_file" -C "$temp_dir"

    local cwebp_path="$temp_dir/$archive_path"
    if [ -f "$cwebp_path" ]; then
        cp "$cwebp_path" "$BINARIES_DIR/$platform/cwebp"
        chmod +x "$BINARIES_DIR/$platform/cwebp"
        echo "  cwebp installed for $platform"
    else
        echo "  WARNING: cwebp not found at $cwebp_path"
        # Try to find it
        find "$temp_dir" -name "cwebp" -type f 2>/dev/null | head -1 | while read found; do
            if [ -n "$found" ]; then
                cp "$found" "$BINARIES_DIR/$platform/cwebp"
                chmod +x "$BINARIES_DIR/$platform/cwebp"
                echo "  cwebp found and installed for $platform"
            fi
        done
    fi

    rm -rf "$temp_dir"
}

# Function to download cavif binary
download_cavif() {
    local platform=$1
    local url=$2

    echo "Downloading cavif for $platform..."

    local temp_file=$(mktemp)
    local http_code=$(curl -sL -w "%{http_code}" -o "$temp_file" "$url")

    if [ "$http_code" = "200" ] && [ -s "$temp_file" ]; then
        local file_size=$(stat -f%z "$temp_file" 2>/dev/null || stat -c%s "$temp_file" 2>/dev/null)
        if [ "$file_size" -gt 1000 ]; then
            mv "$temp_file" "$BINARIES_DIR/$platform/cavif"
            chmod +x "$BINARIES_DIR/$platform/cavif"
            echo "  cavif installed for $platform"
        else
            echo "  WARNING: cavif download too small ($file_size bytes), URL may be incorrect"
            rm -f "$temp_file"
        fi
    else
        echo "  WARNING: Failed to download cavif (HTTP $http_code)"
        rm -f "$temp_file"
    fi
}

# Function to download ImageMagick AppImage (portable, works on all Linux)
download_magick_appimage() {
    echo "Downloading ImageMagick AppImage..."

    local url="https://imagemagick.org/archive/binaries/magick"
    local temp_file=$(mktemp)

    curl -sL -o "$temp_file" "$url"

    if [ -s "$temp_file" ]; then
        local file_size=$(stat -f%z "$temp_file" 2>/dev/null || stat -c%s "$temp_file" 2>/dev/null)
        if [ "$file_size" -gt 1000000 ]; then
            # Copy to all platforms (AppImage is portable)
            for platform in linux-x64 linux-arm64 darwin-x64 darwin-arm64; do
                cp "$temp_file" "$BINARIES_DIR/$platform/magick"
                chmod +x "$BINARIES_DIR/$platform/magick"
            done
            echo "  magick installed for all platforms"
        else
            echo "  WARNING: magick download seems incomplete ($file_size bytes)"
        fi
    else
        echo "  WARNING: Failed to download magick"
    fi

    rm -f "$temp_file"
}

echo "=== Downloading cwebp (libwebp $LIBWEBP_VERSION) ==="

# cwebp - Linux x64
download_cwebp "linux-x64" \
    "https://storage.googleapis.com/downloads.webmproject.org/releases/webp/libwebp-${LIBWEBP_VERSION}-linux-x86-64.tar.gz" \
    "libwebp-${LIBWEBP_VERSION}-linux-x86-64/bin/cwebp"

# cwebp - Linux ARM64
download_cwebp "linux-arm64" \
    "https://storage.googleapis.com/downloads.webmproject.org/releases/webp/libwebp-${LIBWEBP_VERSION}-linux-aarch64.tar.gz" \
    "libwebp-${LIBWEBP_VERSION}-linux-aarch64/bin/cwebp"

# cwebp - macOS x64
download_cwebp "darwin-x64" \
    "https://storage.googleapis.com/downloads.webmproject.org/releases/webp/libwebp-${LIBWEBP_VERSION}-mac-x86-64.tar.gz" \
    "libwebp-${LIBWEBP_VERSION}-mac-x86-64/bin/cwebp"

# cwebp - macOS ARM64
download_cwebp "darwin-arm64" \
    "https://storage.googleapis.com/downloads.webmproject.org/releases/webp/libwebp-${LIBWEBP_VERSION}-mac-arm64.tar.gz" \
    "libwebp-${LIBWEBP_VERSION}-mac-arm64/bin/cwebp"

echo ""
echo "=== Downloading cavif (v$CAVIF_VERSION) ==="

# cavif-rs release assets - check https://github.com/kornelski/cavif-rs/releases for exact names
download_cavif "linux-x64" \
    "https://github.com/kornelski/cavif-rs/releases/download/v${CAVIF_VERSION}/cavif-${CAVIF_VERSION}.zip"

download_cavif "darwin-x64" \
    "https://github.com/kornelski/cavif-rs/releases/download/v${CAVIF_VERSION}/cavif-${CAVIF_VERSION}.zip"

download_cavif "darwin-arm64" \
    "https://github.com/kornelski/cavif-rs/releases/download/v${CAVIF_VERSION}/cavif-${CAVIF_VERSION}.zip"

# Linux ARM64 - may need to compile from source or use the Linux x64 binary
echo "  NOTE: Linux ARM64 cavif may need to be compiled from source"
if [ ! -f "$BINARIES_DIR/linux-arm64/cavif" ]; then
    # Try to use Linux x64 binary as fallback (won't work, but placeholder)
    echo "  Skipping linux-arm64 cavif (not available in releases)"
fi

echo ""
echo "=== Downloading ImageMagick ==="
download_magick_appimage

echo ""
echo "=== Download complete ==="
echo ""
echo "Verify binaries:"
for platform in linux-x64 linux-arm64 darwin-x64 darwin-arm64; do
    echo ""
    echo "$platform:"
    for binary in cwebp cavif magick; do
        if [ -f "$BINARIES_DIR/$platform/$binary" ]; then
            size=$(ls -lh "$BINARIES_DIR/$platform/$binary" | awk '{print $5}')
            echo "  $binary: $size"
        else
            echo "  $binary: NOT FOUND"
        fi
    done
done

echo ""
echo "NOTE: Some binaries may not be available for all platforms."
echo "You may need to compile them from source for missing platforms."
