@extends('layouts.app-layout')

@section('title', 'إضافة مبيعة')
@section('header', 'إضافة مبيعة')
@section('add-route', route('sales.create'))

@section('content')
    <form action="{{ route('sales.store') }}" method="POST" class="bg-white p-6 rounded shadow-md">
        @csrf
        <div class="mb-4">
            <label for="date_time" class="block text-sm font-medium text-gray-700">تاريخ ووقت المبيعة</label>
            <input type="datetime-local" name="date_time" id="date_time" value="{{ old('date_time', now()->timezone('Asia/Damascus')->format('Y-m-d\\TH:i')) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
        </div>

        <div id="products-container">
            <div class="product-item mb-4 p-4 border rounded">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label for="barcode_0" class="block text-sm font-medium text-gray-700">الباركود</label>
                        <div class="flex space-x-2">
                            <input type="text" id="barcode_0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm barcode-input"
                                   placeholder="امسح الباركود أو أدخله يدويًا">
                            <button type="button" class="scan-barcode bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                                    data-index="0">مسح</button>
                            <button type="button" class="capture-barcode bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                                    data-index="0">📷</button>
                        </div>
                    </div>
                    <div>
                        <label for="name_0" class="block text-sm font-medium text-gray-700">البحث عن المنتج</label>
                        <div class="relative">
                            <input type="text" id="name_0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm product-search"
                                   placeholder="ابحث عن المنتج بالاسم" data-index="0">
                            <div id="search-results_0" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        </div>
                        <label for="product_id_0" class="block text-sm font-medium text-gray-700 mt-2">اختر المنتج</label>
                        <select name="products[0][product_id]" id="product_id_0"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm product-select" required>
                            <option value="">اختر منتج</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}"
                                        data-selling-price="{{ $product->latestPurchase->selling_price ?? 0 }}"
                                        data-stock-quantity="{{ $product->stock_quantity }}"
                                        data-barcode="{{ $product->barcode }}"
                                        data-name="{{ $product->name }}">
                                    {{ $product->name }} (المخزن: {{ $product->stock_quantity }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="quantity_0" class="block text-sm font-medium text-gray-700">الكمية</label>
                        <input type="number" name="products[0][quantity]" id="quantity_0" min="1" value="1"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm quantity-input" required>
                    </div>
                    <div>
                        <label for="selling_price_0" class="block text-sm font-medium text-gray-700">سعر البيع</label>
                        <input type="number" name="products[0][selling_price]" id="selling_price_0" step="0.01" min="0"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm selling-price-input" required>
                    </div>
                    <div>
                        <label for="total_price_0" class="block text-sm font-medium text-gray-700">السعر الإجمالي</label>
                        <input type="number" name="products[0][total_price]" id="total_price_0" step="0.01" min="0"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-100" readonly>
                    </div>
                </div>
                <button type="button" class="remove-product mt-2 text-red-600 hover:text-red-800 hidden">إزالة</button>
            </div>
        </div>

        <div id="scanner-container" class="hidden mb-4">
            <video id="scan-video" class="w-full max-w-md mx-auto rounded" muted playsinline></video>
            <div id="reader" class="w-full max-w-md mx-auto"></div>
            <div class="flex justify-center mt-2 gap-2">
                <button type="button" id="torch-btn" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 hidden">💡 الفلاش</button>
                <button type="button" id="scan-cancel" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">إلغاء</button>
            </div>
        </div>

        {{-- Shared photo-capture input + hidden container used to decode a barcode from a photo --}}
        <input type="file" accept="image/*" capture="environment" id="barcode-photo" class="hidden">
        <div id="barcode-file-reader" class="hidden"></div>

        <button type="button" id="add-product"
                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mb-4">
            إضافة منتج آخر
        </button>

        <div class="mb-4">
            <label for="grand_total" class="block text-sm font-medium text-gray-700">الإجمالي الكلي</label>
            <input type="number" id="grand_total" step="0.01" min="0"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-100" readonly>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                تسجيل المبيعة
                </button>
        </div>
    </form>
@endsection

@push('scripts')
    <!-- html5-qrcode (fallback engine) + ZXing (still-photo decoder), served locally -->
    <script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
    <script src="{{ asset('js/zxing.min.js') }}"></script>
    <!-- Native BarcodeDetector live scanner with html5-qrcode fallback -->
    <script src="{{ asset('js/barcode-scanner.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const productsContainer = document.getElementById('products-container');
            const addProductButton = document.getElementById('add-product');
            const grandTotalInput = document.getElementById('grand_total');
            let productIndex = 0;
            let html5QrcodeScanners = [];
            const torchBtn = document.getElementById('torch-btn');
            let torchOn = false;

            // Shared: stop the live scanner and hide its UI
            function stopSalesScanner() {
                if (window.__scanHandle) { window.__scanHandle.stop(); window.__scanHandle = null; }
                torchBtn.classList.add('hidden');
                document.getElementById('scanner-container').classList.add('hidden');
            }
            document.getElementById('scan-cancel')?.addEventListener('click', stopSalesScanner);

            // Toggle the phone's flashlight (torch) on the running camera track
            function setupTorch() {
                const video = document.querySelector('#reader video');
                if (!video || !video.srcObject) { torchBtn.classList.add('hidden'); return; }
                const track = video.srcObject.getVideoTracks()[0];
                const caps = track && track.getCapabilities ? track.getCapabilities() : {};
                if (!caps.torch) { torchBtn.classList.add('hidden'); return; }
                torchBtn.classList.remove('hidden');
                torchOn = false;
                torchBtn.onclick = () => {
                    torchOn = !torchOn;
                    track.applyConstraints({ advanced: [{ torch: torchOn }] })
                        .catch(err => console.warn('Torch error:', err));
                };
            }

            // ---- Decode a barcode from a captured photo (high-res still image) ----
            const photoInput = document.getElementById('barcode-photo');
            let captureIndex = null; // which product row requested the photo

            // Decode order: native BarcodeDetector -> ZXing (TRY_HARDER) -> html5-qrcode scanFile
            async function decodeBarcodeFromImage(file) {
                try {
                    if ('BarcodeDetector' in window) {
                        const formats = await BarcodeDetector.getSupportedFormats();
                        const detector = new BarcodeDetector({ formats });
                        const bitmap = await createImageBitmap(file);
                        const codes = await detector.detect(bitmap);
                        if (codes && codes.length) return codes[0].rawValue;
                    }
                } catch (err) {
                    console.warn('BarcodeDetector failed:', err);
                }
                // ZXing with TRY_HARDER — best for imperfect/blurry 1D barcodes
                try {
                    if (window.ZXing) {
                        const hints = new Map();
                        hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
                        const reader = new ZXing.BrowserMultiFormatReader(hints);
                        const url = URL.createObjectURL(file);
                        try {
                            const result = await reader.decodeFromImageUrl(url);
                            if (result) return result.getText();
                        } finally {
                            URL.revokeObjectURL(url);
                            if (reader.reset) reader.reset();
                        }
                    }
                } catch (err) {
                    console.warn('ZXing failed:', err);
                }
                try {
                    const fileScanner = new Html5Qrcode("barcode-file-reader");
                    return await fileScanner.scanFile(file, false);
                } catch (err) {
                    console.warn('scanFile failed:', err);
                    return null;
                }
            }

            // Any "📷" button opens the camera for its row (event delegation)
            productsContainer.addEventListener('click', (e) => {
                const btn = e.target.closest('.capture-barcode');
                if (!btn) return;
                captureIndex = btn.dataset.index;
                photoInput.click();
            });

            photoInput.addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file || captureIndex === null) return;
                const barcodeInput = document.getElementById(`barcode_${captureIndex}`);
                const code = await decodeBarcodeFromImage(file);
                photoInput.value = ''; // allow re-taking the same item
                if (code && barcodeInput) {
                    if (navigator.vibrate) navigator.vibrate(100);
                    barcodeInput.value = code;
                    barcodeInput.dispatchEvent(new Event('input', { bubbles: true }));
                } else {
                    alert('لم يتم العثور على باركود في الصورة. حاول التقاط صورة أوضح وأقرب للباركود.');
                }
                captureIndex = null;
            });

            // Initialize the first product row
            initProductRow(0);
            updateGrandTotal();

            // Add product button click handler
            if (addProductButton) {
                addProductButton.addEventListener('click', () => {
                    productIndex++;
                    const newItem = document.createElement('div');
                    newItem.classList.add('product-item', 'mb-4', 'p-4', 'border', 'rounded');
                    newItem.innerHTML = `
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div>
                                <label for="barcode_${productIndex}" class="block text-sm font-medium text-gray-700">الباركود</label>
                                <div class="flex space-x-2">
                                    <input type="text" id="barcode_${productIndex}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm barcode-input"
                                           placeholder="امسح الباركود أو أدخله يدويًا">
                                    <button type="button" class="scan-barcode bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                                            data-index="${productIndex}">مسح</button>
                                    <button type="button" class="capture-barcode bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                                            data-index="${productIndex}">📷</button>
                                </div>
                            </div>
                            <div>
                                <label for="name_${productIndex}" class="block text-sm font-medium text-gray-700">البحث عن المنتج</label>
                                <div class="relative">
                                    <input type="text" id="name_${productIndex}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm product-search"
                                           placeholder="ابحث عن المنتج بالاسم أو الباركود" data-index="${productIndex}">
                                    <div id="search-results_${productIndex}" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                                </div>
                                <label for="product_id_${productIndex}" class="block text-sm font-medium text-gray-700 mt-2">اختر المنتج</label>
                                <select name="products[${productIndex}][product_id]" id="product_id_${productIndex}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm product-select" required>
                                    <option value="">اختر منتج</option>
                                    @foreach ($products as $product)
                    <option value="{{ $product->id }}"
                                                data-selling-price="{{ $product->latestPurchase->selling_price ?? 0 }}"
                                                data-stock-quantity="{{ $product->stock_quantity }}"
                                                data-barcode="{{ $product->barcode }}"
                                                data-name="{{ $product->name }}">
                                            {{ $product->name }} (المخزن: {{ $product->stock_quantity }})
                                        </option>
                                    @endforeach
                    </select>
                </div>
                <div>
                    <label for="quantity_${productIndex}" class="block text-sm font-medium text-gray-700">الكمية</label>
                                <input type="number" name="products[${productIndex}][quantity]" id="quantity_${productIndex}" min="1" value="1"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm quantity-input" required>
                            </div>
                            <div>
                                <label for="selling_price_${productIndex}" class="block text-sm font-medium text-gray-700">سعر البيع</label>
                                <input type="number" name="products[${productIndex}][selling_price]" id="selling_price_${productIndex}" step="0.01" min="0"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm selling-price-input" required>
                            </div>
                            <div>
                                <label for="total_price_${productIndex}" class="block text-sm font-medium text-gray-700">السعر الإجمالي</label>
                                <input type="number" name="products[${productIndex}][total_price]" id="total_price_${productIndex}" step="0.01" min="0"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-100" readonly>
                            </div>
                        </div>
                        <button type="button" class="remove-product mt-2 text-red-600 hover:text-red-800">إزالة</button>
                    `;
                    productsContainer.appendChild(newItem);

                    // Initialize the new product row
                    initProductRow(productIndex);

                    // Show remove button for the first product if more than one product exists
                    const firstRemoveButton = productsContainer.querySelector('.product-item:first-child .remove-product');
                    if (firstRemoveButton) {
                        firstRemoveButton.classList.remove('hidden');
                    }
                });
            }

            // Event delegation for remove product buttons
            productsContainer.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-product')) {
                    const productItem = e.target.closest('.product-item');
                    productItem.remove();
                    updateGrandTotal();

                    // Hide remove button for the first product if only one product exists
                    const productItems = productsContainer.querySelectorAll('.product-item');
                    if (productItems.length === 1) {
                        const firstRemoveButton = productItems[0].querySelector('.remove-product');
                        if (firstRemoveButton) {
                            firstRemoveButton.classList.add('hidden');
                        }
                    }
                }
            });

            // Initialize product row functionality
            function initProductRow(index) {
                const productSelect = document.getElementById(`product_id_${index}`);
                const quantityInput = document.getElementById(`quantity_${index}`);
                const sellingPriceInput = document.getElementById(`selling_price_${index}`);
                const totalPriceInput = document.getElementById(`total_price_${index}`);
                const barcodeInput = document.getElementById(`barcode_${index}`);
                const searchInput = document.getElementById(`name_${index}`);
                const searchResults = document.getElementById(`search-results_${index}`);
                const scanButton = document.querySelector(`.scan-barcode[data-index="${index}"]`);

                // Function to update total price
                function updateTotalPrice() {
                    const sellingPrice = parseFloat(sellingPriceInput.value || 0);
                    const quantity = parseInt(quantityInput.value || 1);
                    totalPriceInput.value = (sellingPrice * quantity).toFixed(2);
                    updateGrandTotal();
                }

                // Product search functionality
                if (searchInput) {
                    searchInput.addEventListener('input', (e) => {
                        const query = e.target.value.trim();

                        if (query.length >= 2) {
                            searchResults.innerHTML = '<div class="p-2 text-gray-500">جارٍ البحث...</div>';
                            searchResults.classList.remove('hidden');
                            searchProducts(query, index); // Immediate search
                        } else {
                            searchResults.classList.add('hidden');
                        }
                    });

                    // Hide search results when clicking outside
                    document.addEventListener('click', (e) => {
                        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                            searchResults.classList.add('hidden');
                        }
                    });
                }

                // Barcode input handler
                if (barcodeInput) {
                    barcodeInput.addEventListener('input', (e) => {
                        const barcode = e.target.value.trim();
                        if (barcode) {
                            // Try to match barcode with dropdown options first
                            const productOption = Array.from(productSelect.options).find(
                                option => option.dataset.barcode === barcode
                            );

                            if (productOption) {
                                productSelect.value = productOption.value;
                                searchInput.value = productOption.dataset.name;
                                sellingPriceInput.value = parseFloat(productOption.dataset.sellingPrice || 0).toFixed(2);
                                const changeEvent = new Event('change');
                                productSelect.dispatchEvent(changeEvent);
                            } else {
                                // Fallback to server-side search
                                searchProducts(barcode, index);
                            }
                        }
                    });
                }

                // Barcode scanner button handler — native BarcodeDetector with html5-qrcode fallback
                if (scanButton) {
                    scanButton.addEventListener('click', async () => {
                        const scannerContainer = document.getElementById('scanner-container');
                        const scanVideo = document.getElementById('scan-video');

                        // Toggle off if this scanner is already running
                        if (window.__scanHandle) { stopSalesScanner(); return; }

                        scannerContainer.classList.remove('hidden');
                        try {
                            window.__scanHandle = await startBarcodeScan({
                                videoEl: scanVideo,
                                readerElId: 'reader',
                                onDetect: (decodedText) => {
                                    barcodeInput.value = decodedText;
                                    barcodeInput.dispatchEvent(new Event('input', { bubbles: true }));
                                    stopSalesScanner();
                                }
                            });

                            // Flashlight button, if the device supports it
                            if (window.__scanHandle.torchAvailable && window.__scanHandle.torchAvailable()) {
                                torchBtn.classList.remove('hidden');
                                let torchState = false;
                                torchBtn.onclick = () => { torchState = !torchState; window.__scanHandle.setTorch(torchState); };
                            }
                        } catch (err) {
                            console.error("Scanner start error:", err);
                            scannerContainer.classList.add('hidden');
                            alert("فشل بدء الماسح الضوئي. تأكد من السماح باستخدام الكاميرا وأن الموقع يعمل عبر HTTPS.");
                        }
                    });
                }

                // Product select change handler
                if (productSelect) {
                    productSelect.addEventListener('change', () => {
                        const selectedOption = productSelect.options[productSelect.selectedIndex];
                        const sellingPrice = parseFloat(selectedOption.dataset.sellingPrice || 0);
                        const quantity = parseInt(quantityInput.value || 1);
                        const stockQuantity = parseInt(selectedOption.dataset.stockQuantity || 0);

                        // Set selling price input
                        sellingPriceInput.value = sellingPrice.toFixed(2);

                        // Validate quantity against stock
                        if (quantity > stockQuantity) {
                            alert(`الكمية المطلوبة (${quantity}) تتجاوز المخزون المتاح (${stockQuantity})`);
                            quantityInput.value = stockQuantity || 1;
                        }

                        // Update total price
                        updateTotalPrice();

                        // Update barcode and search input fields
                        if (barcodeInput && selectedOption.dataset.barcode) {
                            barcodeInput.value = selectedOption.dataset.barcode;
                        }
                        if (searchInput && selectedOption.dataset.name) {
                            searchInput.value = selectedOption.dataset.name;
                        }
                    });
                }

                // Quantity input change handler
                if (quantityInput) {
                    quantityInput.addEventListener('input', () => {
                        if (productSelect.value) {
                            const selectedOption = productSelect.options[productSelect.selectedIndex];
                            const stockQuantity = parseInt(selectedOption.dataset.stockQuantity || 0);
                            const quantity = parseInt(quantityInput.value || 1);

                            // Validate quantity against stock
                            if (quantity > stockQuantity) {
                                alert(`الكمية المطلوبة (${quantity}) تتجاوز المخزون المتاح (${stockQuantity})`);
                                quantityInput.value = stockQuantity || 1;
                            }

                            // Update total price
                            updateTotalPrice();
                        }
                    });
                }

                // Selling price input change handler
                if (sellingPriceInput) {
                    sellingPriceInput.addEventListener('input', () => {
                        updateTotalPrice();
                    });
                }
            }

            // Search products function
            function searchProducts(query, index) {
                const searchResults = document.getElementById(`search-results_${index}`);

                fetch(`{{ route('sales.search-products') }}?query=${encodeURIComponent(query)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(products => {
                        searchResults.innerHTML = '';

                        if (products.length > 0) {
                            products.forEach(product => {
                                const resultItem = document.createElement('div');
                                resultItem.className = 'p-2 hover:bg-gray-100 cursor-pointer border-b';
                                resultItem.innerHTML = `
                                    <div class="font-medium">${product.name}</div>
                                    <div class="text-sm text-gray-600">المخزن: ${product.stock_quantity} | السعر: ${product.selling_price}</div>
                                    <div class="text-xs text-gray-500">الباركود: ${product.barcode || 'غير متوفر'}</div>
                                `;
                                resultItem.addEventListener('click', () => {
                                    selectProduct(product, index);
                                    searchResults.classList.add('hidden');
                                });
                                searchResults.appendChild(resultItem);
                            });
                            searchResults.classList.remove('hidden');
                        } else {
                            searchResults.innerHTML = '<div class="p-2 text-gray-500">لا توجد منتجات مطابقة</div>';
                            searchResults.classList.remove('hidden');
                        }
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        searchResults.innerHTML = '<div class="p-2 text-red-500">خطأ في البحث عن المنتجات</div>';
                        searchResults.classList.remove('hidden');
                    });
            }

            // Select product function
            function selectProduct(product, index) {
                const searchInput = document.getElementById(`name_${index}`);
                const productSelect = document.getElementById(`product_id_${index}`);
                const barcodeInput = document.getElementById(`barcode_${index}`);
                const quantityInput = document.getElementById(`quantity_${index}`);
                const sellingPriceInput = document.getElementById(`selling_price_${index}`);
                const totalPriceInput = document.getElementById(`total_price_${index}`);

                // Update search input
                searchInput.value = product.name;

                // Check if product exists in dropdown; if not, add it
                let productOption = Array.from(productSelect.options).find(
                    option => option.value == product.id
                );
                if (!productOption) {
                    productOption = new Option(
                        `${product.name} (المخزن: ${product.stock_quantity})`,
                        product.id,
                        false,
                        true
                    );
                    productOption.dataset.sellingPrice = product.selling_price;
                    productOption.dataset.stockQuantity = product.stock_quantity;
                    productOption.dataset.barcode = product.barcode || '';
                    productOption.dataset.name = product.name;
                    productSelect.add(productOption);
                } else {
                    productSelect.value = product.id;
                }

                // Update barcode
                if (barcodeInput) {
                    barcodeInput.value = product.barcode || '';
                }

                // Update selling price
                sellingPriceInput.value = parseFloat(product.selling_price || 0).toFixed(2);

                // Validate quantity against stock
                const quantity = parseInt(quantityInput.value || 1);
                if (quantity > product.stock_quantity) {
                    alert(`الكمية المطلوبة (${quantity}) تتجاوز المخزون المتاح (${product.stock_quantity})`);
                    quantityInput.value = product.stock_quantity || 1;
                }

                // Update total price
                totalPriceInput.value = (parseFloat(sellingPriceInput.value) * parseInt(quantityInput.value)).toFixed(2);
                updateGrandTotal();
            }

            // Update grand total
            function updateGrandTotal() {
                const totalPriceInputs = document.querySelectorAll('[id^="total_price_"]');
                let grandTotal = 0;

                totalPriceInputs.forEach(input => {
                    grandTotal += parseFloat(input.value || 0);
                });

                if (grandTotalInput) {
                    grandTotalInput.value = grandTotal.toFixed(2);
                }
            }
        });
    </script>
@endpush
