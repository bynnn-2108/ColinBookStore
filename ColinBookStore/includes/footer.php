      <!-- Footer menutup konten customer dan menjadi fallback navigasi desktop. -->
      </main>
      <footer class="site-footer">
        <div>
          <strong>ColinBookStore</strong>
          <span>cerita baik, hari yang lebih panjang.</span>
        </div>
        <div>Jl. Buku Impian No. 10 · Indonesia</div>
        <div>© <?= date('Y') ?> ColinBookStore</div>
      </footer>
    </div>
  </div>

  <!-- Bottom navigation menggantikan rail pada viewport mobile. -->
  <nav class="bottom-nav" aria-label="Navigasi mobile">
    <a class="<?= $active_page === 'home' ? 'is-active' : '' ?>" href="<?= url('index.php') ?>">
<span>⌂</span>Beranda</a>
    <a class="<?= $active_page === 'catalog' ? 'is-active' : '' ?>" href="<?= url('buku.php') ?>">
<span>▦</span>Koleksi</a>
    <a href="<?= url('cart.php') ?>">
<span>🛒</span>Keranjang</a>
    <a class="<?= $active_page === 'settings' ? 'is-active' : '' ?>" href="<?= url(is_user_logged_in() ? 'pengaturan.php' : 'login.php') ?>">
<span>◌</span>Akun</a>
  </nav>
  <!-- Script global dipakai customer dan admin agar perilaku tema tetap konsisten. -->
  <script src="<?= url('assets/js/app.js') ?>">
</script>
</body>
</html>
