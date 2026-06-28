# Integration Notes - Header/Footer dengan Berbagai Framework

##Important Notes Sebelum Update Halaman

### Kalender.php (Tailwind CSS Special Case)

**Status**: Memerlukan perhatian khusus  
**Current**: Menggunakan Tailwind CSS  
**Issue**: Conflict dengan Bootstrap di guru_header.php

**Solusi yang Mungkin**:

#### Option 1: Replace Tailwind dengan Bootstrap (RECOMMENDED)

- Pros: Single CSS framework, konsisten dengan semua halaman lain
- Cons: Perlu refactor semua Tailwind classes menjadi Bootstrap
- Effort: Medium (2-3 jam)
- Command untuk find&replace Tailwind → Bootstrap

#### Option 2: Keep Tailwind, Add guru_header dengan Namespacing

- Pros: Minimal perubahan di kalender.php
- Cons: CSS conflict potential, more complex CSS
- Effort: High (3-4 jam)
- Requires: CSS purging strategy untuk Tailwind

#### Option 3: Create Wrapper Page tanpa CSS Framework

- Pros: Clean separation, flexible styling
- Cons: More HTML to write
- Effort: Medium (1.5-2 jam)

**Recommendation for kalender.php**: Option 1 (Replace Tailwind dengan Bootstrap)

---

##Pages with Different Resources

### Halaman dengan jQuery (Legacy)

- Recommended: Keep jQuery, import bersama dengan Bootstrap JS di guru_footer.php
- Tip: jQuery harus diload sebelum Bootstrap JS

### Halaman dengan Custom CSS Framework

- Recommended: Check for CSS variable conflicts
- Tip: Use CSS namespacing jika ada conflict (`.guru-header .class` untuk overrides)

### Halaman dengan React/Vue Components

- Special case - perlu discussI lebih lanjut
- Tip: May need componentization approach

---

## CSS Framework Compatibility

### Already Integrated (No Issues)

- ✅ Bootstrap 5.3 - guru_header.php, guru_footer.php
- ✅ Bootstrap Icons - Already in guru_header.php
- ✅ Google Fonts (Poppins) - Already in guru_header.php

### Conflict Potential

- ⚠️ Tailwind CSS - kalender.php (CSS namespace needed)
- ⚠️ Foundation CSS - unknown (test needed)
- ⚠️ MaterializeCSS - unknown (test needed)

### About to be Added (Check for Conflict)

- ? Custom CSS in specific pages - likely OK if well-scoped
- ? Font Awesome icons - check if class conflicts
- ? jQuery UI - check z-index conflicts

---

## Database Connection & Session Management

All files now integrated with:

- ✅ Session management (session_start in each page)
- ✅ Database connection ($conn from koneksi.php)
- ✅ Default functions (from functions.php)
- ✅ Lembaga data (data_lembaga() function)

### Variable Requirements Pre-Header

Before including guru_header.php, ensure these are set:

```php
// REQUIRED
$conn         // mysqli connection (from koneksi.php)
$lembaga      // institution data (from data_lembaga())

// STRONGLY RECOMMENDED
$pageTitle    // page title for browser tab
$nipguru      // teacher NIP (from $_SESSION['no_induk'])

// OPTIONAL (for notifications)
$tglskr       // current date Y-m-d
$hariini      // current day name (Indonesian)
$jadwalHariIni // today's schedule
$notifikasiData // notification array
```

---

## Responsive Breakpoints

All updated pages now follow:

- **Mobile First**: < 768px
- **Desktop**: ≥ 768px

### Testing Viewports

- iPhone SE (375px)
- iPhone 12 (390px)
- Tablet (768px)
- Desktop (1024px+)
- Large Desktop (1920px+)

---

## File Update Checklist

For each page you update, verify:

### Pre-Update

- [ ] Backup original file (name_backup.php)
- [ ] Note any custom dependencies
- [ ] Check for CSS framework conflicts
- [ ] List all custom JavaScript functions

### During Update

- [ ] Extract PHP logic (before <!DOCTYPE)
- [ ] Include guru_header.php at proper place
- [ ] Preserve page-specific CSS in <style> tag
- [ ] Wrap content in <div class="page-container">
- [ ] Include guru_footer.php at end
- [ ] Adjust CSS for responsive if needed

