// public/assets/js/shared/face-capture.js
// Shared camera + face-descriptor capture flow, used by both attendance
// verification (pos/attendance_scan.php) and face-ID enrollment
// (shared/profile.php). Builds its own full-screen overlay so callers just
// need to call ShelfFaceCapture.run() and get descriptors back.
//
// Biometric-data notice: this always shows an explicit consent screen
// before the camera is requested, and the resulting descriptors (numeric
// face measurements, not the raw photo) are what gets sent to the server.

(function () {
    const MODEL_BASE = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights';
    let modelsLoadingPromise = null;

    function loadModels() {
        if (modelsLoadingPromise) return modelsLoadingPromise;
        modelsLoadingPromise = Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_BASE),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_BASE),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_BASE)
        ]);
        return modelsLoadingPromise;
    }

    function buildOverlay() {
        const overlay = document.createElement('div');
        overlay.id = 'shelfFaceCaptureOverlay';
        overlay.innerHTML = `
            <div class="sfc-box">
                <div class="sfc-consent" id="sfcConsent">
                    <i class="bi bi-shield-lock" style="font-size:2rem;color:#ff6b35;"></i>
                    <h5 class="mt-2 mb-2">Face verification uses your camera</h5>
                    <p class="sfc-consent-text">
                        This captures a few short-lived numeric face measurements (not a video recording)
                        to verify your identity. Your photo is stored only as attendance evidence and is
                        visible to HR. You can remove your Face ID enrollment at any time from your Profile.
                        Nothing is shared outside ShelfSense.
                    </p>
                    <div class="d-flex gap-2 justify-content-center mt-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="sfcDecline">Cancel</button>
                        <button type="button" class="btn btn-sm btn-yellow-primary" id="sfcAgree">I agree, continue</button>
                    </div>
                </div>
                <div class="sfc-camera" id="sfcCamera" style="display:none;">
                    <div class="sfc-video-wrap">
                        <video id="sfcVideo" autoplay playsinline muted></video>
                        <canvas id="sfcCanvas"></canvas>
                        <div class="sfc-oval"></div>
                    </div>
                    <div class="sfc-status" id="sfcStatus">Loading camera&hellip;</div>
                    <div class="sfc-dots" id="sfcDots"></div>
                    <div class="sfc-debug" id="sfcDebug" style="display:none;"></div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="sfcCancelBtn">Cancel</button>
                </div>
                <div class="sfc-error" id="sfcError" style="display:none;">
                    <i class="bi bi-exclamation-triangle text-danger" style="font-size:1.6rem;"></i>
                    <p id="sfcErrorText" class="mb-2 mt-2"></p>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="sfcErrorClose">Close</button>
                </div>
            </div>`;
        const style = document.createElement('style');
        style.textContent = `
            #shelfFaceCaptureOverlay { position:fixed; inset:0; z-index:30000; background:rgba(20,16,15,0.92);
                display:flex; align-items:center; justify-content:center; padding:20px; }
            #shelfFaceCaptureOverlay .sfc-box { background:var(--bg-card,#fff); color:var(--text-main,#20201d);
                border-radius:16px; padding:24px; max-width:420px; width:100%; text-align:center; }
            #shelfFaceCaptureOverlay .sfc-consent-text { font-size:0.85rem; color:var(--text-muted,#73736f); }
            #shelfFaceCaptureOverlay .sfc-video-wrap { position:relative; width:260px; height:260px; margin:0 auto;
                border-radius:50%; overflow:hidden; background:#000; }
            #shelfFaceCaptureOverlay .sfc-video-wrap video { width:100%; height:100%; object-fit:cover; transform:scaleX(-1); }
            #shelfFaceCaptureOverlay .sfc-video-wrap canvas { display:none; }
            #shelfFaceCaptureOverlay .sfc-oval { position:absolute; inset:0; border-radius:50%; pointer-events:none;
                box-shadow: inset 0 0 0 4px rgba(255,107,53,0.7); }
            #shelfFaceCaptureOverlay .sfc-status { margin-top:14px; font-weight:600; }
            #shelfFaceCaptureOverlay .sfc-dots { display:flex; gap:6px; justify-content:center; margin-top:8px; }
            #shelfFaceCaptureOverlay .sfc-dots span { width:9px; height:9px; border-radius:50%;
                background:var(--border-color,#ebebe7); }
            #shelfFaceCaptureOverlay .sfc-dots span.done { background:#2fb380; }
            #shelfFaceCaptureOverlay .sfc-dots span.active { background:#ff6b35; }
            #shelfFaceCaptureOverlay .sfc-debug { margin-top:8px; font-family:monospace; font-size:0.7rem;
                color:var(--text-muted,#73736f); }
        `;
        document.body.appendChild(style);
        document.body.appendChild(overlay);
        return overlay;
    }

    function euclideanDistance(a, b) {
        let sum = 0;
        for (let i = 0; i < a.length; i++) sum += (a[i] - b[i]) * (a[i] - b[i]);
        return Math.sqrt(sum);
    }

    function pointDist(p1, p2) {
        return Math.hypot(p1.x - p2.x, p1.y - p2.y);
    }

    // Eye Aspect Ratio (Soukupova & Cech) from a 6-point eye landmark set --
    // drops sharply when the eye closes, so tracking it across frames is a
    // cheap, no-extra-model way to prove a live blink happened rather than
    // a static photo being held up to the camera.
    function eyeAspectRatio(eye) {
        const vertical1 = pointDist(eye[1], eye[5]);
        const vertical2 = pointDist(eye[2], eye[4]);
        const horizontal = pointDist(eye[0], eye[3]);
        return (vertical1 + vertical2) / (2 * horizontal);
    }

    const EAR_CLOSED = 0.22;
    const EAR_OPEN = 0.27;
    const BLINKS_REQUIRED = 2;
    const BLINK_TIMEOUT_MS = 25000;

    // Requires two full open->closed->open cycles before continuing --
    // rejects a printed photo or a frozen video frame held up to the
    // camera, which can supply a face descriptor but can't blink on cue.
    async function runBlinkLiveness(video, statusEl, dotsEl, debugEl) {
        dotsEl.innerHTML = '<span></span><span></span>';
        const dots = dotsEl.querySelectorAll('span');
        statusEl.textContent = 'Blink twice to verify you\'re really here';

        let blinkCount = 0;
        let eyesClosed = false;
        let minEarSeen = 1;
        let maxEarSeen = 0;
        const deadline = Date.now() + BLINK_TIMEOUT_MS;

        while (blinkCount < BLINKS_REQUIRED) {
            if (Date.now() > deadline) {
                throw new Error('Could not detect a blink in time. Make sure your eyes are clearly visible and try again.');
            }

            const detection = await faceapi
                .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks();

            if (detection) {
                const leftEAR = eyeAspectRatio(detection.landmarks.getLeftEye());
                const rightEAR = eyeAspectRatio(detection.landmarks.getRightEye());
                const ear = (leftEAR + rightEAR) / 2;
                minEarSeen = Math.min(minEarSeen, ear);
                maxEarSeen = Math.max(maxEarSeen, ear);

                // Temporary live readout while we calibrate thresholds
                // against real cameras -- remove once EAR_CLOSED/EAR_OPEN
                // are confirmed to work reliably in practice.
                if (debugEl) {
                    debugEl.textContent = 'EAR: ' + ear.toFixed(3) + ' (' + (eyesClosed ? 'closed' : 'open') + ') '
                        + 'min:' + minEarSeen.toFixed(3) + ' max:' + maxEarSeen.toFixed(3);
                }

                if (!eyesClosed && ear < EAR_CLOSED) {
                    eyesClosed = true;
                } else if (eyesClosed && ear > EAR_OPEN) {
                    eyesClosed = false;
                    blinkCount++;
                    if (dots[blinkCount - 1]) dots[blinkCount - 1].classList.add('done');
                }
            } else if (debugEl) {
                debugEl.textContent = 'No face detected';
            }
        }

        statusEl.textContent = 'Liveness verified!';
        await new Promise(r => setTimeout(r, 400));
    }

    async function run(options) {
        options = options || {};
        const angles = options.angles || [
            'Look straight at the camera',
            'Slowly turn your head slightly left',
            'Slowly turn your head slightly right'
        ];

        const overlay = buildOverlay();
        const cleanup = () => { overlay.remove(); };

        return new Promise((resolve, reject) => {
            let stream = null;
            let cancelled = false;

            const agreeBtn = overlay.querySelector('#sfcAgree');
            const declineBtn = overlay.querySelector('#sfcDecline');
            const cancelBtn = overlay.querySelector('#sfcCancelBtn');
            const errorCloseBtn = overlay.querySelector('#sfcErrorClose');

            function showError(msg) {
                overlay.querySelector('#sfcConsent').style.display = 'none';
                overlay.querySelector('#sfcCamera').style.display = 'none';
                overlay.querySelector('#sfcError').style.display = 'block';
                overlay.querySelector('#sfcErrorText').textContent = msg;
            }

            function stopStream() {
                if (stream) {
                    stream.getTracks().forEach(t => t.stop());
                    stream = null;
                }
            }

            declineBtn.addEventListener('click', () => {
                cancelled = true;
                cleanup();
                reject(new Error('cancelled'));
            });
            errorCloseBtn.addEventListener('click', () => {
                stopStream();
                cleanup();
                reject(new Error('cancelled'));
            });
            cancelBtn.addEventListener('click', () => {
                cancelled = true;
                stopStream();
                cleanup();
                reject(new Error('cancelled'));
            });

            agreeBtn.addEventListener('click', async () => {
                overlay.querySelector('#sfcConsent').style.display = 'none';
                overlay.querySelector('#sfcCamera').style.display = 'block';
                const statusEl = overlay.querySelector('#sfcStatus');
                const dotsEl = overlay.querySelector('#sfcDots');
                const debugEl = overlay.querySelector('#sfcDebug');
                debugEl.style.display = 'block';

                try {
                    statusEl.textContent = 'Loading face model…';
                    await loadModels();

                    statusEl.textContent = 'Requesting camera access…';
                    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
                    if (cancelled) { stopStream(); return; }

                    const video = overlay.querySelector('#sfcVideo');
                    video.srcObject = stream;
                    await new Promise(res => { video.onloadedmetadata = res; });

                    if (cancelled) return;
                    await runBlinkLiveness(video, statusEl, dotsEl, debugEl);
                    if (cancelled) return;
                    debugEl.style.display = 'none';

                    dotsEl.innerHTML = angles.map(() => '<span></span>').join('');
                    const angleDots = dotsEl.querySelectorAll('span');

                    const descriptors = [];
                    let photoDataUrl = null;

                    for (let i = 0; i < angles.length; i++) {
                        if (cancelled) return;
                        angleDots[i].classList.add('active');
                        statusEl.textContent = angles[i];

                        let captured = false;
                        let attempts = 0;
                        while (!captured && attempts < 60 && !cancelled) {
                            attempts++;
                            const detection = await faceapi
                                .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                                .withFaceLandmarks()
                                .withFaceDescriptor();

                            if (detection && detection.descriptor) {
                                descriptors.push(Array.from(detection.descriptor));
                                if (!photoDataUrl) {
                                    const canvas = overlay.querySelector('#sfcCanvas');
                                    canvas.width = video.videoWidth;
                                    canvas.height = video.videoHeight;
                                    const ctx = canvas.getContext('2d');
                                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                                    photoDataUrl = canvas.toDataURL('image/jpeg', 0.85);
                                }
                                captured = true;
                            } else {
                                await new Promise(r => setTimeout(r, 150));
                            }
                        }

                        if (!captured) {
                            throw new Error('Could not see your face clearly. Make sure you\'re in good lighting and try again.');
                        }

                        angleDots[i].classList.remove('active');
                        angleDots[i].classList.add('done');
                        await new Promise(r => setTimeout(r, 250));
                    }

                    if (cancelled) return;
                    statusEl.textContent = 'Done!';
                    stopStream();
                    cleanup();
                    resolve({ descriptors, photo: photoDataUrl, blinkVerified: true });
                } catch (err) {
                    stopStream();
                    if (cancelled) return;
                    if (err && err.name === 'NotAllowedError') {
                        showError('Camera access was denied. Please allow camera access and try again.');
                    } else {
                        showError(err.message || 'Something went wrong while capturing your face.');
                    }
                    reject(err);
                }
            });
        });
    }

    window.ShelfFaceCapture = { run, euclideanDistance, loadModels };
})();
