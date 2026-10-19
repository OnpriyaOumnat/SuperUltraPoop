    </main>

    <footer class="px-6 py-4 text-center text-xs text-kawaii-ink/50">
      © <?= date('Y') ?> SuperUltraPoop Admin · Made with 🧸
    </footer>
  </div><!-- /พื้นที่เนื้อหา -->

  <script>
    // เปิด/ปิด Sidebar บนมือถือ
    (function () {
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('sidebarOverlay');
      const toggle  = document.getElementById('sidebarToggle');
      function setOpen(open) {
        sidebar.classList.toggle('-translate-x-full', !open);
        overlay.classList.toggle('hidden', !open);
      }
      toggle.addEventListener('click', () => setOpen(sidebar.classList.contains('-translate-x-full')));
      overlay.addEventListener('click', () => setOpen(false));
    })();

    // ยืนยันก่อนลบ: ใส่ data-confirm="ข้อความ" ที่ปุ่ม/ฟอร์ม
    document.querySelectorAll('[data-confirm]').forEach(el => {
      el.addEventListener('click',  e => { if (el.tagName !== 'FORM' && !confirm(el.dataset.confirm)) e.preventDefault(); });
      el.addEventListener('submit', e => { if (!confirm(el.dataset.confirm)) e.preventDefault(); });
    });
  </script>
  <?= $extraScripts ?? '' /* หน้าไหนต้องการสคริปต์ท้ายหน้า ให้ตั้งตัวแปร $extraScripts */ ?>
</body>
</html>
