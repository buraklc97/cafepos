<?php
// src/footer.php
?>
  </main>
  <footer class="text-center py-3 mt-5 small text-white bg-dark shadow-sm" style="letter-spacing:0.01em;">
    &copy; <?= date('Y') ?> Cafe POS
  </footer>

  <!-- Genel Uyarı Modalları -->
  <div class="modal fade" id="alertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center"></div>
        <div class="modal-footer justify-content-center border-0">
          <button type="button" class="btn btn-primary ok-btn" data-bs-dismiss="modal">Tamam</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center"></div>
        <div class="modal-footer justify-content-center border-0">
          <button type="button" class="btn btn-secondary no-btn" data-bs-dismiss="modal">Vazgeç</button>
          <button type="button" class="btn btn-primary yes-btn">Evet</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <?php if (!empty($_SESSION['alert'])): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      showAlert(<?= json_encode($_SESSION['alert']) ?>);
    });
  </script>
  <?php unset($_SESSION['alert']); endif; ?>
</body>
</html>
