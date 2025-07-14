    <!-- jQuery, Bootstrap, Select2 scriptleri -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- ================================================================= -->
    <?php 
    $currentPage = basename($_SERVER['PHP_SELF']);
    if ($currentPage == 'teklif_listesi.php'): 
    ?>
    <script>
    $(document).ready(function(){
        $("#proposalSearchInput").on("keyup", function() { /* ... arama kodu ... */ });
    });
    </script>
    <?php 
    elseif ($currentPage == 'teklif_olustur.php' || $currentPage == 'teklif_revize_et.php'): 
    ?>
    <script>
    $(document).ready(function() {
        // === GENEL DEĞİŞKENLER ===
        var customersData = <?php echo json_encode($customers ?? []); ?>;
        var mevcutTeklif = <?php echo json_encode($teklif ?? null); ?>;

        // === FONKSİYONLAR ===
        function addProductRow() {
            var rowCount = $('#teklifKalemleri tbody tr').length;
            var newRow = `<tr>
                    <td><img src="assets/images/placeholder.png" class="product-image" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;"></td>
                    <td><select class="form-control product-select" name="products[${rowCount}][id]"></select></td>
                    <td><input type="number" class="form-control quantity" name="products[${rowCount}][quantity]" value="1" step="any"></td>
                    <td><input type="number" class="form-control unit-price" name="products[${rowCount}][unit_price]" step="any"></td>
                    <td><input type="text" class="form-control text-end fw-bold total-price" readonly></td>
                    <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
                </tr>`;
            $('#teklifKalemleri tbody').append(newRow);
            var newSelect = $('#teklifKalemleri tbody tr:last-child .product-select');
            newSelect.select2({
                placeholder: 'Ürün arayın...',
                ajax: { url: 'api_get_products.php', dataType: 'json', delay: 250, processResults: data => ({ results: data.results }), cache: true }
            });
        }
        
        function updateAllCalculations() {
    var currency = $('#currency').val();
    var currencySymbol = ' ₺';
    if (currency === 'USD') currencySymbol = ' $';
    else if (currency === 'EUR') currencySymbol = ' €';
    
    var toplamBrut = 0;
    $('#teklifKalemleri tbody tr').each(function() {
        var row = $(this);
        var quantity = parseFloat(row.find('.quantity').val()) || 0;
        var unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
        var lineTotal = quantity * unitPrice;
        row.find('.total-price').val(lineTotal.toFixed(2));
        toplamBrut += lineTotal;
    });

    // İskonto Tutarı'nı doğrudan input'tan al
    var genelIskontoTutar = 0;
    if (!$('.discount-row').hasClass('d-none')) {
        genelIskontoTutar = parseFloat($('#genelIskontoTutar').val()) || 0;
    }
    
    // İskonto Yüzdesi'ni Tutar'a göre hesapla
    if (toplamBrut > 0) {
        var yuzde = (genelIskontoTutar / toplamBrut) * 100;
        $('#genelIskontoYuzde').val(yuzde > 0 ? yuzde.toFixed(2) : '');
    } else {
        $('#genelIskontoYuzde').val('');
    }
    
    var araToplamNet = toplamBrut - genelIskontoTutar;
    var kdvPercent = parseFloat($('#kdvOrani').val()) || 0;
    var kdvAmount = araToplamNet * (kdvPercent / 100);
    var grandTotal = araToplamNet + kdvAmount;

    var formatter = new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    
    $('#toplamBrut').text(formatter.format(toplamBrut) + currencySymbol);
    $('#araToplamNet').text(formatter.format(araToplamNet) + currencySymbol);
    $('#kdvTutari').text(formatter.format(kdvAmount) + currencySymbol);
    $('#genelToplam').text(formatter.format(grandTotal) + currencySymbol);

    // Bu alanı artık kullanmıyoruz ama kalsın
    // $('#genelIskontoGosterim').text(`(${formatter.format(genelIskontoTutar)})${currencySymbol}`);

    $('#kdv_rate_hidden').val(kdvPercent);
}

        // === OLAY DİNLEYİCİLERİ ===
        $('#urunEkleBtn').on('click', addProductRow);
        $('#teklifKalemleri').on('click', '.remove-row', function() { $(this).closest('tr').remove(); updateAllCalculations(); });
        $('#teklifKalemleri').on('change keyup', '.quantity, .unit-price', updateAllCalculations);
        $('#teklifKalemleri').on('select2:select', '.product-select', function(e) {
            var row = $(this).closest('tr');
            var selectedProduct = e.params.data;
            var currency = $('#currency').val();
            if (selectedProduct && selectedProduct.prices && selectedProduct.prices[currency]) {
                row.find('.unit-price').val(parseFloat(selectedProduct.prices[currency]).toFixed(2));
            } else { row.find('.unit-price').val(''); }
            var imagePath = selectedProduct && selectedProduct.fotograf_yolu ? selectedProduct.fotograf_yolu : 'assets/images/placeholder.png';
            row.find('.product-image').attr('src', imagePath);
            updateAllCalculations();
        });
        $(document).on('change', '#currency, #kdvOrani', updateAllCalculations);

        $('#toggleDiscountBtn').on('click', function() {
            var $this = $(this);
            $('.discount-row, .discount-related').toggleClass('d-none');
            $this.toggleClass('btn-outline-secondary btn-secondary');
            if ($('.discount-row').hasClass('d-none')) {
                $('#genelIskontoYuzde, #genelIskontoTutar').val('');
                $this.html('<i class="fas fa-percent"></i> İskonto Uygula');
            } else {
                $this.html('<i class="fas fa-percent"></i> İskontoyu Kaldır');
            }
            updateAllCalculations();
        });

       $('#genelIskontoTutar').on('input', function() {
    var tutar = parseFloat($(this).val()) || 0;
    var brutToplam = parseFloat($('#toplamBrut').text().replace(/\./g, '').replace(',', '.')) || 0;
    if (brutToplam > 0) {
        var yuzde = (tutar / brutToplam) * 100;
        $('#genelIskontoYuzde').val(yuzde > 0 ? yuzde.toFixed(2) : '');
    } else { $('#genelIskontoYuzde').val(''); }
    updateAllCalculations();
});

        $('#genelIskontoTutar').on('input', function() {
            var tutar = parseFloat($(this).val()) || 0;
            var brutToplam = parseFloat($('#toplamBrut').text().replace(/\./g, '').replace(',', '.')) || 0;
            if (brutToplam > 0) {
                var yuzde = (tutar / brutToplam) * 100;
                $('#genelIskontoYuzde').val(yuzde > 0 ? yuzde.toFixed(2) : '');
            } else { $('#genelIskontoYuzde').val(''); }
            updateAllCalculations();
        });

        // === SAYFA YÜKLENDİĞİNDE ÇALIŞACAK KODLAR ===
        if (mevcutTeklif) {
            // REVİZYON SAYFASI
            $('#teklifKalemleri .product-select').each(function() {
                $(this).select2({
                    placeholder: 'Ürün arayın...',
                    ajax: { url: 'api_get_products.php', dataType: 'json', delay: 250, processResults: data => ({ results: data.results }), cache: true }
                });
            });
            $('#kdvOrani').val(parseFloat(mevcutTeklif.tax_rate).toFixed(0));
            var genelIskonto = parseFloat(mevcutTeklif.total_discount) || 0;
            if (genelIskonto > 0) {
                $('#toggleDiscountBtn').trigger('click');
                $('#genelIskontoTutar').val(genelIskonto.toFixed(2));
                $('#genelIskontoTutar').trigger('input');
            } else {
                updateAllCalculations();
            }
        } else {
            // YENİ TEKLİF SAYFASI
            $('#customer_id').select2({
                placeholder: "Müşteri arayın...",
                data: customersData
            }).on('select2:select', function (e) {
                var data = e.params.data;
                var selectedCustomer = customersData.find(c => c.id == data.id);
                if (selectedCustomer && selectedCustomer.yetkili_ismi) {
                    $('#contact_person').val(selectedCustomer.yetkili_ismi);
                } else { $('#contact_person').val(''); }
            });
            if ($('#teklifKalemleri tbody tr').length === 0) {
                addProductRow();
            }
        }
		        // === YENİ EKLENECEK KOD: ŞABLON YÜKLEME (MEVCUT YAPIYI BOZMAZ) ===
        
        // 1. Şablon Dropdown'ını Select2 ile başlat
        $('#template_id').select2({
            placeholder: "Bir şablon seçin...",
            allowClear: true // Temizleme (X) butonu ekler
        });

        // 2. Şablon seçildiğinde çalışacak olay dinleyici
        $('#template_id').on('change', function() {
            var templateId = $(this).val();
            
            // Eğer seçim kaldırıldıysa, tabloyu temizle ve bir boş satır ekle
            if (!templateId) {
                $('#teklifKalemleri tbody').empty();
                addProductRow(); // Bu senin mevcut addProductRow fonksiyonun
                updateAllCalculations(); // Bu da senin mevcut hesaplama fonksiyonun
                return;
            }

            // AJAX ile şablonun ürünlerini getir
            $.ajax({
                url: 'api_get_template_details.php?id=' + templateId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.items) {
                        $('#teklifKalemleri tbody').empty(); // Önce mevcut satırları temizle
                        
                        // Gelen her bir ürün için yeni bir satır ekle
                        response.items.forEach(function(item) {
                            addProductRow(); // Senin mevcut fonksiyonun ile boş bir satır ekle
                            
                            var newRow = $('#teklifKalemleri tbody tr:last-child');
                            var newSelect = newRow.find('.product-select');
                            
                            // Yeni eklenen satırdaki alanları doldur
                            var option = new Option(item.text, item.id, true, true);
                            newSelect.append(option).trigger('change');
                            newSelect.data('prices', item.prices); // Fiyatları sakla
                            
                            newRow.find('.quantity').val(item.quantity);
                            
                            // Ürün görselini güncelle
                            var imagePath = item.fotograf_yolu ? item.fotograf_yolu : 'assets/images/placeholder.png';
                            newRow.find('.product-image').attr('src', imagePath);
                        });

                        // Tüm satırlar eklendikten sonra bir kez hesapla
                        updateAllCalculations();
                    }
                },
                error: function() {
                    alert('Şablon yüklenirken bir hata oluştu.');
                }
            });
        });
        // === YENİ KOD SONU ===
    });
    </script>
    <?php endif; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
		
    </script>
	<?php
