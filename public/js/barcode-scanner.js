// Reusable live barcode/QR scanner for nvtmarket-app.
// Prefers the native BarcodeDetector (Android Chrome, hardware-accelerated) and
// falls back to html5-qrcode when it is not available.
//
// Usage:
//   const handle = await startBarcodeScan({
//       videoEl,        // <video> element for the native path
//       readerElId,     // id of the <div> used by the html5-qrcode fallback
//       onDetect: (text) => { ... }   // called once with the decoded string
//   });
//   handle.stop();                 // stop the camera
//   handle.torchAvailable();       // boolean
//   handle.setTorch(true|false);   // toggle flashlight
(function () {
    const FORMATS = [
        'ean_13', 'ean_8', 'upc_a', 'upc_e',
        'code_128', 'code_39', 'itf', 'codabar', 'qr_code'
    ];

    async function nativeSupported() {
        if (!('BarcodeDetector' in window)) return false;
        try {
            const supported = await BarcodeDetector.getSupportedFormats();
            return supported.includes('ean_13') ||
                   supported.includes('code_128') ||
                   supported.includes('qr_code');
        } catch (_) {
            return false;
        }
    }

    // Native path: continuous BarcodeDetector loop on a high-res video stream.
    async function startNative(videoEl, onDetect) {
        const supported = await BarcodeDetector.getSupportedFormats();
        const formats = supported.filter(f => FORMATS.includes(f));
        const detector = new BarcodeDetector({ formats });

        const stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: { ideal: 'environment' },
                width:  { ideal: 1920 },
                height: { ideal: 1080 },
                // Focus hints; browsers ignore unknown constraints safely.
                advanced: [{ focusMode: 'continuous' }]
            },
            audio: false
        });

        videoEl.srcObject = stream;
        videoEl.setAttribute('playsinline', 'true');
        videoEl.muted = true;
        await videoEl.play();

        const track = stream.getVideoTracks()[0];
        let running = true;

        async function loop() {
            if (!running) return;
            try {
                const codes = await detector.detect(videoEl);
                if (codes && codes.length) {
                    const text = codes[0].rawValue;
                    if (text) { stop(); onDetect(text); return; }
                }
            } catch (_) {
                // Transient per-frame errors are normal; keep looping.
            }
            requestAnimationFrame(loop);
        }
        requestAnimationFrame(loop);

        function stop() {
            running = false;
            try { if (track) track.stop(); } catch (_) {}
            if (videoEl) { try { videoEl.pause(); } catch (_) {} videoEl.srcObject = null; }
        }

        function caps() {
            try { return track && track.getCapabilities ? track.getCapabilities() : {}; }
            catch (_) { return {}; }
        }

        return {
            stop,
            torchAvailable: () => !!caps().torch,
            setTorch: (on) => {
                if (!caps().torch) return Promise.resolve();
                return track.applyConstraints({ advanced: [{ torch: !!on }] }).catch(() => {});
            }
        };
    }

    // Fallback path: html5-qrcode (used only when BarcodeDetector is missing).
    async function startFallback(readerElId, onDetect) {
        const scanner = new Html5Qrcode(readerElId);
        await scanner.start(
            { facingMode: 'environment' },
            {
                fps: 15,
                qrbox: { width: 300, height: 150 },
                aspectRatio: 1.777,
                videoConstraints: {
                    facingMode: 'environment',
                    width:  { ideal: 1920 },
                    height: { ideal: 1080 },
                    focusMode: 'continuous'
                },
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.EAN_8,
                    Html5QrcodeSupportedFormats.UPC_A,
                    Html5QrcodeSupportedFormats.UPC_E,
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.CODE_39,
                    Html5QrcodeSupportedFormats.ITF,
                    Html5QrcodeSupportedFormats.CODABAR,
                    Html5QrcodeSupportedFormats.QR_CODE
                ]
            },
            (text) => { scanner.stop().catch(() => {}); onDetect(text); },
            () => {}
        );

        function runningTrack() {
            try {
                const v = document.querySelector('#' + readerElId + ' video');
                return v && v.srcObject ? v.srcObject.getVideoTracks()[0] : null;
            } catch (_) { return null; }
        }
        function caps() {
            const t = runningTrack();
            try { return t && t.getCapabilities ? t.getCapabilities() : {}; }
            catch (_) { return {}; }
        }

        return {
            stop: () => scanner.stop().catch(() => {}),
            torchAvailable: () => !!caps().torch,
            setTorch: (on) => {
                const t = runningTrack();
                if (!t || !caps().torch) return Promise.resolve();
                return t.applyConstraints({ advanced: [{ torch: !!on }] }).catch(() => {});
            }
        };
    }

    // Public entry point.
    window.startBarcodeScan = async function (opts) {
        const onDetect = (text) => {
            if (navigator.vibrate) navigator.vibrate(100);
            opts.onDetect(text);
        };
        if (await nativeSupported()) {
            return startNative(opts.videoEl, onDetect);
        }
        return startFallback(opts.readerElId, onDetect);
    };
})();
