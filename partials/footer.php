    <!-- ... (jQuery, Bootstrap, Select2) ... -->
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
        // ... (teklif listesi arama scripti) ...
    });
    </script>
    <?php 
    elseif ($currentPage == 'teklif_olustur.php' || $currentPage == 'teklif_revize_et.php'): 
    ?>
    <script>
    $(document).ready(function() {
        var customersData = <?php echo json_encode($customers ?? []); ?>;
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

        // SADE İSKONTO MANTIĞI
        $('#toggleDiscountBtn').on('click', function() {
            $('.discount-row').toggleClass('d-none');
            if ($('.discount-row').hasClass('d-none')) {
                $('#genelIskontoYuzde').val('');
                $('#genelIskontoTutar').val('');
                updateAllCalculations();
            }
        });

        // SADE ÜRÜN SATIRI EKLEME
        function addProductRow() {
            var rowCount = $('#teklifKalemleri tbody tr').length;
            var newRow = `
                <tr>
                    <td><img src="assets/images/placeholder.png" class="product-image" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;"></td>
                    <td><select class="form-control product-select" name="products[${rowCount}][id]"></select></td>
                    <td><input type="number" class="form-control quantity" name="products[${rowCount}][quantity]" value="1" min="1"></td>
                    <td><input type="number" class="form-control unit-price" name="products[${rowCount}][unit_price]" step="0.01"></td>
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
        if ($('#teklifKalemleri tbody tr').length === 0) { addProductRow(); }
        $('#urunEkleBtn').on('click', addProductRow);
        $('#teklifKalemleri').on('click', '.remove-row', function() { $(this).closest('tr').remove(); updateAllCalculations(); });

        // HESAPLAMAYI TETİKLEYEN OLAYLAR
        $(document).on('change, keyup', '#currency, #kdvOrani, #genelIskontoYuzde, #genelIskontoTutar', updateAllCalculations);
        $('#teklifKalemleri').on('change, keyup', '.quantity, .unit-price', updateAllCalculations);
        $('#teklifKalemleri').on('select2:select', '.product-select', function(e) {
            var row = $(this).closest('tr');
            var selectedProduct = e.params.data;
            var currency = $('#currency').val();
            if (selectedProduct) {
                var price = selectedProduct.prices[currency] || 0;
                row.find('.unit-price').val(parseFloat(price).toFixed(2));
                var imagePath = selectedProduct.fotograf_yolu && selectedProduct.fotograf_yolu !== '' ? selectedProduct.fotograf_yolu : 'assets/images/placeholder.png';
                row.find('.product-image').attr('src', imagePath);
                updateAllCalculations();
            }
        });

        // GENEL İSKONTO ALANLARI
        $('#genelIskontoYuzde').on('input', function() {
            var yuzde = parseFloat($(this).val()) || 0;
            var araToplam = parseFloat($('#araToplam').text().replace(/\./g, '').replace(',', '.')) || 0;
            var tutar = (araToplam * yuzde) / 100;
            $('#genelIskontoTutar').val(tutar.toFixed(2));
            updateAllCalculations();
        });
        $('#genelIskontoTutar').on('input', function() {
            var tutar = parseFloat($(this).val()) || 0;
            var araToplam = parseFloat($('#araToplam').text().replace(/\./g, '').replace(',', '.')) || 0;
            if (araToplam > 0) {
                var yuzde = (tutar / araToplam) * 100;
                $('#genelIskontoYuzde').val(yuzde.toFixed(2));
            } else { $('#genelIskontoYuzde').val(0); }
            updateAllCalculations();
        });

        // SADELEŞTİRİLMİŞ ANA HESAPLAMA FONKSİYONU
        function updateAllCalculations() {
            var currency = $('#currency').val();
            var currencySymbol = ' ₺';
            if (currency === 'USD') currencySymbol = ' $';
            else if (currency === 'EUR') currencySymbol = ' €';

            var araToplam = 0;
            $('#teklifKalemleri tbody tr').each(function() {
                var row = $(this);
                var quantity = parseFloat(row.find('.quantity').val()) || 0;
                var unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
                var lineTotalBrut = quantity * unitPrice;
                row.find('.total-price').val(lineTotalBrut.toFixed(2));
                araToplam += lineTotalBrut;
            });

            var genelIskontoTutar = parseFloat($('#genelIskontoTutar').val()) || 0;
            var kdvMatrahi = araToplam - genelIskontoTutar;
            var kdvPercent = parseFloat($('#kdvOrani').val()) || 0;
            var kdvAmount = kdvMatrahi * (kdvPercent / 100);
            var grandTotal = kdvMatrahi + kdvAmount;

            var formatter = new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $('#araToplam').text(formatter.format(araToplam) + currencySymbol);
            $('#kdvTutari').text(formatter.format(kdvAmount) + currencySymbol);
            $('#genelToplam').text(formatter.format(grandTotal) + currencySymbol);
            $('#kdv_rate_hidden').val(kdvPercent);
        }
    });
    </script>
    <?php endif; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // ... (Tooltip script'i aynı) ...
        });
    </script>
</body>
</html>