// === 2. EKLENECEK KOD BLOĞU: JAVASCRIPT ===
// Mevcut sayfanın adını alıyoruz
$currentPage = basename($_SERVER['PHP_SELF']);

// Bu kodun sadece teklif_view.php sayfasında çalışmasını sağlıyoruz
if ($currentPage == 'teklif_view.php'):
?>
<script>
$(document).ready(function() {
    // "Mail Gönder" butonuna tıklandığında modal içindeki formu doldur
    $('#mailGonderModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var proposalId = button.data('proposal-id');
        var proposalNo = button.data('proposal-no');
        var customerEmail = button.data('customer-email');
        var customerName = button.data('customer-name');

        var modal = $(this);
        modal.find('#mail_proposal_id').val(proposalId);
        modal.find('#to_email').val(customerEmail);
        modal.find('#subject').val(proposalNo + ' Numaralı Fiyat Teklifiniz');
        modal.find('#mail_body').val('Sayın ' + customerName + ',\n\nİlginize teşekkür eder, teklifimizi ekte bilgilerinize sunarız.\n\nİyi çalışmalar dileriz.');
    });

    // Modal'daki "Gönder" butonuna basıldığında AJAX ile maili gönder
    $('#sendMailForm').on('submit', function(e) {
        e.preventDefault(); 

        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        var originalButtonText = submitButton.html();
        var alertDiv = $('#mail-response-alert');

        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Gönderiliyor...');
        alertDiv.hide();

        $.ajax({
            type: 'POST',
            url: 'send_mail.php', 
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alertDiv.removeClass('alert-danger').addClass('alert-success').text(response.message).show();
                    setTimeout(function() {
                        $('#mailGonderModal').modal('hide');
                        location.reload(); 
                    }, 2000);
                } else {
                    alertDiv.removeClass('alert-success').addClass('alert-danger').text(response.message).show();
                    submitButton.prop('disabled', false).html(originalButtonText);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX Hatası: ", jqXHR.responseText);
                alertDiv.removeClass('alert-success').addClass('alert-danger').text('Sunucuya bağlanırken bir hata oluştu. Detaylar için konsolu kontrol edin.').show();
                submitButton.prop('disabled', false).html(originalButtonText);
            }
        });
		        // === YENİ EKLENECEK KOD: ŞABLON YÜKLEME ===
        $('#template_id').select2({
            placeholder: "Bir şablon seçin...",
            allowClear: true
        });

        $('#template_id').on('change', function() {
            var templateId = $(this).val();
            if (!templateId) {
                $('#teklifKalemleri tbody').empty(); // Şablon seçimi kaldırılırsa listeyi temizle
                addProductRow(); // Bir tane boş satır ekle
                return;
            }

            $.ajax({
                url: 'api_get_template_details.php?id=' + templateId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.items) {
                        $('#teklifKalemleri tbody').empty(); // Önce mevcut satırları temizle
                        response.items.forEach(function(item) {
                            addProductRow(item); // Her ürün için yeni bir satır ekle
                        });
                        // Tüm satırlar eklendikten sonra toplamları güncelle
                        setTimeout(function() { updateAllCalculations(); }, 100);
                    }
                },
                error: function() {
                    alert('Şablon yüklenirken bir hata oluştu.');
                }
            });
        });
        // === YENİ KOD SONU ===
    });
});
</script>
<?php 
endif; 
?>
<?php
// === 2. EKLENECEK KOD BLOĞU: JAVASCRIPT ===
// Mevcut sayfanın adını alıyoruz
if(isset($_SERVER['PHP_SELF'])){
    $currentPage = basename($_SERVER['PHP_SELF']);

    // Bu kodun sadece teklif_view.php sayfasında çalışmasını sağlıyoruz
    if ($currentPage == 'teklif_view.php'):
?>
<script>
$(document).ready(function() {
    // "Mail Gönder" butonuna tıklandığında modal içindeki formu doldur
    $('#mailGonderModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var proposalId = button.data('proposal-id');
        var proposalNo = button.data('proposal-no');
        var customerEmail = button.data('customer-email');
        var customerName = button.data('customer-name');

        var modal = $(this);
        modal.find('#mail_proposal_id').val(proposalId);
        modal.find('#to_email').val(customerEmail);
        modal.find('#subject').val(proposalNo + ' Numaralı Fiyat Teklifiniz');
        modal.find('#mail_body').val('Sayın ' + customerName + ',\n\nİlginize teşekkür eder, teklifimizi ekte bilgilerinize sunarız.\n\nİyi çalışmalar dileriz.');
    });

    // Modal'daki "Gönder" butonuna basıldığında AJAX ile maili gönder
    $('#sendMailForm').on('submit', function(e) {
        e.preventDefault(); 

        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        var originalButtonText = submitButton.html();
        var alertDiv = $('#mail-response-alert');

        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Gönderiliyor...');
        alertDiv.hide();

        $.ajax({
            type: 'POST',
            url: 'send_mail.php', 
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alertDiv.removeClass('alert-danger').addClass('alert-success').text(response.message).show();
                    setTimeout(function() {
                        $('#mailGonderModal').modal('hide');
                        location.reload(); 
                    }, 2000);
                } else {
                    alertDiv.removeClass('alert-success').addClass('alert-danger').text(response.message).show();
                    submitButton.prop('disabled', false).html(originalButtonText);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX Hatası: ", jqXHR.responseText);
                alertDiv.removeClass('alert-success').addClass('alert-danger').text('Sunucuya bağlanırken bir hata oluştu. Detaylar için konsolu kontrol edin.').show();
                submitButton.prop('disabled', false).html(originalButtonText);
            }
        });
    });
});
</script>
<?php 
    endif; 
}
?>
<!-- === YENİ EKLENECEK KOD: MOBİL MENÜ İŞLEVSELLİĞİ === -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gerekli HTML elemanlarını seç
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainWrapper = document.querySelector('.main-wrapper');

    // Eğer menü butonu ve sidebar varsa, tıklama olayını ekle
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            mainWrapper.classList.toggle('sidebar-open');
        });
    }
    
    // Menü dışına (karartılmış alana) tıklandığında menüyü kapat
    if (mainWrapper) {
        mainWrapper.addEventListener('click', function(e) {
            // Sadece .main-wrapper'ın kendisine tıklandıysa ve menü açıksa
            if(e.target === mainWrapper && sidebar.classList.contains('open')) {
                 sidebar.classList.remove('open');
                 mainWrapper.classList.remove('sidebar-open');
            }
        });
    }
});

