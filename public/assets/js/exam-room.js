/* ExamCore exam room.
 *
 * The whole paper arrives with the page, so the exam keeps working when the network drops.
 * Every change is written to this device first (localStorage) and then sent to the server in
 * small batches. The server owns the clock; this page only displays it.
 */
(() => {
    "use strict";
    const dataEl = document.getElementById("exam-data");
    if (!dataEl) return;
    const D = JSON.parse(dataEl.textContent);
    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
    const KEY = `xr-${D.sitting}`;
    const LETTERS = ["A", "B", "C", "D", "E"];
    const Q = D.questions;
    const byN = new Map(Q.map((q) => [q.n, q]));
    const papers = [];
    Q.forEach((q) => { if (!papers.find((p) => p.id === q.paper)) papers.push({ id: q.paper, subject: q.subject, first: q.n }); });

    /* ---- State: server copy merged with anything still waiting on this device ------------- */
    const answers = new Map(Q.map((q) => [q.n, { a: q.answer, f: q.flagged }]));
    let pending = {};
    let events = [];
    let current = D.position || 1;
    let furthest = current;
    let deadline = D.deadline;
    let offset = D.serverNow - Date.now() / 1000;
    let finished = false;
    try {
        const saved = JSON.parse(localStorage.getItem(KEY) || "null");
        if (saved) {
            pending = saved.pending || {};
            Object.entries(pending).forEach(([n, v]) => answers.set(Number(n), v));
            current = saved.current || current;
            furthest = Math.max(saved.furthest || 0, current);
        }
    } catch { /* storage blocked: still works, just without device backup */ }
    if (!byN.has(current)) current = 1;
    const persist = () => { try { localStorage.setItem(KEY, JSON.stringify({ pending, current, furthest })); } catch { /* ignore */ } };

    /* ---- Small UI helpers ------------------------------------------------------------------ */
    let toastTimer;
    const toast = (text) => {
        const el = $("[data-xr-toast]");
        el.textContent = text;
        el.classList.remove("hidden");
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => el.classList.add("hidden"), 3500);
    };
    const net = (state, text) => {
        const el = $("[data-net]");
        el.classList.toggle("is-saving", state === "saving");
        el.classList.toggle("is-offline", state === "offline");
        $("[data-net-text]").textContent = text;
    };
    const overlay = (title, text) => {
        $("[data-overlay-title]").textContent = title;
        $("[data-overlay-text]").textContent = text;
        $("[data-overlay]").classList.remove("hidden");
    };

    /* ---- Rendering ------------------------------------------------------------------------- */
    const canVisit = (n) => D.settings.allowBack || n >= furthest;

    function render() {
        const q = byN.get(current);
        const state = answers.get(current);
        const paperQs = Q.filter((x) => x.paper === q.paper);
        $("[data-q-number]").textContent = `Question ${current}`;
        $("[data-q-of]").textContent = `of ${Q.length} · ${q.subject} ${paperQs.indexOf(q) + 1}/${paperQs.length}`;
        $("[data-q-body]").innerHTML = q.html;
        const img = $("[data-q-image]");
        img.classList.toggle("hidden", !q.image);
        if (q.image) img.src = q.image; else img.removeAttribute("src");
        const passageBox = $("[data-passage]");
        const passage = q.passage ? D.passages[q.passage] : null;
        passageBox.classList.toggle("hidden", !passage);
        if (passage) {
            $("[data-passage-title]").textContent = passage.title || "Read the passage";
            $("[data-passage-body]").innerHTML = passage.html;
        }
        const box = $("[data-options]");
        box.innerHTML = "";
        q.options.forEach((opt, i) => {
            const b = document.createElement("button");
            b.type = "button";
            b.className = "xr-opt";
            b.setAttribute("role", "radio");
            b.setAttribute("aria-checked", String(state.a === opt.key));
            b.dataset.key = opt.key;
            b.innerHTML = `<span class="xr-opt-letter">${LETTERS[i]}</span><span class="min-w-0 pt-0.5">${opt.html}</span>`;
            b.addEventListener("click", () => choose(opt.key));
            box.appendChild(b);
        });
        const flag = $("[data-flag]");
        flag.setAttribute("aria-pressed", String(!!state.f));
        flag.classList.toggle("btn-primary", !!state.f);
        flag.classList.toggle("btn-secondary", !state.f);
        $("[data-flag] span").textContent = state.f ? "Flagged" : "Flag for review";
        $("[data-prev]").disabled = current === 1 || !canVisit(current - 1);
        $("[data-prev]").classList.toggle("invisible", !D.settings.allowBack);
        $("[data-next]").disabled = current === Q.length;
        $$("[data-tabs] button").forEach((t) => t.setAttribute("aria-current", String(Number(t.dataset.paper) === q.paper)));
        renderPalette();
        $("[data-question]").scrollIntoView({ block: "nearest" });
    }

    function renderTabs() {
        const nav = $("[data-tabs]");
        nav.classList.toggle("hidden", papers.length < 2);
        nav.innerHTML = "";
        papers.forEach((p) => {
            const b = document.createElement("button");
            b.type = "button";
            b.className = "xr-tab";
            b.dataset.paper = p.id;
            b.innerHTML = `<span></span><small></small>`;
            b.firstChild.textContent = p.subject;
            b.addEventListener("click", () => go(p.first));
            nav.appendChild(b);
        });
    }

    function renderPalette() {
        const box = $("[data-palette]");
        if (!box.childElementCount) {
            papers.forEach((p) => {
                const wrap = document.createElement("div");
                wrap.innerHTML = `<p class="mb-2 text-xs font-semibold uppercase tracking-wider text-subtle"></p><div class="xr-pal"></div>`;
                wrap.firstChild.textContent = p.subject;
                Q.filter((q) => q.paper === p.id).forEach((q) => {
                    const b = document.createElement("button");
                    b.type = "button";
                    b.textContent = q.n;
                    b.dataset.n = q.n;
                    b.setAttribute("aria-label", `Question ${q.n}`);
                    b.addEventListener("click", () => { go(q.n); closeSheet(); });
                    wrap.lastChild.appendChild(b);
                });
                box.appendChild(wrap);
            });
        }
        let answered = 0;
        $$("[data-palette] button").forEach((b) => {
            const n = Number(b.dataset.n);
            const s = answers.get(n);
            if (s.a) answered++;
            b.classList.toggle("is-answered", !!s.a);
            b.classList.toggle("is-flagged", !!s.f);
            b.setAttribute("aria-current", String(n === current));
            b.disabled = !canVisit(n);
        });
        $("[data-count-answered]").textContent = answered;
        $("[data-count-left]").textContent = Q.length - answered;
        $("[data-sheet-label]").textContent = `Questions · ${answered}/${Q.length}`;
        $("[data-progress]").style.width = `${(answered / Q.length) * 100}%`;
        papers.forEach((p) => {
            const tab = $(`[data-tabs] button[data-paper="${p.id}"] small`);
            if (tab) tab.textContent = `${Q.filter((q) => q.paper === p.id && answers.get(q.n).a).length}/${Q.filter((q) => q.paper === p.id).length}`;
        });
    }

    /* ---- Actions --------------------------------------------------------------------------- */
    function change(n, next) {
        if (finished) return;
        answers.set(n, next);
        pending[n] = next;
        persist();
        scheduleSync(1200);
        render();
    }
    const choose = (key) => change(current, { ...answers.get(current), a: key });
    const go = (n) => {
        if (!byN.has(n) || !canVisit(n)) return;
        current = n;
        furthest = Math.max(furthest, n);
        persist();
        render();
    };
    $("[data-next]").addEventListener("click", () => go(current + 1));
    $("[data-prev]").addEventListener("click", () => go(current - 1));
    $("[data-clear]").addEventListener("click", () => change(current, { ...answers.get(current), a: null }));
    $("[data-flag]").addEventListener("click", () => change(current, { ...answers.get(current), f: !answers.get(current).f }));

    // Text size (remembered on this device)
    let size = 17;
    try { size = Number(localStorage.getItem("xr-size")) || 17; } catch { /* ignore */ }
    const applySize = () => document.documentElement.style.setProperty("--xr-size", `${size}px`);
    applySize();
    $$("[data-size]").forEach((b) => b.addEventListener("click", () => {
        size = Math.max(14, Math.min(24, size + Number(b.dataset.size) * 2));
        applySize();
        try { localStorage.setItem("xr-size", String(size)); } catch { /* ignore */ }
    }));

    // Phone palette sheet
    const sheet = $("[data-sheet]");
    const closeSheet = () => sheet.classList.remove("is-open");
    $("[data-open-sheet]")?.addEventListener("click", () => sheet.classList.add("is-open"));
    $("[data-close-sheet]")?.addEventListener("click", closeSheet);

    // Keyboard: A–E answer, N/→ next, P/← previous, F flag
    document.addEventListener("keydown", (e) => {
        if (e.ctrlKey || e.metaKey) {
            if (["p", "s", "c", "u"].includes(e.key.toLowerCase())) {
                e.preventDefault();
                if (e.key.toLowerCase() === "p") logEvent("print_attempt");
            }
            return;
        }
        if (document.querySelector("dialog[open]") || e.altKey) return;
        const k = e.key.toUpperCase();
        const q = byN.get(current);
        const i = LETTERS.indexOf(k);
        if (i >= 0 && q.options[i]) { e.preventDefault(); choose(q.options[i].key); }
        else if (k === "N" || e.key === "ArrowRight") go(current + 1);
        else if (k === "P" || e.key === "ArrowLeft") go(current - 1);
        else if (k === "F") $("[data-flag]").click();
    });

    /* ---- Saving ---------------------------------------------------------------------------- */
    let syncTimer = null;
    let syncing = false;
    let failures = 0;
    function scheduleSync(ms) {
        clearTimeout(syncTimer);
        syncTimer = setTimeout(sync, ms);
    }
    function logEvent(type, detail = "") {
        events.push({ type, detail: String(detail).slice(0, 200) });
        scheduleSync(800);
    }
    async function post(url, body) {
        const res = await fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-Token": D.csrf },
            body: JSON.stringify(body),
        });
        const fresh = res.headers.get("X-CSRF-Token");
        if (fresh) D.csrf = fresh;
        if (res.status === 401) { location.reload(); throw new Error("signed out"); }
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    }
    async function sync() {
        if (syncing || finished) return;
        syncing = true;
        const batch = pending;
        const sentEvents = events;
        pending = {};
        events = [];
        net("saving", "Saving…");
        try {
            const r = await post(D.urls.sync, { answers: batch, events: sentEvents, position: current });
            failures = 0;
            noteWarnings(r.warnings);
            offset = r.serverNow - Date.now() / 1000;
            if (r.deadline !== deadline) {
                if (r.deadline > deadline) toast(`Your teacher added ${Math.round((r.deadline - deadline) / 60)} minute(s).`);
                deadline = r.deadline;
            }
            net("ok", "Saved");
            persist();
            if (r.status !== "in_progress") return finish(false);
        } catch {
            pending = { ...batch, ...pending };
            events = [...sentEvents, ...events].slice(-40);
            persist();
            failures++;
            net("offline", "Offline · answers kept on this device");
        } finally {
            syncing = false;
        }
        if (Object.keys(pending).length || events.length) scheduleSync(Math.min(30000, 2000 * 2 ** Math.min(failures, 4)));
    }
    setInterval(() => { if (!syncing) sync(); }, 25000); // heartbeat: shows teacher you are online, picks up added time
    addEventListener("online", () => { toast("Back online. Saving your answers…"); sync(); });
    addEventListener("offline", () => net("offline", "Offline · answers kept on this device"));

    /* ---- Clock ----------------------------------------------------------------------------- */
    const warned = new Set();
    function tick() {
        const left = Math.max(0, Math.round(deadline - (Date.now() / 1000 + offset)));
        const h = Math.floor(left / 3600), m = Math.floor((left % 3600) / 60), s = left % 60;
        $("[data-timer-text]").textContent = `${h ? `${h}:` : ""}${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}`;
        const t = $("[data-timer]");
        t.classList.toggle("is-warn", left <= 300 && left > 60);
        t.classList.toggle("is-danger", left <= 60);
        [[300, "5 minutes left."], [60, "1 minute left. Check your answers."]].forEach(([at, msg]) => {
            if (left <= at && left > at - 5 && !warned.has(at)) { warned.add(at); toast(msg); navigator.vibrate?.(200); }
        });
        if (left === 0 && !finished) finish(true);
    }
    tick();
    setInterval(tick, 1000);

    /* ---- Submit ---------------------------------------------------------------------------- */
    async function finish(auto) {
        if (finished) return;
        finished = true;
        $$("video").forEach((v) => v.srcObject?.getTracks().forEach((t) => t.stop()));
        overlay(auto ? "Time is up" : "Submitting", "Saving and submitting your answers…");
        for (let attempt = 0; ; attempt++) {
            try {
                const r = await post(D.urls.submit, { answers: pending, position: current, auto });
                try { localStorage.removeItem(KEY); } catch { /* ignore */ }
                location.replace(r.redirect || D.urls.done);
                return;
            } catch {
                overlay(auto ? "Time is up" : "Submitting", "No connection. Your answers are safe on this device and will be sent automatically when the network returns. Do not close this page.");
                await new Promise((r) => setTimeout(r, Math.min(15000, 3000 + attempt * 1000)));
            }
        }
    }

    const dialogs = {};
    $$("dialog[data-dialog]").forEach((d) => {
        dialogs[d.dataset.dialog] = d;
        $$("[data-close]", d).forEach((b) => b.addEventListener("click", () => d.close()));
    });
    $$("[data-open]").forEach((b) => b.addEventListener("click", () => {
        const d = dialogs[b.dataset.open];
        if (!d) return;
        if (b.dataset.open === "review") buildReview();
        d.showModal();
    }));
    function buildReview() {
        const list = $("[data-review-list]");
        list.innerHTML = "";
        let unanswered = 0, flagged = 0;
        papers.forEach((p) => {
            const qs = Q.filter((q) => q.paper === p.id);
            const done = qs.filter((q) => answers.get(q.n).a).length;
            const f = qs.filter((q) => answers.get(q.n).f).length;
            unanswered += qs.length - done;
            flagged += f;
            const row = document.createElement("div");
            row.className = "flex items-center justify-between rounded-2xl bg-soft px-4 py-3 text-sm";
            row.innerHTML = `<span class="font-semibold text-ink"></span><span class="text-muted"></span>`;
            row.firstChild.textContent = p.subject;
            row.lastChild.textContent = `${done}/${qs.length} answered${f ? ` · ${f} flagged` : ""}`;
            list.appendChild(row);
        });
        $("[data-review-note]").textContent = unanswered
            ? `You have ${unanswered} unanswered question(s)${flagged ? ` and ${flagged} flagged` : ""}. You cannot change anything after submitting.`
            : flagged ? `All answered. ${flagged} question(s) are flagged for review.` : "All questions answered. You cannot change anything after submitting.";
    }
    $("[data-submit-final]").addEventListener("click", () => { dialogs.review.close(); finish(false); });

    /* ---- Calculator (no eval: immediate-execution, like a handheld) -------------------------- */
    if (dialogs.calc) {
        const display = $("[data-calc-display]");
        let acc = null, op = null, entry = "0", fresh = true;
        const show = (v) => { display.textContent = String(Number.isFinite(v) ? Number(v.toPrecision(12)) : "Error"); };
        const apply = (a, b, o) => ({ "+": a + b, "−": a - b, "×": a * b, "÷": b === 0 ? NaN : a / b })[o] ?? b;
        $$("[data-calc-keys] button").forEach((b) => b.addEventListener("click", () => {
            const k = b.dataset.k;
            if (/^[0-9]$/.test(k) || k === ".") {
                if (fresh) { entry = k === "." ? "0." : k; fresh = false; } else if (!(k === "." && entry.includes("."))) entry = entry === "0" && k !== "." ? k : entry + k;
                display.textContent = entry;
            } else if (k === "C") { acc = null; op = null; entry = "0"; fresh = true; display.textContent = "0"; }
            else if (k === "±") { entry = String(-Number(entry)); display.textContent = entry; fresh = false; }
            else if (k === "%") { entry = String(Number(entry) / 100); show(Number(entry)); fresh = false; }
            else if (k === "√") { entry = String(Math.sqrt(Number(entry))); show(Number(entry)); fresh = false; }
            else {
                const value = Number(entry);
                if (op === null) acc = value;
                else if (!fresh) acc = apply(acc, value, op);
                show(acc);
                entry = String(acc);
                op = k === "=" ? null : k;
                fresh = true;
            }
        }));
    }

    /* ---- Exam integrity (recorded for the school; M7 adds face checks) ------------------------ */
    document.addEventListener("visibilitychange", () => { if (document.hidden && !finished) { logEvent("focus_lost", "left the exam tab"); } else if (!finished) { toast("Leaving the exam screen is recorded."); } });
    ["copy", "cut", "paste", "contextmenu", "dragstart", "selectstart"].forEach((t) => document.addEventListener(t, (e) => {
        e.preventDefault();
        if (t === "copy" || t === "cut") logEvent("copy_attempt");
    }));
    addEventListener("beforeprint", () => logEvent("print_attempt"));
    $("[data-fullscreen]")?.addEventListener("click", () => document.documentElement.requestFullscreen?.().catch(() => {}));
    document.addEventListener("fullscreenchange", () => { if (!document.fullscreenElement && !finished) logEvent("fullscreen_exit"); });
    addEventListener("beforeunload", (e) => { if (!finished && Object.keys(pending).length) { e.preventDefault(); e.returnValue = ""; } });
    // Keep the screen awake on phones and tablets during the exam.
    let wake = null;
    const keepAwake = async () => { try { wake = await navigator.wakeLock?.request("screen"); } catch { /* not supported */ } };
    keepAwake();
    document.addEventListener("visibilitychange", () => { if (!document.hidden && (!wake || wake.released)) keepAwake(); });

    /* ---- Warnings, full screen and camera (M7) ---------------------------------------------- */
    let warnings = D.warnings || 0;
    function noteWarnings(n, why = "") {
        if (typeof n !== "number" || n <= warnings) return;
        warnings = n;
        const reason = why ? `: ${why}` : "";
        toast(D.maxWarnings ? `Warning ${n} of ${D.maxWarnings}${reason}. At ${D.maxWarnings} your exam is submitted automatically.` : `Warning recorded${reason}.`);
        navigator.vibrate?.([120, 80, 120]);
    }
    // Blocking gates: the student must fix the problem before continuing.
    const gates = new Map();
    function showGate(id, title, text, button, action) {
        gates.set(id, { title, text, button, action });
        drawGate();
    }
    function hideGate(id) { gates.delete(id); drawGate(); }
    function drawGate() {
        const el = $("[data-gate]");
        const g = [...gates.values()][0];
        el.classList.toggle("hidden", !g || finished);
        if (!g) return;
        $("[data-gate-title]").textContent = g.title;
        $("[data-gate-text]").textContent = g.text;
        const b = $("[data-gate-btn]");
        b.textContent = g.button;
        b.onclick = g.action;
    }
    const fsSupported = !!document.documentElement.requestFullscreen && !matchMedia("(pointer: coarse)").matches;
    function checkFullscreen() {
        if (!D.requireFullscreen || !fsSupported || finished) return;
        if (document.fullscreenElement) hideGate("fs");
        else showGate("fs", "Full screen is required", "This exam must be written in full screen. Leaving full screen is recorded as a warning.", "Enter full screen", () => document.documentElement.requestFullscreen().catch(() => {}));
    }
    document.addEventListener("fullscreenchange", checkFullscreen);
    checkFullscreen();

    const P = window.ExamProctor;
    const pc = D.proctor;
    if (P && pc && pc.mode !== "off") {
        const FLAG_TEXT = { no_face: "no face in view", multiple_faces: "more than one face", face_mismatch: "face does not match" };
        const camBox = $("[data-cam]");
        const video = $("[data-cam] video");
        let ref = null, noFace = 0, mismatch = 0, lastFlagAt = 0, queue = [];
        let nextRoutine = Date.now() + pc.every * 1000 * (0.4 + Math.random() * 0.6);
        const camLost = () => {
            if (finished) return;
            logEvent("camera_off");
            camBox.classList.add("hidden");
            showGate("cam", "Turn your camera back on", "This exam uses your camera to confirm it is you writing. Your time is still running.", "Turn camera on", startCam);
        };
        async function startCam() {
            try {
                await P.camera(video);
                camBox.classList.remove("hidden");
                hideGate("cam");
                video.srcObject.getVideoTracks()[0]?.addEventListener("ended", camLost);
                return true;
            } catch {
                camLost();
                return false;
            }
        }
        async function send(blob, fields) {
            if (!blob) return;
            try {
                const r = await P.upload(pc.snapshotUrl, D.csrf, blob, fields);
                if (r.csrf) D.csrf = r.csrf;
                if (!r.ok && r.status >= 500) throw new Error("server");
                if (r.ok) {
                    noteWarnings(r.data.warnings, fields.flag ? FLAG_TEXT[fields.flag] : "");
                    if (r.data.status && r.data.status !== "in_progress") finish(false);
                }
            } catch {
                queue = [...queue, [blob, fields]].slice(-3); // offline: keep the latest few
            }
        }
        async function watch() {
            if (finished) return;
            try {
                if (!video.srcObject || video.readyState < 2 || document.hidden) return;
                let a = { faces: null, distance: null };
                try { a = await P.analyse(video, ref); } catch { /* face model not loaded: plain snapshots only */ }
                let flag = null;
                noFace = a.faces === 0 ? noFace + 1 : 0;
                if (noFace >= 2) flag = "no_face";
                if (a.faces > 1) flag = "multiple_faces";
                if (pc.mode === "match" && ref && a.distance !== null) {
                    mismatch = a.distance > P.MATCH_BAD ? mismatch + 1 : 0;
                    if (mismatch >= 2) flag = "face_mismatch";
                }
                const now = Date.now();
                if (flag && now - lastFlagAt > 60000) {
                    lastFlagAt = now;
                    noFace = 0;
                    mismatch = 0;
                    await send(await P.capture(video), { faces: a.faces, score: a.distance, flag });
                } else if (now >= nextRoutine) {
                    nextRoutine = now + pc.every * 1000 * (0.7 + Math.random() * 0.6);
                    await send(await P.capture(video), { faces: a.faces, score: a.distance });
                }
                if (queue.length && navigator.onLine) { const [b, f] = queue.shift(); await send(b, f); }
            } catch { /* detection hiccup: try again next round */ } finally {
                setTimeout(watch, 12000);
            }
        }
        (async () => {
            if (!(await startCam())) return;
            try { await P.load(pc); ref = await P.reference(pc).catch(() => null); } catch { /* models failed: snapshots still sent */ }
            setTimeout(watch, 4000);
        })();
    }

    renderTabs();
    render();
    net(navigator.onLine ? "ok" : "offline", navigator.onLine ? "Saved" : "Offline · answers kept on this device");
    if (Object.keys(pending).length) sync();
})();
