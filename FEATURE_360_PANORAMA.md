# 360° Equirectangular Panorama Support

## Overview

Pixelfed now supports 360° equirectangular panoramic images with an interactive viewer that allows users to explore panoramas by dragging, zooming, and rotating the view. This feature automatically detects 360° images and renders them with an immersive viewer instead of displaying them as flat images.

## Features

- **Automatic Detection**: Images are automatically identified as 360° panoramas based on:
  - XMP GPano metadata (`GPano:ProjectionType="equirectangular"`)
  - Aspect ratio analysis (2:1 ratio with ±10% tolerance as fallback)

- **Interactive Viewer**:
  - Mouse drag navigation
  - Touch gesture support on mobile devices
  - Zoom controls (mousewheel and UI buttons)
  - Directional navigation controls
  - Fullscreen mode
  - Smooth camera movements

- **Lazy Loading**: The Photo Sphere Viewer library is loaded only when a 360° image is present on the page, minimizing performance impact for regular posts

- **Accessibility**:
  - ARIA labels for screen readers
  - Alt text support
  - Keyboard-accessible navigation controls
  - Visual instructions for users

- **Responsive Design**: Optimized for both desktop and mobile devices with touch-friendly controls

- **Backward Compatibility**: Regular images continue to work exactly as before with no changes to existing functionality

## Technical Implementation

### Backend

**Database Schema**
- New field: `is_equirectangular` (boolean, default: false) in the `media` table
- Migration: `2025_10_30_120000_add_is_equirectangular_to_media_table.php`

**Detection Logic** (`app/Util/Media/Image.php`)
The detection process runs during image processing and includes:

1. **Primary Method**: XMP GPano Metadata Detection
   - Searches for `GPano:ProjectionType="equirectangular"` in image XMP data
   - Industry standard for 360° panoramas from cameras and apps

2. **Fallback Method**: Aspect Ratio Analysis
   - Calculates width/height ratio
   - Identifies images with 2:1 ratio (±0.1 tolerance)
   - Common for equirectangular panoramas

**API Response** (`app/Transformer/Api/MediaTransformer.php`)
- Adds `is_equirectangular` field to media attachment API responses
- Returns `true` for 360° images, `false` for regular images

### Frontend

**Component** (`resources/assets/components/presenter/PhotoPresenter.vue`)

The PhotoPresenter component conditionally renders either:
- An interactive 360° viewer for equirectangular images
- A standard `<img>` element for regular photos

**Library Used**: [Photo Sphere Viewer v5](https://photo-sphere-viewer.js.org/)
- Loaded dynamically from jsDelivr CDN
- Only loaded when needed (lazy loading)
- Lightweight and well-maintained

**Viewer Features**:
- Default zoom level: 0 (fit to view)
- Mouse/touch drag to rotate view
- Mousewheel zoom enabled
- Touch gesture support (single and two-finger)
- Navigation controls for all directions
- Fullscreen toggle

## Usage

### For Users

1. **Upload a 360° Panorama**: Simply upload your equirectangular panoramic image as you would any other photo
2. **Automatic Detection**: The system automatically detects if the image is a 360° panorama
3. **Interactive Viewing**:
   - Drag to look around
   - Scroll to zoom in/out
   - Click the fullscreen button for immersive viewing
   - On mobile, use touch gestures to explore

### For Administrators

**Running the Migration**:
```bash
php artisan migrate
```

This adds the `is_equirectangular` field to the media table. Existing images default to `false`.

**Configuration**: No additional configuration is required. The feature works out of the box.

**Testing**:
1. Upload a genuine 360° panoramic image with GPano metadata
2. Upload a regular image with 2:1 aspect ratio (should be detected as 360°)
3. Upload regular photos with other aspect ratios (should display normally)

## Image Requirements

**Recommended Format**:
- Equirectangular projection (full sphere or partial)
- 2:1 aspect ratio (e.g., 4096x2048, 8192x4096)
- JPEG, PNG, or WebP format
- GPano metadata for accurate detection (optional but recommended)

**Supported Cameras/Apps**:
- Ricoh Theta series
- Insta360 cameras
- Google Street View app
- Any camera/app that produces equirectangular panoramas

## Performance Considerations

- **First Load**: The Photo Sphere Viewer library (~200KB) is downloaded only once and cached by the browser
- **Lazy Loading**: The library is only loaded when a 360° image is present
- **Memory Management**: Viewer instances are properly destroyed when navigating away
- **Mobile Optimization**: Viewer height is reduced on mobile devices (300px vs 500px on desktop)

## Browser Compatibility

The feature is compatible with all modern browsers that support:
- ES6 JavaScript
- WebGL (for rendering)
- Touch events (for mobile)

Supported browsers:
- Chrome/Edge 80+
- Firefox 75+
- Safari 13+
- Mobile browsers with WebGL support

## Troubleshooting

**360° Image Not Detected**:
- Ensure the image has a 2:1 aspect ratio
- Check if the image contains GPano XMP metadata
- Verify the image was processed (check `processed_at` field)

**Viewer Not Loading**:
- Check browser console for errors
- Ensure CDN access (jsDelivr) is not blocked
- Verify JavaScript is enabled

**Performance Issues**:
- Try reducing panorama resolution (4096x2048 recommended)
- Ensure the image is properly optimized
- Check network connection for CDN resources

## Future Enhancements

Potential improvements for future versions:
- Support for 180° panoramas (partial spheres)
- VR mode support
- Gyroscope support for mobile devices
- Custom navbar configurations
- Hotspot support (clickable points of interest)
- Multi-resolution panorama support for faster loading

## Credits

This feature uses the [Photo Sphere Viewer](https://github.com/mistic100/Photo-Sphere-Viewer) library by Damien "Mistic" Sorel, licensed under MIT.

## Related Files

- `app/Media.php` - Media model with `is_equirectangular` field
- `app/Util/Media/Image.php` - Image processing with 360° detection
- `app/Transformer/Api/MediaTransformer.php` - API transformer
- `resources/assets/components/presenter/PhotoPresenter.vue` - Frontend component
- `database/migrations/2025_10_30_120000_add_is_equirectangular_to_media_table.php` - Database migration