<!-- === MOBİL MENÜ KAPATMA İŞLEVSELLİĞİ === -->
<script>
$(document).ready(function() {
    // Bu kodun sadece sidebarToggleBtn ID'li bir buton varsa çalışmasını sağlıyoruz
    var sidebarToggleButton = $('#sidebarToggleBtn');
    
    if (sidebarToggleButton.length > 0) {
        
        // Hamburger butonuna tıklandığında menüyü aç/kapat
        sidebarToggleButton.on('click', function(e) {
            e.stopPropagation(); // Tıklamanın diğer elementlere yayılmasını engelle
            $('.main-wrapper').toggleClass('sidebar-toggled');
        });

        // Ana içerik alanına tıklandığında menüyü kapat
        $('.main-content').on('click', function() {
            // Eğer menü zaten açıksa
            if ($('.main-wrapper').hasClass('sidebar-toggled')) {
                // menüyü kapat
                $('.main-wrapper').removeClass('sidebar-toggled');
            }
        });
    }
});
<!-- === MOBİL MENÜ KAPATMA İŞLEVSELLİĞİ === -->
<script>
$(document).ready(function() {
    // Bu kodun sadece sidebarToggleBtn ID'li bir buton varsa çalışmasını sağlıyoruz
    var sidebarToggleButton = $('#sidebarToggleBtn');
    
    if (sidebarToggleButton.length > 0) {
        
        // Hamburger butonuna tıklandığında menüyü aç/kapat
        sidebarToggleButton.on('click', function(e) {
            e.stopPropagation(); // Tıklamanın diğer elementlere yayılmasını engelle
            $('.main-wrapper').toggleClass('sidebar-toggled');
        });

        // Ana içerik alanına tıklandığında menüyü kapat
        $('.main-content').on('click', function() {
            // Eğer menü zaten açıksa
            if ($('.main-wrapper').hasClass('sidebar-toggled')) {
                // menüyü kapat
                $('.main-wrapper').removeClass('sidebar-toggled');
            }
        });
    }
});
</script>
<!-- === YENİ VE DÜZELTİLMİŞ MOBİL MENÜ SCRİPTİ === -->
    <script>
    $(document).ready(function() {
        // Gerekli elemanları seç
        var toggleBtn = $('.mobile-menu-toggle');
        var sidebar = $('.sidebar');
        var mainWrapper = $('.main-wrapper');

        // Hamburger butonuna tıklandığında menüyü aç/kapat
        toggleBtn.on('click', function(e) {
            e.stopPropagation(); // Tıklamanın diğer elementlere yayılmasını engelle
            sidebar.toggleClass('open');
            mainWrapper.toggleClass('sidebar-open');
        });

        // Ana içerik alanına (menü dışındaki boşluğa) tıklandığında menüyü kapat
        $('.main-content').on('click', function() {
            // Eğer menü açıksa
            if (sidebar.hasClass('open')) {
                sidebar.removeClass('open');
                mainWrapper.removeClass('sidebar-open');
            }
        });
    });
    </script>

</body>
</html>