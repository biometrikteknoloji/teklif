    <!--
    SIRALAMA ÇOK ÖNEMLİDİR!
    1. jQuery
    2. Bootstrap JS (içinde Popper.js de var)
    3. Diğer Eklentiler (Select2 vb.)
    4. Bizim özel scriptlerimiz
    -->

    <!-- jQuery (Tüm interaktif özellikler için GEREKLİ) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <!-- Bootstrap JS (Tooltip gibi özellikler için) -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <!-- Select2 JS (Gelişmiş Seçim Kutuları için) -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- =================================================================
         SAYFAYA ÖZEL JAVASCRIPT KODLARI
    ================================================================== -->
    
    <?php 
    // Mevcut sayfanın adını alıyoruz
    $currentPage = basename($_SERVER['PHP_SELF']);
    
    // --- TEKLİF LİSTESİ SAYFASINA ÖZEL SCRIPT ---
    if ($currentPage == 'teklif_listesi.php'): 
    ?>
    <script>
    $(document).ready(function(){
        $("#proposalSearchInput").on("keyup", function() {
            var value = $(this).val().toLocaleLowerCase('tr-TR');
            $("#proposalsTable tbody tr").filter(function() {
                if ($(this).find('td[colspan="7"]').length > 0) { return false; }
                var rowText = $(this).text().toLocaleLowerCase('tr-TR');
                $(this).toggle(rowText.indexOf(value) > -1);
            });
        });
    });
    </script>
    <?php 
    // --- TEKLİF OLUŞTURMA/DÜZENLEME SAYFALARINA ÖZEL SCRIPT (BİRLEŞTİRİLMİŞ) ---
    elseif ($currentPage == 'teklif_olustur.php' || $currentPage == 'teklif_revize_et.php'): 
    ?>
    <script>
    $(document).ready(function() {
        // Müşteri verilerini PHP'den alıyoruz (teklif_olustur.php'de tanımlanmıştı)
        var customersData = <?php echo json_encode($customers ?? []); ?>;

        // Müşteri seçildiğinde "Kime" alanını doldur
        $('#customer_id').select2({
            placeholder: "Müşteri arayın...",
            data: customersData
        }).on('select2:select', function (e) {
            var data = e.params.data;
            // PHP'den gelen tüm müşteri verileri içinde seçileni bul
            var selectedCustomer = customersData.find(c => c.id == data.id);
            if (selectedCustomer && selectedCustomer.yetkili_ismi) {
                $('#contact_person').val(selectedCustomer.yetkili_ismi);
            } else {
                $('#contact_person').val(''); // Eğer yetkili ismi yoksa alanı boşalt
            }
        });

        // --- SENİN ÇALIŞAN KODUNUN TAMAMI BURADA ---
        function addProductRow() {
            var rowCount = $('#teklifKalemleri tbody tr').length;
            var newRow = `
                <tr>
                    <td><img src="assets/images/placeholder.png" class="product-image" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;"></td>
                    <td><select class="form-control product-select" name="products[${rowCount}][id]"></select></td>
                    <td><input type="number" class="form-control quantity" name="products[${rowCount}][quantity]" value="1" min="1"></td>
                    <td><input type="number" class="form-control unit-price" name="products[${rowCount}][unit_price]" step="0.01"></td>
                    <td>
                        <div class="input-group">
                            <input type="number" class="form-control discount" name="products[${rowCount}][discount]" value="0" min="0" max="100" step="1">
                            <span class="input-group-text">%</span>
                        </div>
                    </td>
                    <td><input type="text" class="form-control text-end fw-bold total-price" name="products[${rowCount}][total]" readonly></td>
                    <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
                </tr>
            `;
            $('#teklifKalemleri tbody').append(newRow);
            
            var newSelect = $('#teklifKalemleri tbody tr:last-child .product-select');
            newSelect.select2({
                placeholder: 'Ürün arayın...',
                ajax: { url: 'api_get_products.php', dataType: 'json', delay: 250, processResults: data => ({ results: data.results }), cache: true }
            });
        }

        if ($('#teklifKalemleri tbody tr').length === 0) {
            addProductRow();
        }
        
        $('#urunEkleBtn').on('click', addProductRow);
        
        $('#teklifKalemleri').on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            updateAllCalculations();
        });

        $(document).on('change', '#currency, #kdvOrani', updateAllCalculations);
        $('#teklifKalemleri').on('change', '.product-select, .quantity, .unit-price, .discount', function() {
            var row = $(this).closest('tr');
            var selectedProduct = row.find('.product-select').select2('data')[0];
            var currency = $('#currency').val();

            if (selectedProduct && $(this).hasClass('product-select')) {
                var price = selectedProduct.prices[currency] || 0;
                row.find('.unit-price').val(parseFloat(price).toFixed(2));
                var imagePath = selectedProduct.fotograf_yolu && selectedProduct.fotograf_yolu !== '' ? selectedProduct.fotograf_yolu : 'assets/images/placeholder.png';
                row.find('.product-image').attr('src', imagePath);
            }
            updateAllCalculations();
        });

        function updateAllCalculations() {
            var currency = $('#currency').val();
            var currencySymbol = ' ₺';
            if (currency === 'USD') {
                currencySymbol = ' $';
            } else if (currency === 'EUR') {
                currencySymbol = ' €';
            }

            var subTotal = 0;
            var totalDiscount = 0;

            $('#teklifKalemleri tbody tr').each(function() {
                var row = $(this);
                var quantity = parseFloat(row.find('.quantity').val()) || 0;
                var unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
                var discountPercent = parseFloat(row.find('.discount').val()) || 0;
                
                var lineTotal = quantity * unitPrice;
                var lineDiscountAmount = lineTotal * (discountPercent / 100);
                var lineFinalTotal = lineTotal - lineDiscountAmount;

                row.find('.total-price').val(lineFinalTotal.toFixed(2));
                
                subTotal += lineTotal;
                totalDiscount += lineDiscountAmount;
            });

            var kdvPercent = parseFloat($('#kdvOrani').val()) || 0;
            var netTotal = subTotal - totalDiscount;
            var kdvAmount = netTotal * (kdvPercent / 100);
            var grandTotal = netTotal + kdvAmount;

            var formatter = new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $('#araToplam').text(formatter.format(subTotal) + currencySymbol);
            $('#iskontoToplami').text(`(${formatter.format(totalDiscount)})${currencySymbol}`);
            $('#kdvTutari').text(formatter.format(kdvAmount) + currencySymbol);
            $('#genelToplam').text(formatter.format(grandTotal) + currencySymbol);
            
            $('#kdv_rate_hidden').val(kdvPercent);
        }
    });
    </script>
    <?php endif; ?>
    
    
    <!-- Bootstrap İpucu Balonlarını (Tooltips) Aktif Etme Scripti (Tüm sayfalarda çalışır) -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
              return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        });
    </script>
    
</body>
</html>