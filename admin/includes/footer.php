</div>
<!-- /.container-fluid -->
</div>
<!-- /.app-content -->
</main>
<!-- /.app-main -->

<!-- Footer AdminLTE -->
<footer class="app-footer shadow-sm">

  <strong>Hak Cipta &copy; 2026 <a href="../index.php" class="text-decoration-none">Barasuara Official</a>.</strong>
</footer>
</div>
<!-- /.app-wrapper -->

<!-- Third Party Plugin: OverlayScrollbars -->
<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
  crossorigin="anonymous"></script>

<!-- Required Plugin: Popper.js for Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
  crossorigin="anonymous"></script>

<!-- Required Plugin: Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>

<!-- Required Plugin: AdminLTE v4 JS -->
<script src="dist/js/adminlte.min.js"></script>

<!-- Optional Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js" crossorigin="anonymous"></script>

<!-- OverlayScrollbars Configure Script -->
<script>
  const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
  const DefaultScrollbar = {
    scrollbarTheme: 'os-theme-light',
    scrollbarAutoHide: 'leave',
    scrollbarClickScroll: true,
  };
  document.addEventListener('DOMContentLoaded', function () {
    const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
    const isMobile = window.innerWidth <= 992;
    if (sidebarWrapper && typeof OverlayScrollbarsGlobal !== 'undefined' && !isMobile) {
      OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
        scrollbars: {
          theme: DefaultScrollbar.scrollbarTheme,
          autoHide: DefaultScrollbar.scrollbarAutoHide,
          clickScroll: DefaultScrollbar.scrollbarClickScroll,
        },
      });
    }
  });
</script>
</body>

</html>