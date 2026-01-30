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
        local found=$(find "$temp_dir" -name "cwebp" -type f 2>/dev/null | head -1)
        if [ -n "$found" ]; then
            cp "$found" "$BINARIES_DIR/$platform/cwebp"
            chmod +x "$BINARIES_DIR/$platform/cwebp"
            echo "  cwebp found and installed for $platform"
        fi
    fi

    rm -rf "$temp_dir"
}

# Function to download cavif from .deb package (Linux)
download_cavif_from_deb() {
    local platform=$1
    local url=$2

    echo "Downloading cavif for $platform from .deb..."

    local temp_dir=$(mktemp -d)
    local deb_file="$temp_dir/cavif.deb"

    curl -sL -o "$deb_file" "$url"

    # Extract .deb (it's an ar archive)
    cd "$temp_dir"
    ar -x "$deb_file" 2>/dev/null || {
        echo "  WARNING: Failed to extract .deb"
        rm -rf "$temp_dir"
        return
    }

    # Find and extract data.tar (could be .tar.xz, .tar.gz, .tar.zst)
    if [ -f "data.tar.xz" ]; then
        tar -xJf "data.tar.xz"
    elif [ -f "data.tar.gz" ]; then
        tar -xzf "data.tar.gz"
    elif [ -f "data.tar.zst" ]; then
        zstd -d "data.tar.zst" -o "data.tar" && tar -xf "data.tar"
    elif [ -f "data.tar" ]; then
        tar -xf "data.tar"
    fi

    # Find cavif binary
    local cavif_path=$(find "$temp_dir" -name "cavif" -type f 2>/dev/null | head -1)
    if [ -n "$cavif_path" ]; then
        cp "$cavif_path" "$BINARIES_DIR/$platform/cavif"
        chmod +x "$BINARIES_DIR/$platform/cavif"
        echo "  cavif installed for $platform"
    else
        echo "  WARNING: cavif not found in .deb package"
    fi

    cd - > /dev/null
    rm -rf "$temp_dir"
}

# Function to download cavif from .zip (macOS)
download_cavif_from_zip() {
    local platform=$1
    local url=$2

    echo "Downloading cavif for $platform from .zip..."

    local temp_dir=$(mktemp -d)
    local zip_file="$temp_dir/cavif.zip"

    curl -sL -o "$zip_file" "$url"

    # Extract zip
    unzip -q "$zip_file" -d "$temp_dir" 2>/dev/null || {
        echo "  WARNING: Failed to extract .zip"
        rm -rf "$temp_dir"
        return
    }

    # Find cavif binary
    local cavif_path=$(find "$temp_dir" -name "cavif" -type f 2>/dev/null | head -1)
    if [ -n "$cavif_path" ]; then
        cp "$cavif_path" "$BINARIES_DIR/$platform/cavif"
        chmod +x "$BINARIES_DIR/$platform/cavif"
        echo "  cavif installed for $platform"
    else
        echo "  WARNING: cavif not found in .zip package"
    fi

    rm -rf "$temp_dir"
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

# cavif - Linux x64 (from .deb)
download_cavif_from_deb "linux-x64" \
    "https://github.com/kornelski/cavif-rs/releases/download/v${CAVIF_VERSION}/cavif_${CAVIF_VERSION}-1_amd64.deb"

# cavif - macOS (from .zip - universal binary works on both x64 and arm64)
download_cavif_from_zip "darwin-x64" \
    "https://github.com/kornelski/cavif-rs/releases/download/v${CAVIF_VERSION}/cavif-${CAVIF_VERSION}.zip"

# Copy macOS binary to arm64 (it's a universal binary)
if [ -f "$BINARIES_DIR/darwin-x64/cavif" ]; then
    cp "$BINARIES_DIR/darwin-x64/cavif" "$BINARIES_DIR/darwin-arm64/cavif"
    chmod +x "$BINARIES_DIR/darwin-arm64/cavif"
    echo "  cavif copied to darwin-arm64 (universal binary)"
fi

# Linux ARM64 - not available in releases
echo "  NOTE: Linux ARM64 cavif not available in releases (needs compilation)"

echo ""
echo "=== Download complete ==="
echo ""
echo "Verify binaries:"
for platform in linux-x64 linux-arm64 darwin-x64 darwin-arm64; do
    echo ""
    echo "$platform:"
    for binary in cwebp cavif; do
        if [ -f "$BINARIES_DIR/$platform/$binary" ]; then
            size=$(ls -lh "$BINARIES_DIR/$platform/$binary" | awk '{print $5}')
            echo "  $binary: $size"
        else
            echo "  $binary: NOT FOUND"
        fi
    done
done
