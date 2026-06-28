# 🔧 Setup & Installation Guide - Guru Mobile App

## 📦 File Structure

```
pages/guru/
├── guru_mobile_app.php                    ← Main mobile app file (production)
├── guru_mobile_app_with_external_css.php  ← Version with external CSS (optional)
├── GURU_MOBILE_APP_DOKUMENTASI.md        ← User documentation
├── GURU_MOBILE_APP_SETUP.md              ← This file
│
└── css/
    ├── guru-mobile-app.css               ← Standalone CSS file
    └── guru-mobile-app.min.css           ← Minified version (optional)
```

---

## 🚀 Quick Start

### Option 1: Use Default Version (guru_mobile_app.php)

File ini sudah self-contained dengan semua styling di dalam `<style>` tag.

**Setup:**

1. Pastikan file sudah di `/pages/guru/guru_mobile_app.php`
2. Akses via browser: `http://localhost/jurnal/pages/guru/guru_mobile_app.php`
3. Done! ✅

**Kelebihan:**

- ✅ Single file, no dependencies
- ✅ Faster load (no separate CSS request)
- ✅ Easy to deploy

---

### Option 2: Use External CSS Version (Recommended for large teams)

Gunakan versi dengan external CSS untuk maintainability yang lebih baik.

**Setup:**

1. Copy `guru_mobile_app_with_external_css.php` ke folder `pages/guru/`
2. Pastikan `css/guru-mobile-app.css` sudah ada
3. Update link di file PHP:
   ```html
   <link rel="stylesheet" href="css/guru-mobile-app.css" />
   ```
4. Akses: `http://localhost/jurnal/pages/guru/guru_mobile_app_with_external_css.php`

**Kelebihan:**

- ✅ CSS bisa di-share dengan file lain
- ✅ Lebih mudah di-maintain
- ✅ Better for large projects

---

## 📋 Requirements

### Backend

- ✅ PHP 7.0+ (tested on PHP 7.4)
- ✅ MySQL/MariaDB
- ✅ Session enabled
- ✅ Required files:
  - `koneksi.php` (database connection)
  - `functions.php` (helper functions)
  - Folder `foto/` (for teacher photos)
  - Folder `img/` (for school logo)

### Frontend

- ✅ Modern browser (Chrome, Firefox, Safari, Edge)
- ✅ JavaScript enabled
- ✅ Bootstrap 5.3.0 CDN
- ✅ jQuery 3.6.0 CDN

### Database Tables

- `tbl_guru` - Teacher data
- `tbl_mapel_ampu` - Teacher schedule
- `tbl_setting` - School settings
- `tbl_materi` - Learning materials
- `tbl_penilaian_item` - Assessment items
- `tbl_nilai_item` - Student grades
- `tbl_siswa` - Student data

---

## 🔐 Security Checklist

Before deploying to production:

- [ ] Database credentials in `koneksi.php` are secure
- [ ] Session cookie settings are secure (HTTPOnly, Secure flag)
- [ ] Input validation is enabled in helper files
- [ ] CSRF tokens are implemented
- [ ] SQL injection prevention is in place
- [ ] User permissions are verified
- [ ] Error logging is configured
- [ ] SSL/HTTPS is enabled (recommended)
- [ ] File uploads are restricted
- [ ] API endpoints are authenticated

---

## 🎨 Customization Guide

### 1. Change Color Scheme

**Option A: Edit CSS Variables (guru-mobile-app.css)**

```css
:root {
  --primary: #0d6efd; /* Change to your primary color */
  --primary-dark: #0856ca; /* Darker variant */
  --primary-light: #4f46e5; /* Lighter variant */
  /* ... other colors ... */
}
```

**Option B: Edit Inline Styles (guru_mobile_app.php)**
Search and replace gradient values:

```css
linear-gradient(135deg, #0d6efd 0%, #0856ca 100%)
/* Change #0d6efd and #0856ca */
```

### 2. Change Logo/Header

Edit header section in guru_mobile_app.php:

```php
<img src="../../img/<?= $lembaga['logo']; ?>" alt="Logo">
```

Replace with your custom logo path if needed.

### 3. Add Custom Quick Action Button

Add new button in quick-actions-grid:

```html
<button class="quick-action-btn qa-primary" id="qaCustomAction">
  <i class="bi bi-icon-name"></i>
  Custom Action
</button>
```

Add JavaScript handler:

```javascript
$("#qaCustomAction").on("click", function () {
  window.location = "path-to-your-page.php";
});
```

### 4. Modify Greeting Message

Edit greeting section:

```php
<p class="greeting-text">
  Halo, <?= htmlspecialchars($dataguru['nama_guru']); ?> 👋
</p>
<p class="greeting-subtext">
  Your custom message here
</p>
```

### 5. Add New Modal

Copy existing modal structure:

```html
<div class="modal fade" id="newModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Your Title</h6>
        <button
          type="button"
          class="btn-close"
          data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- Your content -->
      </div>
    </div>
  </div>
</div>
```

---

