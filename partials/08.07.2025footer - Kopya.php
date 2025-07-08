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

            var genelIskontoTutar = 0;
            if (!$('.discount-row').hasClass('d-none')) {
                genelIskontoTutar = parseFloat($('#genelIskontoTutar').val()) || 0;
            }
            
            var araToplamNet = toplamBrut - genelIskontoTutar;
            var kdvPercent = parseFloat($('#kdvOrani').val()) || 0;
            var kdvAmount = araToplamNet * (kdvPercent / 100);
            var grandTotal = araToplamNet + kdvAmount;

            var formatter = new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            
            // Yeni HTML yapısına göre ID'leri düzeltiyoruza
            $('#toplamBrut').text(formatter.format(toplamBrut) + currencySymbol);
            $('#araToplamNet').text(formatter.format(araToplamNet) + currencySymbol);
            $('#kdvTutari').text(formatter.format(kdvAmount) + currencySymbol);
            $('#genelToplam').text(formatter.format(grandTotal) + currencySymbol);

            // İskonto gösterimini de güncelleyelim
            $('#genelIskontoGosterim').text(`(${formatter.format(genelIskontoTutar)})${currencySymbol}`);

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

        $('#genelIskontoYuzde').on('input', function() {
            var yuzde = parseFloat($(this).val()) || 0;
            var brutToplam = parseFloat($('#toplamBrut').text().replace(/\./g, '').replace(',', '.')) || 0;
            var tutar = (brutToplam * yuzde) / 100;
            $('#genelIskontoTutar').val(tutar > 0 ? tutar.toFixed(2) : '');
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
</body>
</html>