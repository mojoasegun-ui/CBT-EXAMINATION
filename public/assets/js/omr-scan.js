/* ExamCore shading-sheet scanner. Runs entirely in the browser: no AI, no data needed to read
 * a sheet. Steps: QR (who/which paper) → four corner squares → perspective map → darkness of
 * every bubble → teacher confirms anything unclear → save (queued offline if needed).
 */
(() => {
    "use strict";
    const cfgEl = document.getElementById("omr-config");
    if (!cfgEl) return;
    const cfg = JSON.parse(cfgEl.textContent);
    const $ = (s) => document.querySelector(s);
    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
    const QUEUE_KEY = `omr-queue-${cfg.exam}`;
    const TEMPLATE_QR = [172, 39]; // QR centre on the sheet (mm): closest to the top-right marker
    let current = null;
    let saved = 0;

    // ---------- helpers ----------
    const status = (text, tone = "muted") => {
        const el = $("[data-omr-status]");
        el.textContent = text;
        el.className = `mt-2 text-sm ${({ ok: "font-semibold text-emerald-600", bad: "font-semibold text-red-600", muted: "text-muted" })[tone]}`;
    };
    const show = (sel, on) => $(sel)?.classList.toggle("hidden", !on);
    const loadJsQR = () => new Promise((resolve, reject) => {
        if (window.jsQR) return resolve(window.jsQR);
        const s = document.createElement("script");
        s.src = cfg.jsqr;
        s.onload = () => resolve(window.jsQR);
        s.onerror = () => reject(new Error("Could not load the QR reader."));
        document.head.appendChild(s);
    });

    // Otsu threshold for a set of gray values.
    function otsu(hist, total) {
        let sum = 0;
        for (let i = 0; i < 256; i++) sum += i * hist[i];
        let sumB = 0, wB = 0, best = 0, t = 100;
        for (let i = 0; i < 256; i++) {
            wB += hist[i];
            if (!wB) continue;
            const wF = total - wB;
            if (!wF) break;
            sumB += i * hist[i];
            const mB = sumB / wB, mF = (sum - sumB) / wF;
            const between = wB * wF * (mB - mF) * (mB - mF);
            if (between > best) { best = between; t = i; }
        }
        return t;
    }

    /** Largest solid, square-ish dark blob nearest to a corner, inside one corner region. */
    function findMarker(gray, W, H, rx, ry, rw, rh, corner, expected) {
        const hist = new Uint32Array(256);
        for (let y = ry; y < ry + rh; y++) for (let x = rx; x < rx + rw; x++) hist[gray[y * W + x]]++;
        const t = Math.min(otsu(hist, rw * rh), 140);
        const seen = new Uint8Array(rw * rh);
        const stack = new Int32Array(rw * rh);
        let best = null;
        for (let y0 = 0; y0 < rh; y0++) {
            for (let x0 = 0; x0 < rw; x0++) {
                const i0 = y0 * rw + x0;
                if (seen[i0] || gray[(ry + y0) * W + rx + x0] > t) continue;
                let sp = 0, area = 0, sx = 0, sy = 0, minX = x0, maxX = x0, minY = y0, maxY = y0;
                stack[sp++] = i0;
                seen[i0] = 1;
                while (sp) {
                    const i = stack[--sp];
                    const x = i % rw, y = (i - x) / rw;
                    area++; sx += x; sy += y;
                    if (x < minX) minX = x; if (x > maxX) maxX = x; if (y < minY) minY = y; if (y > maxY) maxY = y;
                    const nb = [x > 0 ? i - 1 : -1, x < rw - 1 ? i + 1 : -1, y > 0 ? i - rw : -1, y < rh - 1 ? i + rw : -1];
                    for (const j of nb) {
                        if (j >= 0 && !seen[j] && gray[(ry + ((j / rw) | 0)) * W + rx + (j % rw)] <= t) { seen[j] = 1; stack[sp++] = j; }
                    }
                }
                const bw = maxX - minX + 1, bh = maxY - minY + 1;
                const fill = area / (bw * bh), aspect = bw / bh;
                if (area < expected * 0.2 || area > expected * 4 || fill < 0.7 || aspect < 0.55 || aspect > 1.8) continue;
                const cx = rx + sx / area, cy = ry + sy / area;
                const d = Math.hypot(cx - corner[0], cy - corner[1]);
                if (!best || d < best.d) best = { x: cx, y: cy, d, area };
            }
        }
        return best;
    }

    /** 3×3 homography mapping 4 source points to 4 destination points. */
    function homography(src, dst) {
        const A = [], b = [];
        for (let i = 0; i < 4; i++) {
            const [x, y] = src[i], [u, v] = dst[i];
            A.push([x, y, 1, 0, 0, 0, -u * x, -u * y]); b.push(u);
            A.push([0, 0, 0, x, y, 1, -v * x, -v * y]); b.push(v);
        }
        for (let c = 0; c < 8; c++) { // Gaussian elimination with partial pivoting
            let p = c;
            for (let r = c + 1; r < 8; r++) if (Math.abs(A[r][c]) > Math.abs(A[p][c])) p = r;
            [A[c], A[p]] = [A[p], A[c]]; [b[c], b[p]] = [b[p], b[c]];
            for (let r = c + 1; r < 8; r++) {
                const f = A[r][c] / A[c][c];
                for (let k = c; k < 8; k++) A[r][k] -= f * A[c][k];
                b[r] -= f * b[c];
            }
        }
        const h = new Array(8);
        for (let r = 7; r >= 0; r--) {
            let s = b[r];
            for (let k = r + 1; k < 8; k++) s -= A[r][k] * h[k];
            h[r] = s / A[r][r];
        }
        return (x, y) => {
            const w = h[6] * x + h[7] * y + 1;
            return [(h[0] * x + h[1] * y + h[2]) / w, (h[3] * x + h[4] * y + h[5]) / w];
        };
    }

    function darkness(gray, W, H, cx, cy, r) {
        let sum = 0, n = 0;
        const r2 = r * r;
        for (let y = Math.floor(cy - r); y <= Math.ceil(cy + r); y++) {
            if (y < 0 || y >= H) continue;
            for (let x = Math.floor(cx - r); x <= Math.ceil(cx + r); x++) {
                if (x < 0 || x >= W || (x - cx) * (x - cx) + (y - cy) * (y - cy) > r2) continue;
                sum += gray[y * W + x]; n++;
            }
        }
        return n ? 255 - sum / n : 0;
    }

    /** Pick the shaded choice in a row of bubble darkness values. */
    function decide(values, threshold) {
        const sorted = [...values].sort((a, b) => b - a);
        const base = sorted[sorted.length - 1];
        const top = sorted[0], second = sorted[1] ?? base;
        const idx = values.indexOf(top);
        if (top - base < threshold * 0.55) return { pick: null, state: "blank" };
        if (second - base > threshold * 0.7 && top - second < threshold * 0.6) return { pick: idx, state: "multiple" };
        if (top - base < threshold) return { pick: idx, state: "unsure" };
        return { pick: idx, state: "ok" };
    }

    // ---------- reading a sheet ----------
    async function read(file) {
        const jsQR = await loadJsQR();
        const bmp = await createImageBitmap(file, { imageOrientation: "from-image" }).catch(() => createImageBitmap(file));
        const scale = Math.min(1, 1600 / Math.max(bmp.width, bmp.height));
        const W = Math.round(bmp.width * scale), H = Math.round(bmp.height * scale);
        const work = document.createElement("canvas");
        work.width = W; work.height = H;
        const ctx = work.getContext("2d", { willReadFrequently: true });
        ctx.drawImage(bmp, 0, 0, W, H);
        const img = ctx.getImageData(0, 0, W, H);
        const gray = new Uint8Array(W * H);
        for (let i = 0, j = 0; i < gray.length; i++, j += 4) gray[i] = (img.data[j] * 299 + img.data[j + 1] * 587 + img.data[j + 2] * 114) / 1000;

        // 1. QR: which exam, student and paper version.
        let qr = jsQR(img.data, W, H, { inversionAttempts: "dontInvert" });
        if (!qr) {
            const small = document.createElement("canvas");
            const s2 = 800 / Math.max(W, H);
            small.width = Math.round(W * s2); small.height = Math.round(H * s2);
            small.getContext("2d").drawImage(work, 0, 0, small.width, small.height);
            const d2 = small.getContext("2d").getImageData(0, 0, small.width, small.height);
            const q2 = jsQR(d2.data, small.width, small.height);
            if (q2) qr = { data: q2.data, location: Object.fromEntries(Object.entries(q2.location).map(([k, p]) => [k, { x: p.x / s2, y: p.y / s2 }])) };
        }
        if (!qr) throw new Error("Could not read the QR code. Take the photo closer, in good light, without glare.");
        const m = /^EC1-(\d+)-(\d+)-([A-D])-[a-f0-9]{10}$/.exec(qr.data.trim());
        if (!m) throw new Error("This QR code is not an ExamCore answer sheet.");
        if (Number(m[1]) !== cfg.exam) throw new Error("This sheet belongs to a different exam.");
        const layout = cfg.layouts[m[3]];
        if (!layout) throw new Error(`Paper ${m[3]} is not part of this exam any more.`);
        const qc = ["topLeftCorner", "topRightCorner", "bottomRightCorner", "bottomLeftCorner"].map((k) => qr.location[k]);
        const qrCenter = [qc.reduce((s, p) => s + p.x, 0) / 4, qc.reduce((s, p) => s + p.y, 0) / 4];

        // 2. The four corner squares.
        const rw = Math.round(W * 0.34), rh = Math.round(H * 0.34);
        // The sheet's short side (210 mm) usually fills 70–100% of the photo's short side.
        const expectedSide = (layout.markerSize / 210) * Math.min(W, H) * 0.85;
        const expected = Math.max(80, expectedSide * expectedSide);
        const regions = [[0, 0, [0, 0]], [W - rw, 0, [W, 0]], [0, H - rh, [0, H]], [W - rw, H - rh, [W, H]]];
        const found = regions.map(([x, y, c]) => findMarker(gray, W, H, x, y, rw, rh, c, expected));
        if (found.some((f) => !f)) throw new Error("Could not find all four black corner squares. Fit the whole sheet in the photo.");
        // Clockwise in the photo: top-left, top-right, bottom-right, bottom-left.
        const cycle = [found[0], found[1], found[3], found[2]];
        const trIndex = cycle.reduce((bi, p, i) => (Math.hypot(p.x - qrCenter[0], p.y - qrCenter[1]) < Math.hypot(cycle[bi].x - qrCenter[0], cycle[bi].y - qrCenter[1]) ? i : bi), 0);
        const t = layout.markers; // template: TL, TR, BL, BR
        const templateCycle = [t[0], t[1], t[3], t[2]];
        const dst = templateCycle.map((_, i) => cycle[(i - 1 + trIndex + 4) % 4]);
        const map = homography(templateCycle, dst.map((p) => [p.x, p.y]));
        const pxPerMm = Math.hypot(...[0, 1].map((k) => map(...t[1])[k] - map(...t[0])[k])) / (t[1][0] - t[0][0]);
        const r = layout.r * pxPerMm * 0.62;

        // 3. Darkness of every bubble.
        const rows = layout.questions.map((q) => q.letters.map((b) => darkness(gray, W, H, ...map(b.x, b.y), r)));
        const all = rows.flat().sort((a, b) => a - b);
        const empty = all[Math.floor(all.length * 0.25)] ?? 0;
        const marked = all[Math.floor(all.length * 0.97)] ?? 255;
        const threshold = Math.max(28, (marked - empty) * 0.45);
        const answers = layout.questions.map((q, i) => ({ n: q.n, ...decide(rows[i], threshold), letters: q.letters.map((b) => b.l) }));
        let theory = null, theoryUnsure = false;
        if (layout.theory.length) {
            const pickRow = (name) => {
                const cells = layout.theory.filter((b) => b.row === name);
                if (!cells.length) return { pick: 0, state: "ok" };
                return decide(cells.map((b) => darkness(gray, W, H, ...map(b.x, b.y), r)), threshold);
            };
            const tens = pickRow("t"), units = pickRow("u");
            if (tens.pick !== null || units.pick !== null) theory = (tens.pick ?? 0) * 10 + (units.pick ?? 0);
            theoryUnsure = [tens, units].some((x) => x.state !== "ok" && x.state !== "blank");
        }
        return { bmp, W, H, map, r, layout, answers, theory, theoryUnsure, code: qr.data.trim(), student: Number(m[2]), version: m[3], markers: cycle };
    }

    // ---------- UI ----------
    function draw(res) {
        const canvas = $("[data-omr-canvas]");
        canvas.width = res.W; canvas.height = res.H;
        const c = canvas.getContext("2d");
        c.drawImage(res.bmp, 0, 0, res.W, res.H);
        c.lineWidth = Math.max(2, res.r * 0.35);
        res.answers.forEach((a, i) => {
            const q = res.layout.questions[i];
            if (a.pick === null) return;
            const b = q.letters[a.pick];
            const [x, y] = res.map(b.x, b.y);
            c.strokeStyle = a.state === "ok" ? "#10b981" : "#f59e0b";
            c.beginPath(); c.arc(x, y, res.r * 1.6, 0, Math.PI * 2); c.stroke();
        });
        c.fillStyle = "rgba(37,99,235,.85)";
        res.markers.forEach((p) => { c.beginPath(); c.arc(p.x, p.y, res.r * 1.2, 0, Math.PI * 2); c.fill(); });
        show("[data-omr-placeholder]", false);
    }

    function render(res) {
        const student = cfg.students[res.student];
        show("[data-omr-student]", true);
        $("[data-omr-name]").textContent = student ? student.name : `Student #${res.student}`;
        $("[data-omr-meta]").textContent = student ? `${student.adm} · ${student.class} · Paper ${res.version}${student.scanned ? " · already scanned (saving replaces it)" : ""}` : "Not in this exam's classes";
        const unsure = res.answers.filter((a) => a.state === "unsure" || a.state === "multiple");
        const box = $("[data-omr-uncertain]");
        box.innerHTML = "";
        unsure.forEach((a) => {
            const row = document.createElement("div");
            row.className = "flex flex-wrap items-center gap-1.5";
            row.innerHTML = `<span class="w-20 text-sm font-semibold text-ink">Q${a.n}${a.state === "multiple" ? " (2 marks)" : ""}</span>`;
            [...a.letters, "–"].forEach((l, i) => {
                const b = document.createElement("button");
                b.type = "button";
                b.textContent = l === "–" ? "Blank" : l;
                b.className = `btn btn-sm ${a.pick === i && l !== "–" ? "btn-primary" : "btn-secondary"}`;
                b.addEventListener("click", () => {
                    a.pick = l === "–" ? null : i;
                    a.state = "ok";
                    a.fixed = true;
                    row.querySelectorAll("button").forEach((x) => { x.classList.remove("btn-primary"); x.classList.add("btn-secondary"); });
                    b.classList.add("btn-primary"); b.classList.remove("btn-secondary");
                    listAnswers(res);
                });
                row.appendChild(b);
            });
            box.appendChild(row);
        });
        show("[data-omr-review]", unsure.length > 0);
        listAnswers(res);
        if (cfg.theoryMax > 0) {
            show("[data-omr-theory-box]", true);
            const t = $("[data-omr-theory]");
            t.max = cfg.theoryMax;
            t.value = res.theory ?? "";
            t.classList.toggle("input-error", res.theoryUnsure);
        }
        show("[data-omr-save]", !!student);
        show("[data-omr-retry]", true);
        const answered = res.answers.filter((a) => a.pick !== null).length;
        status(unsure.length ? `Read ${answered} answers. Check ${unsure.length} highlighted question(s), then save.` : `Read ${answered} of ${res.answers.length} answers clearly.`, unsure.length ? "muted" : "ok");
    }

    function listAnswers(res) {
        const grid = $("[data-omr-answers]");
        grid.innerHTML = "";
        res.answers.forEach((a) => {
            const s = document.createElement("span");
            s.className = `rounded-lg px-1.5 py-1 text-center ${a.state === "ok" || a.fixed ? "bg-soft text-ink" : "bg-amber-100 text-amber-800"}`;
            s.textContent = `${a.n}:${a.pick === null ? "–" : a.letters[a.pick]}`;
            grid.appendChild(s);
        });
        show("[data-omr-answers]", true);
    }

    function snapshot(res) {
        const max = 1000, k = Math.min(1, max / Math.max(res.W, res.H));
        const c = document.createElement("canvas");
        c.width = Math.round(res.W * k); c.height = Math.round(res.H * k);
        c.getContext("2d").drawImage($("[data-omr-canvas]"), 0, 0, c.width, c.height);
        return c.toDataURL("image/jpeg", 0.55);
    }

    async function post(payload) {
        const res = await fetch(cfg.saveUrl, {
            method: "POST", credentials: "same-origin",
            headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-Token": csrf() },
            body: JSON.stringify(payload),
        });
        const fresh = res.headers.get("X-CSRF-Token");
        if (fresh) document.querySelector('meta[name="csrf-token"]').content = fresh;
        return { status: res.status, data: await res.json().catch(() => ({})) };
    }

    const queue = {
        get() { try { return JSON.parse(localStorage.getItem(QUEUE_KEY) || "[]"); } catch { return []; } },
        set(list) { try { localStorage.setItem(QUEUE_KEY, JSON.stringify(list.slice(-60))); } catch { /* storage full */ } $("[data-omr-queue]").textContent = list.length; },
    };

    function logSaved(text, tone = "") {
        show("[data-omr-log-box]", true);
        const li = document.createElement("li");
        li.className = `px-6 py-3 ${tone}`;
        li.textContent = text;
        $("[data-omr-log]").prepend(li);
    }

    async function flushQueue() {
        const list = queue.get();
        if (!list.length || !navigator.onLine) return;
        const left = [];
        for (const item of list) {
            try {
                const r = await post(item);
                if (r.status === 200) { saved++; logSaved(`✓ ${r.data.student}: ${r.data.score}/${r.data.out_of} (sent when back online)`); }
                else if (r.status >= 500) left.push(item);
                else logSaved(`✗ ${item.label}: ${r.data.error || "not saved"}`, "text-red-600");
            } catch { left.push(item); }
        }
        queue.set(left);
        $("[data-omr-count]").textContent = saved;
    }

    async function save(replace = false) {
        if (!current) return;
        const btn = $("[data-omr-save]");
        btn.disabled = true;
        const answers = {};
        current.answers.forEach((a) => { answers[a.n] = a.pick === null ? null : a.letters[a.pick]; });
        const tv = $("[data-omr-theory]")?.value;
        const student = cfg.students[current.student];
        const payload = { code: current.code, answers, theory: tv === "" || tv === undefined ? null : Number(tv), image: snapshot(current), replace: replace || !!student?.scanned, label: student?.name || "" };
        try {
            const r = await post(payload);
            if (r.status === 200) {
                saved++;
                if (student) student.scanned = true;
                $("[data-omr-count]").textContent = saved;
                logSaved(`✓ ${r.data.student} (${r.data.admission_no}) · Paper ${current.version} · ${r.data.score}/${r.data.out_of} · ${Math.round(r.data.percent)}%`);
                status(`Saved ${r.data.student}: ${r.data.score}/${r.data.out_of}. Snap the next sheet.`, "ok");
                reset(false);
            } else if (r.status === 409 && r.data.already && !replace && confirm(`${r.data.error}\n\nReplace the earlier scan with this one?`)) {
                btn.disabled = false;
                return save(true);
            } else {
                status(r.data.error || "Could not save this sheet.", "bad");
            }
        } catch {
            queue.set([...queue.get(), payload]);
            status("No network: this sheet is kept on the phone and will be sent automatically.", "muted");
            logSaved(`… ${payload.label}: waiting for network`, "text-amber-700");
            reset(false);
        } finally {
            btn.disabled = false;
        }
    }

    function reset(clearStatus = true) {
        current = null;
        ["[data-omr-save]", "[data-omr-retry]", "[data-omr-review]", "[data-omr-answers]", "[data-omr-student]", "[data-omr-theory-box]"].forEach((s) => show(s, false));
        if (clearStatus) status("Waiting for a photo…");
        const input = $("[data-omr-file]");
        if (input) input.value = "";
    }

    $("[data-omr-file]").addEventListener("change", async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        reset();
        status("Reading the sheet…");
        try {
            current = await read(file);
            draw(current);
            render(current);
            window.__omrLast = { student: current.student, version: current.version, theory: current.theory, answers: current.answers.map((a) => (a.pick === null ? null : a.letters[a.pick])), states: current.answers.map((a) => a.state) };
        } catch (err) {
            window.__omrLast = { error: err.message };
            status(err.message, "bad");
            show("[data-omr-retry]", true);
        }
    });
    $("[data-omr-save]").addEventListener("click", () => save());
    $("[data-omr-retry]").addEventListener("click", () => { reset(); $("[data-omr-file]").click(); });
    addEventListener("online", flushQueue);
    queue.set(queue.get());
    flushQueue();
})();
