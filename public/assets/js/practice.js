/* ExamCore Practice Centre.
 * - Small helpers for the practice pages (mock subject picker, "only my mistakes" filter).
 * - The question runner. Every answer is written to this device first (localStorage) and then sent in small
 *   batches, so a dropped connection loses nothing. Learning sessions carry their own answers/explanations,
 *   so they keep working offline once opened; the server always re-marks from its own records.
 */
(() => {
    "use strict";
    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

    /* ---- Practice pages ---------------------------------------------------------------------- */
    const pickForm = $("[data-pick-form]");
    if (pickForm) {
        const boxes = $$("[data-pick]", pickForm);
        const count = $("[data-pick-count]", pickForm);
        const go = $("[data-pick-submit]", pickForm);
        const update = () => {
            const n = boxes.filter((b) => b.checked).length;
            if (count) count.textContent = String(n);
            boxes.forEach((b) => { b.disabled = !b.checked && n >= 3; });
            if (go) go.disabled = n !== 3;
        };
        boxes.forEach((b) => b.addEventListener("change", update));
        window.addEventListener("pageshow", update);
        update();
    }
    $("[data-filter-wrong]")?.addEventListener("change", (e) => {
        $$("[data-item]").forEach((el) => el.classList.toggle("hidden", e.target.checked && el.dataset.ok === "1"));
    });

    /* ---- Runner ---------------------------------------------------------------------------------- */
    const dataEl = document.getElementById("pc-data");
    if (!dataEl) return;
    const D = JSON.parse(dataEl.textContent);
    const Q = D.questions;
    const L = D.learning;
    const LETTERS = ["A", "B", "C", "D", "E"];
    const KEY = `pc-${D.token}`;
    const answers = new Map(Q.map((q) => [q.n, q.mine || null]));
    let pending = {};
    let current = 1;
    let deadline = D.deadline;
    let offset = D.serverNow - Date.now() / 1000;
    let finished = false;
    let syncing = false;
    let failures = 0;
    let syncTimer = null;

    try {
        const saved = JSON.parse(localStorage.getItem(KEY) || "null");
        if (saved) {
            pending = saved.pending || {};
            Object.entries(pending).forEach(([n, v]) => answers.set(Number(n), v));
            current = saved.current || 1;
        }
    } catch { /* storage blocked: the server copy still works */ }
    if (current === 1) {
        const first = Q.find((q) => !answers.get(q.n));
        if (first && [...answers.values()].some(Boolean)) current = first.n; // resume where you stopped
    }
    const persist = () => { try { localStorage.setItem(KEY, JSON.stringify({ pending, current })); } catch { /* ignore */ } };

    const toast = (text) => {
        const el = $("[data-xr-toast]");
        el.textContent = text;
        el.classList.remove("hidden");
        clearTimeout(toast.t);
        toast.t = setTimeout(() => el.classList.add("hidden"), 3000);
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
    const byN = new Map(Q.map((q) => [q.n, q]));
    const done = (n) => !!answers.get(n);

    /* ---- Drawing --------------------------------------------------------------------------------- */
    function render() {
        const q = byN.get(current);
        const mine = answers.get(current);
        $("[data-q-number]").textContent = `Question ${current}`;
        $("[data-q-of]").textContent = `of ${Q.length}`;
        $("[data-q-topic]").textContent = D.mode === "mock" ? q.subject : q.topic;
        $("[data-q-body]").innerHTML = q.html;
        const img = $("[data-q-image]");
        img.classList.toggle("hidden", !q.image);
        if (q.image) img.src = q.image; else img.removeAttribute("src");
        const box = $("[data-options]");
        box.innerHTML = "";
        q.options.forEach((opt, i) => {
            const b = document.createElement("button");
            b.type = "button";
            b.className = "xr-opt";
            b.setAttribute("role", "radio");
            b.setAttribute("aria-checked", String(mine === opt.key));
            b.innerHTML = `<span class="xr-opt-letter">${LETTERS[i]}</span><span class="min-w-0 pt-0.5">${opt.html}</span>`;
            if (L && mine) {
                if (opt.key === q.answer) b.dataset.state = "right";
                else if (opt.key === mine) b.dataset.state = "wrong";
                b.disabled = true;
            }
            b.addEventListener("click", () => choose(opt.key));
            box.appendChild(b);
        });
        const fb = $("[data-feedback]");
        if (fb) {
            fb.classList.toggle("hidden", !(L && mine));
            if (L && mine) {
                const ok = mine === q.answer;
                fb.className = `mt-5 rounded-2xl border p-4 text-sm leading-7 ${ok ? "border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300" : "border-red-200 bg-red-50 text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"}`;
                $("[data-feedback-title]").textContent = ok ? "Correct" : `Not quite. The answer is ${LETTERS[q.options.findIndex((o) => o.key === q.answer)]}.`;
                $("[data-feedback-text]").innerHTML = q.explanation || "";
            }
        }
        $("[data-prev]").disabled = current === 1;
        const last = current === Q.length;
        const next = $("[data-next]");
        next.querySelector("span").textContent = last ? "Finish" : "Next";
        palette();
        $("[data-q-body]").closest("article").scrollIntoView({ block: "nearest" });
    }

    function palette() {
        const box = $("[data-palette]");
        if (!box.childElementCount) {
            Q.forEach((q) => {
                const b = document.createElement("button");
                b.type = "button";
                b.textContent = q.n;
                b.dataset.n = q.n;
                b.setAttribute("aria-label", `Question ${q.n}`);
                b.addEventListener("click", () => { go(q.n); closeSheet(); });
                box.appendChild(b);
            });
        }
        let n = 0;
        $$("button", box).forEach((b) => {
            const num = Number(b.dataset.n);
            const a = answers.get(num);
            if (a) n++;
            const q = byN.get(num);
            b.classList.toggle("is-answered", !!a && (!L || a === q.answer));
            b.classList.toggle("is-wrong", !!a && L && a !== q.answer);
            b.setAttribute("aria-current", String(num === current));
        });
        $("[data-count-answered]").textContent = n;
        $("[data-count-left]").textContent = Q.length - n;
        $("[data-sheet-label]").textContent = `Questions · ${n}/${Q.length}`;
        $("[data-progress]").style.width = `${(n / Q.length) * 100}%`;
    }

    /* ---- Actions --------------------------------------------------------------------------------- */
    function choose(key) {
        if (finished || (L && done(current))) return;
        answers.set(current, key);
        pending[current] = key;
        persist();
        scheduleSync(1500);
        render();
    }
    function go(n) {
        if (!byN.has(n)) return;
        current = n;
        persist();
        render();
    }
    $("[data-next]").addEventListener("click", () => (current === Q.length ? openFinish() : go(current + 1)));
    $("[data-prev]").addEventListener("click", () => go(current - 1));
    $("[data-clear]")?.addEventListener("click", () => {
        answers.set(current, null);
        pending[current] = null;
        persist();
        scheduleSync(1500);
        render();
    });
    const sheet = $("[data-sheet]");
    const closeSheet = () => sheet.classList.remove("is-open");
    $("[data-open-sheet]")?.addEventListener("click", () => sheet.classList.add("is-open"));
    $("[data-close-sheet]")?.addEventListener("click", closeSheet);

    document.addEventListener("keydown", (e) => {
        if (e.ctrlKey || e.metaKey || e.altKey || document.querySelector("dialog[open]")) return;
        const k = e.key.toUpperCase();
        const i = LETTERS.indexOf(k);
        const q = byN.get(current);
        if (i >= 0 && q.options[i]) { e.preventDefault(); choose(q.options[i].key); }
        else if (k === "N" || e.key === "ArrowRight") $("[data-next]").click();
        else if (k === "P" || e.key === "ArrowLeft") go(current - 1);
    });

    /* ---- Saving ---------------------------------------------------------------------------------- */
    function scheduleSync(ms) {
        clearTimeout(syncTimer);
        syncTimer = setTimeout(sync, ms);
    }
    async function post(url, body) {
        const res = await fetch(url, {
            method: "POST", credentials: "same-origin",
            headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-Token": D.csrf },
            body: JSON.stringify(body),
        });
        const fresh = res.headers.get("X-CSRF-Token");
        if (fresh) D.csrf = fresh;
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    }
    async function sync() {
        if (syncing || finished || !Object.keys(pending).length) return;
        syncing = true;
        const batch = pending;
        pending = {};
        net("saving", "Saving…");
        try {
            const r = await post(D.urls.sync, { answers: batch });
            failures = 0;
            offset = r.serverNow - Date.now() / 1000;
            if (r.deadline) deadline = r.deadline;
            net("ok", "Saved");
            persist();
            if (r.status === "submitted") { finished = true; location.replace(D.urls.result); return; }
        } catch {
            pending = { ...batch, ...pending };
            persist();
            failures++;
            net("offline", "Offline · kept on this device");
        } finally {
            syncing = false;
        }
        if (Object.keys(pending).length) scheduleSync(Math.min(30000, 2000 * 2 ** Math.min(failures, 4)));
    }
    addEventListener("online", () => { toast("Back online. Saving…"); sync(); });
    addEventListener("offline", () => net("offline", "Offline · kept on this device"));

    /* ---- Clock (mock exams) ------------------------------------------------------------------------- */
    if (deadline) {
        const warned = new Set();
        const tick = () => {
            const left = Math.max(0, Math.round(deadline - (Date.now() / 1000 + offset)));
            const h = Math.floor(left / 3600), m = Math.floor((left % 3600) / 60), s = left % 60;
            $("[data-timer-text]").textContent = `${h ? `${h}:` : ""}${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}`;
            const t = $("[data-timer]");
            t.classList.toggle("is-warn", left <= 300 && left > 60);
            t.classList.toggle("is-danger", left <= 60);
            [[300, "5 minutes left."], [60, "1 minute left."]].forEach(([at, msg]) => { if (left <= at && left > at - 5 && !warned.has(at)) { warned.add(at); toast(msg); navigator.vibrate?.(200); } });
            if (left === 0 && !finished) submit(true);
        };
        tick();
        setInterval(tick, 1000);
    }

    /* ---- Finishing ----------------------------------------------------------------------------------- */
    const dialog = $('dialog[data-dialog="finish"]');
    $$("[data-close]", dialog).forEach((b) => b.addEventListener("click", () => dialog.close()));
    function openFinish() {
        const left = Q.filter((q) => !done(q.n)).length;
        $("[data-finish-title]").textContent = left ? `${left} question${left === 1 ? "" : "s"} not answered` : "All questions answered";
        $("[data-finish-note]").textContent = L
            ? (left ? "Unanswered questions count as wrong, but you can still come back and practise them." : "Nice. Let’s see how you did and why each answer is right.")
            : (left ? "You cannot change anything after submitting. Unanswered questions score zero." : "You cannot change anything after submitting.");
        dialog.showModal();
    }
    $("[data-finish]").addEventListener("click", openFinish);
    $("[data-finish-final]").addEventListener("click", () => { dialog.close(); submit(false); });

    async function submit(auto) {
        if (finished) return;
        finished = true;
        overlay(auto ? "Time is up" : "Marking your answers", "Saving and marking…");
        const all = {};
        answers.forEach((v, k) => { if (v) all[k] = v; });
        for (let attempt = 0; ; attempt++) {
            try {
                const r = await post(D.urls.submit, { answers: all });
                try { localStorage.removeItem(KEY); } catch { /* ignore */ }
                location.replace(r.redirect || D.urls.result);
                return;
            } catch {
                overlay(auto ? "Time is up" : "Marking your answers", "No connection. Your answers are safe on this device and will be sent when the network returns. Keep this page open.");
                await new Promise((res) => setTimeout(res, Math.min(15000, 3000 + attempt * 1000)));
            }
        }
    }

    render();
    net(navigator.onLine ? "ok" : "offline", navigator.onLine ? "Saved" : "Offline · kept on this device");
    if (Object.keys(pending).length) sync();
})();
