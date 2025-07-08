    <!-- Temel Kütüphaneler -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- === YENİ MOBİL MENÜ SCRİPTİ (TÜM SAYFALARDA ÇALIŞIR) === -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.querySelector('.mobile-menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        const mainWrapper = document.querySelector('.main-wrapper');

        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('open');
                mainWrapper.classList.toggle('sidebar-open');
            });
        }
        
        // Menü dışına tıklanınca kapat
        mainWrapper.addEventListener('click', function(e) {
            if(e.target === mainWrapper) {
                 sidebar.classList.remove('open');
                 mainWrapper.classList.remove('sidebar-open');
            }
        });
    });
    </script>
    
    <!-- Sayfaya Özel Scriptler -->
    <?php 
    if(isset($_SERVER['PHP_SELF'])){
        $currentPage = basename($_SERVER['PHP_SELF']);

        if ($currentPage == 'teklif_listesi.php'): 
            // ... (arama scripti buraya eklenebilir)
        elseif ($currentPage == 'teklif_olustur.php' || $currentPage == 'teklif_revize_et.php'):
            // ... (teklif oluşturma scripti buraya eklenebilir)
        elseif ($currentPage == 'teklif_view.php'):
            // ... (mail modal scripti buraya eklenebilir)
        endif; 
    }
    ?>
    
    <!-- Genel Tooltip Scripti -->
    <script>
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>
</html>