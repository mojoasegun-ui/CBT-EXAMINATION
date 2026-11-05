/* ExamCore proctoring: camera + face detection that runs entirely on the student's device.
 *
 * Uses face-api (TensorFlow.js) served from this site, so it is free, needs no AI service and
 * keeps working offline once loaded. Only small JPEG snapshots (~15 KB) are sent to the school.
 */
window.ExamProctor = (() => {
    "use strict";
    let api = null;
    let loading = null;
    const MATCH_OK = 0.55;      // same person below this distance
    const MATCH_BAD = 0.62;     // clearly a different person above this

    function loadScript(src) {
        return new Promise((resolve, reject) => {
            if (window.faceapi) return resolve(window.faceapi);
            const s = document.createElement("script");
            s.src = src;
            s.onload = () => resolve(window.faceapi);
            s.onerror = () => reject(new Error("Could not load the face check. Check your connection and refresh."));
            document.head.appendChild(s);
        });
    }

    async function load(cfg) {
        if (api) return api;
        loading ??= (async () => {
            const faceapi = await loadScript(cfg.lib);
            await faceapi.tf?.ready?.();
            await faceapi.nets.tinyFaceDetector.loadFromUri(cfg.models);
            if (cfg.mode === "match") {
                await faceapi.nets.faceLandmark68TinyNet.loadFromUri(cfg.models);
                await faceapi.nets.faceRecognitionNet.loadFromUri(cfg.models);
            }
            api = faceapi;
            return api;
        })();
        return loading;
    }

    const options = (size = 224) => new api.TinyFaceDetectorOptions({ inputSize: size, scoreThreshold: 0.45 });

    async function camera(video) {
        if (!navigator.mediaDevices?.getUserMedia) throw new Error("This browser cannot use the camera. Use Chrome on Android or a computer with a webcam.");
        const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "user", width: { ideal: 640 }, height: { ideal: 480 } }, audio: false });
        video.srcObject = stream;
        video.muted = true;
        video.setAttribute("playsinline", "");
        await video.play();
        return stream;
    }

    /** Face descriptor of the school photo (cached for this browser tab). */
    async function reference(cfg) {
        if (cfg.mode !== "match" || !cfg.photo) return null;
        const key = `xr-ref-${cfg.photo}`;
        try { const hit = sessionStorage.getItem(key); if (hit) return new Float32Array(JSON.parse(hit)); } catch { /* ignore */ }
        const img = await api.fetchImage(cfg.photo);
        const r = await api.detectSingleFace(img, options(416)).withFaceLandmarks(true).withFaceDescriptor();
        if (!r) return null;
        try { sessionStorage.setItem(key, JSON.stringify(Array.from(r.descriptor))); } catch { /* ignore */ }
        return r.descriptor;
    }

    /** How many faces are in view, and (match mode) how close the face is to the school photo. */
    async function analyse(video, ref) {
        const found = await api.detectAllFaces(video, options());
        const out = { faces: found.length, distance: null };
        if (ref && found.length === 1) {
            const r = await api.detectSingleFace(video, options(320)).withFaceLandmarks(true).withFaceDescriptor();
            if (r) out.distance = Math.round(api.euclideanDistance(ref, r.descriptor) * 1000) / 1000;
        }
        return out;
    }

    function capture(video, width = 320) {
        const c = document.createElement("canvas");
        const ratio = (video.videoHeight || 480) / (video.videoWidth || 640);
        c.width = width;
        c.height = Math.round(width * ratio);
        c.getContext("2d").drawImage(video, 0, 0, c.width, c.height);
        return new Promise((resolve) => c.toBlob((b) => resolve(b), "image/jpeg", 0.6));
    }

    async function upload(url, csrf, blob, fields) {
        const fd = new FormData();
        fd.append("image", blob, "snap.jpg");
        Object.entries(fields).forEach(([k, v]) => { if (v !== null && v !== undefined) fd.append(k, String(v)); });
        const res = await fetch(url, { method: "POST", body: fd, credentials: "same-origin", headers: { Accept: "application/json", "X-CSRF-Token": csrf } });
        const fresh = res.headers.get("X-CSRF-Token");
        const data = await res.json().catch(() => ({}));
        return { ok: res.ok, status: res.status, data, csrf: fresh };
    }

    return { load, camera, reference, analyse, capture, upload, MATCH_OK, MATCH_BAD };
})();

/* ---- Lobby: face check before Start ------------------------------------------------------ */
(() => {
    const box = document.querySelector("[data-face-check]");
    if (!box) return;
    const cfg = JSON.parse(box.dataset.faceCheck);
    const P = window.ExamProctor;
    const $ = (s) => box.querySelector(s);
    const video = $("video");
    const status = $("[data-face-status]");
    const btn = $("[data-face-run]");
    const startBtn = document.querySelector("[data-start-button]");
    const pinBox = document.querySelector("[data-pin-box]");
    let csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";
    let ref = null;
    const say = (text, tone = "muted") => {
        status.textContent = text;
        status.className = `text-sm ${({ ok: "font-semibold text-emerald-600", bad: "font-semibold text-red-600", muted: "text-muted" })[tone]}`;
    };
    const unlock = () => { startBtn?.removeAttribute("disabled"); box.classList.add("is-verified"); };
    if (cfg.verified) { say("Face check complete. You can start.", "ok"); unlock(); return; }

    btn.addEventListener("click", async () => {
        btn.disabled = true;
        try {
            if (!video.srcObject) {
                say("Allow the camera when your browser asks…");
                await P.camera(video);
            }
            say(cfg.mode === "match" ? "Loading face check (first time only, about 8 MB)…" : "Loading face check…");
            await P.load(cfg);
            if (cfg.mode === "match" && !ref) {
                ref = await P.reference(cfg).catch(() => null);
            }
            say("Look straight at the camera. Checking…");
            await new Promise((r) => setTimeout(r, 600));
            const a = await P.analyse(video, ref);
            if (a.faces === 0) { say("No face found. Move into good light and look at the camera.", "bad"); return; }
            if (a.faces > 1) { say("More than one face in view. Only you should be in front of the camera.", "bad"); return; }
            let result = "ok";
            if (cfg.mode === "match") {
                result = !ref ? "no_photo" : (a.distance !== null && a.distance <= P.MATCH_OK ? "ok" : "mismatch");
            }
            const blob = await P.capture(video, 480);
            const r = await P.upload(cfg.verifyUrl, csrf, blob, { faces: a.faces, score: a.distance, result });
            if (r.csrf) csrf = r.csrf;
            if (r.data.status === "verified") {
                say(result === "no_photo" ? "Face captured (your school has no photo on file to compare). You can start." : "Face check complete. You can start.", "ok");
                unlock();
                video.srcObject?.getTracks().forEach((t) => t.stop());
                btn.classList.add("hidden");
            } else if (r.data.status === "mismatch") {
                say(r.data.message || "Your face does not match your school photo. Try again in better light, or ask your invigilator.", "bad");
                if (r.data.pin) pinBox?.classList.remove("hidden");
            } else {
                say("Try again: keep your face in the middle of the picture.", "bad");
            }
        } catch (e) {
            say(e && e.name === "NotAllowedError" ? "Camera permission was refused. Allow the camera for this site, or ask your invigilator." : (e.message || "The face check could not run on this device."), "bad");
            pinBox?.classList.remove("hidden");
        } finally {
            btn.disabled = false;
        }
    });
})();
