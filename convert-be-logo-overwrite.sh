#!/bin/bash

# Script to convert a JPG image to PNG and SVG formats with multiple PNG sizes
# Usage: ./convert_image.sh input.jpg

# Check if ImageMagick and librsvg are installed
if ! command -v convert &> /dev/null || ! command -v rsvg-convert &> /dev/null; then
    echo "Error: This script requires ImageMagick and librsvg."
    echo "Please install them with:"
    echo "  brew install imagemagick librsvg               # For macOS"
    exit 1
fi

# Check if inkscape is installed
if ! command -v inkscape &> /dev/null; then
    echo "Error: This script requires inkscape"
    echo "Please install it with:"
    echo "  brew install inkscape               # For macOS"
    exit 1
fi

INPUT_FILE="./public/img/logo_be.jpg"
FILENAME=$(basename -- "$INPUT_FILE")
FILENAME_NO_EXT="${FILENAME%.*}"

# Check if input file exists
if [ ! -f "$INPUT_FILE" ]; then
    echo "Error: Input file '$INPUT_FILE' not found."
    exit 1
fi

# Check if input file is a JPG
if [[ ! "$INPUT_FILE" =~ \.(jpg|jpeg)$ ]]; then
    echo "Warning: Input file doesn't appear to be a JPG. Continuing anyway..."
fi

# Convert to PNG in different sizes
PNG_SIZES=(48 57 60 72 76 96 114 120 128 144 152 180 192 256 384 512 1024)
for size in "${PNG_SIZES[@]}"; do
    convert "$INPUT_FILE" -resize "${size}x${size}" "./public/img/logo/pwa/${size}.png"
    echo "Created PNG: ${size}.png"
done

# Convert to SVG
# Note: Direct JPG to SVG conversion is not ideal for quality
# This is a basic conversion that traces the bitmap
convert "$INPUT_FILE" "${FILENAME_NO_EXT}_temp.pnm"
inkscape --export-filename="./public/img/pixelfed-icon-color.svg" \
             --export-type=svg \
             --export-plain-svg \
             "$INPUT_FILE"
echo "Created SVG: ${FILENAME_NO_EXT}.svg"

# Also create a standard PNG without resizing
convert "$INPUT_FILE" "./public/img/pixelfed-icon-color.png"
echo "Created standard PNG: ${FILENAME_NO_EXT}.png"

echo "All conversions complete!"
