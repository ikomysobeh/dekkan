@extends('layouts.app-layout')

@section('title', 'إضافة منتج')
@section('header', 'إضافة منتج')
@section('add-route', route('products.create'))
@section('search-action', '')

@section('content')
    <form action="{{ route('products.store') }}" method="POST" class="bg-white p-6 rounded shadow-md" enctype="multipart/form-data">
        @csrf

        <div class="mb-4 form-group">
            <label for="barcode" class="form-label">الباركود</label>
            <div class="flex space-x-2">
                <input type="text" name="barcode" id="barcode" value="{{ old('barcode') }}"
                       class="form-input @error('barcode') error @enderror"
                       placeholder="امسح الباركود أو أدخله يدويًا">
                <button type="button" id="scan-barcode" class="btn btn-blue">
                    مسح الباركود
                </button>
                <button type="button" id="capture-barcode" class="btn btn-blue">
                    📷 صورة الباركود
                </button>
                <input type="file" accept="image/*" capture="environment" id="barcode-photo" class="hidden">
            </div>
            @error('barcode')
            <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div id="scanner-container" class="hidden mb-4">
            <video id="scan-video" class="w-full max-w-md mx-auto rounded" muted playsinline></video>
            <div id="reader" class="w-full max-w-md mx-auto"></div>
            <div class="flex justify-center mt-2 gap-2">
                <button type="button" id="torch-btn" class="btn btn-blue hidden">💡 الفلاش</button>
                <button type="button" id="scan-cancel" class="btn btn-blue">إلغاء</button>
            </div>
        </div>

        {{-- Hidden container used to decode a barcode from a captured photo --}}
        <div id="barcode-file-reader" class="hidden"></div>

        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700">اسم المنتج</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm @error('name') border-red-500 @enderror" required>
            @error('name')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="stock_quantity" class="block text-sm font-medium text-gray-700">كمية المخزون</label>
            <input type="number" name="stock_quantity" id="stock_quantity" min="0" value="{{ old('stock_quantity', 0) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm @error('stock_quantity') border-red-500 @enderror" required>
            @error('stock_quantity')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="quantity_alert" class="block text-sm font-medium text-gray-700">تنبيه الكمية</label>
            <input type="number" name="quantity_alert" id="quantity_alert" min="0" value="{{ old('quantity_alert') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm @error('quantity_alert') border-red-500 @enderror" required>
            @error('quantity_alert')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="min_order" class="block text-sm font-medium text-gray-700">الحد الأدنى للطلب</label>
            <input type="number" name="min_order" id="min_order" min="0" value="{{ old('min_order') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm @error('min_order') border-red-500 @enderror" required>
            @error('min_order')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="image" class="block text-sm font-medium text-gray-700">صورة المنتج</label>
            <input type="file" name="image" id="image"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm @error('image') border-red-500 @enderror">
            @error('image')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                إضافة المنتج
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
            const scanButton = document.getElementById('scan-barcode');
            const scannerContainer = document.getElementById('scanner-container');
            const barcodeInput = document.getElementById('barcode');
            const torchBtn = document.getElementById('torch-btn');
            let html5QrcodeScanner;
            let torchOn = false;

            // Toggle the phone's flashlight (torch) on the running camera track
            function setupTorch() {
                const video = document.querySelector('#reader video');
                if (!video || !video.srcObject) { torchBtn.classList.add('hidden'); return; }
                const track = video.srcObject.getVideoTracks()[0];
                const caps = track && track.getCapabilities ? track.getCapabilities() : {};
                if (!caps.torch) { torchBtn.classList.add('hidden'); return; }
                torchBtn.classList.remove('hidden');
                torchBtn.onclick = () => {
                    torchOn = !torchOn;
                    track.applyConstraints({ advanced: [{ torch: torchOn }] })
                        .catch(err => console.warn('Torch error:', err));
                };
            }

            // ---- Decode a barcode from a captured photo (high-res still image) ----
            const captureBtn = document.getElementById('capture-barcode');
            const photoInput = document.getElementById('barcode-photo');

            // Decode order: native BarcodeDetector -> ZXing (TRY_HARDER) -> html5-qrcode scanFile
            async function decodeBarcodeFromImage(file) {
                // 1) Native BarcodeDetector (fast + accurate, mainly on Android Chrome)
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
                // 2) ZXing with TRY_HARDER — best for imperfect/blurry 1D barcodes
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
                // 3) Fallback: html5-qrcode decodes the image file
                try {
                    const fileScanner = new Html5Qrcode("barcode-file-reader");
                    const result = await fileScanner.scanFile(file, false);
                    return result;
                } catch (err) {
                    console.warn('scanFile failed:', err);
                    return null;
                }
            }

            if (captureBtn && photoInput) {
                captureBtn.addEventListener('click', () => photoInput.click());
                photoInput.addEventListener('change', async (e) => {
                    const file = e.target.files[0];
                    if (!file) return;
                    captureBtn.disabled = true;
                    captureBtn.textContent = '... جاري القراءة';
                    const code = await decodeBarcodeFromImage(file);
                    captureBtn.disabled = false;
                    captureBtn.textContent = '📷 صورة الباركود';
                    photoInput.value = ''; // allow re-taking the same item
                    if (code) {
                        if (navigator.vibrate) navigator.vibrate(100);
                        barcodeInput.value = code;
                        fetchProductByBarcode(code);
                    } else {
                        alert('لم يتم العثور على باركود في الصورة. حاول التقاط صورة أوضح وأقرب للباركود.');
                    }
                });
            }

            // Function to fetch product by barcode
            function fetchProductByBarcode(barcode) {
                if (!barcode) return; // Prevent fetching if barcode is empty
                fetch(`{{ url('products/by-barcode') }}/${barcode}`, {
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Auto-fill form with product details
                            document.getElementById('name').value = data.product.name;
                            document.getElementById('category').value = data.product.category || '';
                            document.getElementById('stock_quantity').value = data.product.stock_quantity;
                            document.getElementById('quantity_alert').value = data.product.quantity_alert || '';
                            document.getElementById('min_order').value = data.product.min_order || '';
                            // Redirect to edit page for existing product
                            window.location.href = `{{ url('products') }}/${data.product.id}/edit`;
                        } else {
                            // Clear form fields if no product is found
                            document.getElementById('name').value = '';
                            document.getElementById('category').value = '';
                            document.getElementById('stock_quantity').value = 0;
                            document.getElementById('quantity_alert').value = '';
                            document.getElementById('min_order').value = '';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching product:', error);
                    });
            }

            // Barcode scan button event — native BarcodeDetector with html5-qrcode fallback
            let scanHandle = null;
            const scanVideo = document.getElementById('scan-video');
            const scanCancelBtn = document.getElementById('scan-cancel');

            function stopScanner() {
                if (scanHandle) { scanHandle.stop(); scanHandle = null; }
                torchBtn.classList.add('hidden');
                scannerContainer.classList.add('hidden');
            }

            scanButton.addEventListener('click', async () => {
                // Toggle off if already scanning
                if (scanHandle) { stopScanner(); return; }

                scannerContainer.classList.remove('hidden');
                try {
                    scanHandle = await startBarcodeScan({
                        videoEl: scanVideo,
                        readerElId: 'reader',
                        onDetect: (text) => {
                            barcodeInput.value = text;
                            stopScanner();
                            fetchProductByBarcode(text);
                        }
                    });

                    // Flashlight button, if the device supports it
                    if (scanHandle.torchAvailable && scanHandle.torchAvailable()) {
                        torchBtn.classList.remove('hidden');
                        let torchState = false;
                        torchBtn.onclick = () => { torchState = !torchState; scanHandle.setTorch(torchState); };
                    }
                } catch (err) {
                    console.error('Failed to start scanner:', err);
                    scannerContainer.classList.add('hidden');
                    alert('فشل في تشغيل الماسح. تأكد من السماح باستخدام الكاميرا وأن الموقع يعمل عبر HTTPS.');
                }
            });

            if (scanCancelBtn) scanCancelBtn.addEventListener('click', stopScanner);

            // Barcode input change event
            barcodeInput.addEventListener('input', () => {
                const barcode = barcodeInput.value.trim();
                if (barcode.length > 0) {
                    fetchProductByBarcode(barcode);
                } else {
                    // Clear form fields if barcode input is empty
                    document.getElementById('name').value = '';
                    document.getElementById('category').value = '';
                    document.getElementById('stock_quantity').value = 0;
                    document.getElementById('quantity_alert').value = '';
                    document.getElementById('min_order').value = '';
                }
            });
        });
    </script>
@endpush
