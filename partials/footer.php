    <!-- jQuery, Bootstrap, Select2 scriptleri -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Chart.js (Sadece Raporlar ve Dashboard'da Gerekli) -->
    <?php if(isset($_SERVER['PHP_SELF']) && (basename($_SERVER['PHP_SELF']) == 'dashboard.php' || basename($_SERVER['PHP_SELF']) == 'raporlar.php')): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>

    <!-- ================================================================= -->
    <?php 
    if(isset($_SERVER['PHP_SELF'])){
        $currentPage = basename($_SERVER['PHP_SELF']);

        // --- TEKLİF LİSTESİ SAYFASI ---
        if ($currentPage == 'teklif_listesi.php'): 
    ?>
        <script>
        $(document).ready(function(){
            $("#proposalSearchInput").on("keyup", function() { /* ... arama kodu ... */ });
        });
        </script>
    <?php 
        // --- TEKLİF OLUŞTURMA/DÜZENLEME SAYFALARI ---
        elseif ($currentPage == 'teklif_olustur.php' || $currentPage == 'teklif_revize_et.php'): 
    ?>
        <script>
        $(document).ready(function() {
            // ... (SENİN UZUN VE ÇALIŞAN TEKLİF OLUŞTURMA KODUN BURADA, DEĞİŞİKLİK YOK) ...
        });
        </script>
    <?php
        // --- TEKLİF GÖRÜNTÜLEME SAYFASI ---
        elseif ($currentPage == 'teklif_view.php'):
    ?>
        <script>
        $(document).ready(function() {
            $('#mailGonderModal').on('show.bs.modal', function (event) { /* ... mail modal doldurma ... */ });
            $('#sendMailForm').on('submit', function(e) { /* ... mail gönderme AJAX ... */ });
            $(document).on('click', '.whatsapp-gonder-btn', function() { /* ... WhatsApp gönderme ... */ });
        });
        </script>
    <?php
        // --- DASHBOARD SAYFASI ---
        elseif ($currentPage == 'dashboard.php'):
    ?>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('teklifDurumGrafigi').getContext('2d');
            const labels = <?php echo json_encode($chart_labels ?? []); ?>;
            const dataValues = <?php echo json_encode($chart_values ?? []); ?>;
            new Chart(ctx, { /* ... dashboard grafik ayarları ... */ });
        });
        </script>
    <?php 
        endif; 
    }
    ?>
    
    <!-- === YENİ EKLENEN KOD: MOBİL MENÜ KONTROLÜ (TÜM SAYFALARDA ÇALIŞIR) === -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var menuToggleButton = document.querySelector('.mobile-menu-toggle');
            var sidebar = document.querySelector('.sidebar');
            
            if (menuToggleButton && sidebar) {
                menuToggleButton.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
        });
    </script>

    <!-- Genel Tooltip Scripti (Tüm sayfalarda çalışır) -->
    <script>
        var tooltipTriggerList = Array.prototype.slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
    
</body>
</html>