### Post-Update

- [ ] Test desktop (≥ 768px)
- [ ] Test mobile (< 768px)
- [ ] Test sidebar toggle (mobile)
- [ ] Test notification bell
- [ ] Test all links work
- [ ] Check console for JS errors
- [ ] Verify no CSS conflicts

---

## Performance Considerations

### CSS Loading Order (guru_header.php)

1. Bootstrap CSS (CDN)
2. Bootstrap Icons (CDN)
3. Google Fonts - Poppins (CDN)
4. Custom CSS from guru_header.php
5. Page-specific CSS (in <style> tag)

### JS Loading Order (guru_footer.php)

1. Bootstrap JS (CDN) - **FIRST**
2. Page-specific JS (if any)
3. Utility functions from guru_footer.php

### Tips untuk Performance

- Use CSS variables untuk theming
- Defer non-critical JS
- Minify custom CSS sebelum production
- Use single CDN domain jika possible

---

## Security Considerations

### Already Handled in guru_header.php & guru_footer.php

- ✅ htmlspecialchars() untuk output sanitization
- ✅ mysqli_real_escape_string untuk query
- ✅ Session validation di top of each page
- ✅ CSRF-like protection via session dependency

### Still Need to Check in Page Logic

- [ ] Input validation (GET/POST params)
- [ ] File upload validation (if any)
- [ ] Database query optimization
- [ ] XSS prevention for user output

---

## Known Issues & Workarounds

### Issue 1: Notification Not Showing

**Cause**: $notifikasiData not properly set  
**Fix**: Include guru_notifikasi.php before guru_header.php

### Issue 2: Sidebar Not Toggling

**Cause**: guru_footer.php JavaScript not loaded  
**Fix**: Verify guru_footer.php included at bottom

### Issue 3: CSS Conflicts

**Cause**: Multiple CSS frameworks loaded  
**Fix**: Use CSS namespacing or replace with single framework

### Issue 4: Responsive Layout Not Working

**Cause**: Missing viewport meta tag or conflicting CSS  
**Fix**: Verify guru_header.php includes viewport meta

---

## Mobile Footer Navigation Links

Current mobile footer (guru_footer.php) includes:

- Home (guru.php)
- Jurnal (guru_jurnal.php)
- Nilai (nilai.php)
- Presensi (presensi.php)
- Kalender (kalender.php)

These are shown/hidden automatically based on viewport width.

---

## Browser Support

### Fully Supported

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Chrome Mobile (latest)
- ✅ Safari iOS (14+)
- ✅ Samsung Internet (14+)

### Partial Support

- ⚠️ IE 11 (not tested, likely not supported)

### Not Supported

- ❌ IE 10 and below

---

## Future Improvements

### Phase 2 (Consider for Next Release)

- [ ] Dark mode toggle
- [ ] Profile dropdown menu in header
- [ ] Search functionality
- [ ] Breadcrumb navigation
- [ ] Quick action menu customization

### Phase 3 (Long term)

- [ ] Offline support (Service Workers)
- [ ] Push notifications
- [ ] Analytics integration
- [ ] Performance monitoring

---

## Support & Troubleshooting

### Common Questions

**Q: Can I modify guru_header.php colors?**
A: Yes, edit CSS variables in guru_header.php. Search for "gradient" and "color" for quick locate.

**Q: What if my page has modal dialogs?**
A: Should work fine. Ensure modal z-index > 1000 if needed.

**Q: Can I add custom buttons to header?**
A: Yes, edit `.header-right` section in guru_header.php.

**Q: Is the sidebar editable per page?**
A: Not recommended. For consistency, keep sidebar items same across all pages. If needed per-page customization, consider adding $pageMenuItems variable.

---

## Update Status Timeline

**Completed** (as of April 14, 2026):

- guru.php ✅
- guru_jurnal.php ✅

**In Queue** (ready to update):

- nilai.php ⏳
- presensi.php ⏳
- history-tugas.php ⏳
- twibbon.php ⏳
- validasi-izin.php ⏳
- kalender.php ⏳ (needs Tailwind resolution)

---

**Latest Update**: April 14, 2026  
**Next Review**: After completing 4+ additional pages
