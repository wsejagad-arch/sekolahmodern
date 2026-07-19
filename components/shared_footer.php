<!-- Footer -->
<footer class="sticky-footer bg-white" style="background-color: transparent !important; border-top: 1px solid var(--school-border, #e2e8f0) !important; padding: 24px 0 80px 0 !important; margin-top: auto;">
  <div class="container my-auto">
    <div class="copyright text-center my-auto">
      <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; flex-wrap:wrap; padding:10px 0 6px;">
        <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; margin-bottom:16px;">
          <span style="font-size:13px; color:#64748b; font-weight:600;">
            <i class="fas fa-school mr-1" style="color:#1a3c6e;"></i>
            SMA Negeri 1 Sumber
          </span>
          <span style="font-size:12px; color:#94a3b8;">
            &copy; 2026 &mdash; Sistem Manajemen Pendidikan SMA Negeri 1 Sumber
          </span>

        </div>

        <?php if (!isset($_SESSION['hak_akses']) || (int)$_SESSION['hak_akses'] !== 2): ?>
        <div style="display:flex; align-items:center; justify-content:center; gap:10px; flex-wrap:wrap; flex-direction:column;">
          <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:center;">
            <a href="https://www.instagram.com/sman1sumber_rembang" target="_blank" rel="noopener noreferrer" class="btn btn-sm" style="border-radius:999px; background:#fdf2f8; color:#db2777; border:1px solid #f9a8d4; font-weight:700; padding:6px 12px; font-size:12px; display:inline-flex; align-items:center; gap:4px; text-decoration:none;">
              <i class="fab fa-instagram"></i>@sman1sumber_rembang
            </a>
            <a href="https://www.facebook.com/sman1sumber_rembang" target="_blank" rel="noopener noreferrer" class="btn btn-sm" style="border-radius:999px; background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; font-weight:700; padding:6px 12px; font-size:12px; display:inline-flex; align-items:center; gap:4px; text-decoration:none;">
              <i class="fab fa-facebook-square"></i>Facebook
            </a>
            <a href="https://www.tiktok.com/@sman1sumber_rembang" target="_blank" rel="noopener noreferrer" class="btn btn-sm" style="border-radius:999px; background:#f8fafc; color:#111827; border:1px solid #cbd5e1; font-weight:700; padding:6px 12px; font-size:12px; display:inline-flex; align-items:center; gap:4px; text-decoration:none;">
              <i class="fab fa-tiktok"></i>TikTok
            </a>
          </div>
          <a href="https://sman1sumber.sch.id" target="_blank" rel="noopener noreferrer" class="btn btn-sm" style="border-radius:999px; background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; font-weight:700; padding:6px 12px; font-size:12px; display:inline-flex; align-items:center; gap:4px; text-decoration:none;">
            <i class="fas fa-globe"></i>sman1sumber.sch.id
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</footer>
<!-- End of Footer -->