## 📱 Testing Checklist

### Desktop Testing

- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Screen size: 1920x1080, 1366x768

### Tablet Testing

- [ ] iPad (Safari)
- [ ] iPad Pro (different sizes)
- [ ] Android tablet (Chrome)
- [ ] Screen sizes: 768x1024, 1024x768

### Mobile Testing

- [ ] iPhone 12/13/14/15
- [ ] iPhone SE
- [ ] Android devices (various sizes)
- [ ] Screen sizes: 375x667, 390x844, 414x896

### Functional Testing

- [ ] Login works
- [ ] Header displays correctly
- [ ] Quick actions are clickable
- [ ] Schedule cards appear correctly
- [ ] Modals open/close smoothly
- [ ] Bottom navigation works
- [ ] Forms can be submitted
- [ ] Responsive on all screen sizes
- [ ] Performance is acceptable (< 2s load time)

### Browser Console

- [ ] No JavaScript errors
- [ ] No CSS warnings
- [ ] No security warnings
- [ ] Network requests are successful

---

## 🚀 Deployment Steps

### 1. Production Preparation

```bash
# Minify CSS (if using external CSS)
npm install -g csso-cli
csso pages/guru/css/guru-mobile-app.css -o pages/guru/css/guru-mobile-app.min.css

# Backup existing files
cp pages/guru/guru_legacy.php pages/guru/guru_legacy.php.backup
```

### 2. Update guru.php Redirect

Option A: Auto-detect mobile

```php
<?php
$is_mobile = preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $_SERVER['HTTP_USER_AGENT']);

if ($is_mobile) {
    header('Location: guru_mobile_app.php');
} else {
    header('Location: guru_legacy.php');
}
exit;
?>
```

Option B: Always use mobile

```php
<?php
header('Location: guru_mobile_app.php');
exit;
?>
```

Option C: Let user choose

```php
<?php
// Add UI to select version
if (isset($_POST['version'])) {
    if ($_POST['version'] === 'mobile') {
        header('Location: guru_mobile_app.php');
    } else {
        header('Location: guru_legacy.php');
    }
    exit;
}

// Show selection interface
?>
<div class="version-selector">
    <form method="POST">
        <button type="submit" name="version" value="mobile">Mobile Version</button>
        <button type="submit" name="version" value="desktop">Desktop Version</button>
    </form>
</div>
```

### 3. Test on Production Server

- [ ] Test all features
- [ ] Check database connectivity
- [ ] Verify file paths
- [ ] Check error logs
- [ ] Monitor performance

### 4. Rollback Plan

If issues occur:

```bash
# Revert to legacy version
cp pages/guru/guru.php pages/guru/guru.php.backup
# Restore original
# Edit guru.php to redirect to guru_legacy.php
```

---

## 🐛 Troubleshooting

### Problem: Styles not loading

**Solution:**

1. Clear browser cache (Ctrl+Shift+Del)
2. Hard refresh page (Ctrl+F5)
3. Check CSS file exists in correct path
4. Check browser console for 404 errors

### Problem: Modals not appearing

**Solution:**

1. Check Bootstrap JS is loaded: `typeof Bootstrap` in console
2. Check jQuery is loaded: `typeof jQuery` in console
3. Verify modal IDs match in HTML and JavaScript
4. Check for JavaScript errors in console

### Problem: Database connection error

**Solution:**

1. Check `koneksi.php` file
2. Verify database credentials
3. Check if MySQL/MariaDB is running
4. Check error_log in root folder

### Problem: Images not loading

**Solution:**

1. Check folder paths: `img/`, `foto/`, `materi/`
2. Verify file permissions (644 for files)
3. Check file exists in database path
4. Use full URL instead of relative path if needed

### Problem: Slow performance

**Solution:**

1. Check database query performance
2. Enable browser caching in .htaccess
3. Minify CSS and JavaScript
4. Optimize images
5. Check server resources

---

## 📊 Performance Optimization

### CSS Optimization

```bash
# Use minified CSS in production
<link rel="stylesheet" href="css/guru-mobile-app.min.css">
```

### JavaScript Optimization

```html
<!-- Defer non-critical scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>
<script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
  defer></script>
```

### Image Optimization

```bash
# Optimize images
ffmpeg -i image.jpg -c:v libx264 -crf 28 image_optimized.jpg
```

### Caching Headers

Add to `.htaccess`:

```apache
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresDefault "access plus 1 month"
  ExpiresByType text/css "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
</IfModule>
```

---

## 🔄 Version History

| Version | Date         | Changes         |
| ------- | ------------ | --------------- |
| 1.0     | May 13, 2026 | Initial release |
| -       | -            | -               |

---

## 📞 Support & Contact

- **Bug Reports:** Email IT Department
- **Feature Requests:** Submit via admin panel
- **Documentation:** See GURU_MOBILE_APP_DOKUMENTASI.md
- **Technical Issues:** Check error_log or browser console

---

## 📄 License

© 2026 School Management System. All rights reserved.

Last Updated: May 13, 2026
