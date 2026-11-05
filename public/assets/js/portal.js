"use strict";

/* ExamCore portal behaviour. Progressive enhancement: every page works without this file. */
(() => {
    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

    /* ---- Theme -------------------------------------------------------------------- */
    $$("[data-theme-toggle]").forEach((button) => {
        button.addEventListener("click", () => {
            const next = document.documentElement.dataset.theme === "dark" ? "light" : "dark";
            document.documentElement.dataset.theme = next;
            try { localStorage.setItem("examcore-theme", next); } catch { /* private mode */ }
        });
    });

    /* ---- Sidebar drawer (mobile) ---------------------------------------------------- */
    const sidebar = $("[data-sidebar]");
    const backdrop = $("[data-sidebar-backdrop]");
    const opener = $("[data-sidebar-open]");
    const setSidebar = (open) => {
        if (!sidebar) return;
        sidebar.classList.toggle("-translate-x-full", !open);
        if (backdrop) backdrop.hidden = !open;
        opener?.setAttribute("aria-expanded", String(open));
        document.body.classList.toggle("overflow-hidden", open);
        if (open) $("a, button", sidebar)?.focus(); else if (window.innerWidth < 1024) opener?.focus();
    };
    opener?.addEventListener("click", () => setSidebar(true));
    $("[data-sidebar-close]")?.addEventListener("click", () => setSidebar(false));
    backdrop?.addEventListener("click", () => setSidebar(false));
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && backdrop && !backdrop.hidden) setSidebar(false);
    });

    /* ---- Toasts ----------------------------------------------------------------------- */
    const toastRegion = $("[data-toasts]");
    const dismiss = (toast) => {
        toast.style.transition = "opacity .2s, transform .2s";
        toast.style.opacity = "0";
        toast.style.transform = "translateY(-6px)";
        window.setTimeout(() => toast.remove(), 200);
    };
    const wireToast = (toast) => {
        $("[data-toast-close]", toast)?.addEventListener("click", () => dismiss(toast));
        if (toast.getAttribute("role") !== "alert") window.setTimeout(() => dismiss(toast), 6000);
    };
    $$("[data-toast]").forEach(wireToast);
    const toast = (message) => {
        if (!toastRegion) return;
        const node = document.createElement("div");
        node.className = "pointer-events-auto flex w-full max-w-md animate-toast-in items-center gap-3 rounded-2xl border border-line bg-card p-4 text-sm font-medium text-ink shadow-lift";
        node.setAttribute("role", "status");
        node.setAttribute("data-toast", "");
        node.textContent = message;
        toastRegion.append(node);
        wireToast(node);
    };

    /* ---- Confirm dialogs (forms with data-confirm) ------------------------------------ */
    const dialog = $("[data-confirm-dialog]");
    $$("form[data-confirm]").forEach((form) => {
        form.addEventListener("submit", (event) => {
            if (form.dataset.confirmed === "1" || !dialog || typeof dialog.showModal !== "function") return;
            event.preventDefault();
            event.stopImmediatePropagation();
            $("[data-confirm-title]", dialog).textContent = form.dataset.confirmTitle || "Are you sure?";
            $("[data-confirm-message]", dialog).textContent = form.dataset.confirm || "";
            const ok = $("[data-confirm-ok]", dialog);
            ok.textContent = form.dataset.confirmOk || "Confirm";
            const submitter = event.submitter;
            const close = () => dialog.close();
            const proceed = () => {
                dialog.close();
                form.dataset.confirmed = "1";
                form.requestSubmit(submitter && form.contains(submitter) ? submitter : undefined);
            };
            ok.onclick = proceed;
            $("[data-confirm-cancel]", dialog).onclick = close;
            dialog.showModal();
            $("[data-confirm-cancel]", dialog).focus();
        });
    });
    dialog?.addEventListener("click", (event) => { if (event.target === dialog) dialog.close(); });

    /* ---- Submit once: no double-clicks, clear "working" state ---------------------------- */
    $$("form[data-submit-once]").forEach((form) => {
        form.addEventListener("submit", (event) => {
            if (event.defaultPrevented) return;
            if (form.dataset.submitting === "1") {
                event.preventDefault();
                return;
            }
            form.dataset.submitting = "1";
            // Disabled buttons are left out of the form data, so keep the tapped button's name/value (e.g. which topic).
            const tapped = event.submitter;
            if (tapped && tapped.name && !form.querySelector(`input[type="hidden"][data-submitter]`)) {
                const keep = document.createElement("input");
                keep.type = "hidden";
                keep.name = tapped.name;
                keep.value = tapped.value;
                keep.setAttribute("data-submitter", "");
                form.appendChild(keep);
            }
            $$('button[type="submit"], button:not([type])', form).forEach((button) => {
                button.disabled = true;
                button.setAttribute("aria-busy", "true");
                const label = $("[data-label]", button);
                if (label) {
                    label.dataset.original = label.textContent;
                    label.textContent = "Working…";
                }
            });
        });
    });
    // Restore buttons when the page comes back from the back/forward cache.
    window.addEventListener("pageshow", () => {
        $$("form[data-submit-once]").forEach((form) => {
            delete form.dataset.submitting;
            delete form.dataset.confirmed;
            $$("button[aria-busy]", form).forEach((button) => {
                button.disabled = false;
                button.removeAttribute("aria-busy");
                const label = $("[data-label]", button);
                if (label?.dataset.original) label.textContent = label.dataset.original;
            });
        });
    });

    /* ---- Copy / download -------------------------------------------------------------- */
    const copyText = async (text) => {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch {
            const area = document.createElement("textarea");
            area.value = text;
            area.setAttribute("readonly", "");
            area.style.position = "fixed";
            area.style.opacity = "0";
            document.body.append(area);
            area.select();
            const ok = document.execCommand("copy");
            area.remove();
            return ok;
        }
    };
    $$("[data-copy]").forEach((button) => {
        button.addEventListener("click", async () => {
            toast((await copyText(button.dataset.copy || "")) ? "Copied to clipboard." : "Copy failed. Select the text and copy it manually.");
        });
    });
    $$("[data-download-text]").forEach((button) => {
        button.addEventListener("click", () => {
            const blob = new Blob([button.dataset.downloadText || ""], { type: "text/plain" });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = button.dataset.filename || "download.txt";
            link.click();
            window.setTimeout(() => URL.revokeObjectURL(link.href), 1000);
        });
    });

    /* ---- Show/hide password ------------------------------------------------------------- */
    $$("[data-toggle-password]").forEach((button) => {
        const input = document.getElementById(button.dataset.togglePassword || "");
        button.addEventListener("click", () => {
            if (!input) return;
            const show = input.type === "password";
            input.type = show ? "text" : "password";
            button.setAttribute("aria-label", show ? "Hide password" : "Show password");
        });
    });

    /* ---- Slug from name --------------------------------------------------------------------- */
    const slugSource = $("[data-slug-source]");
    const slugTarget = $("[data-slug-target]");
    if (slugSource && slugTarget) {
        let touched = slugTarget.value.trim() !== "";
        const slugify = (value) => value.normalize("NFKD").replace(/[̀-ͯ]/g, "").toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "").slice(0, 60);
        slugTarget.addEventListener("input", () => { touched = slugTarget.value.trim() !== ""; slugTarget.value = slugTarget.value.toLowerCase().replace(/[^a-z0-9-]/g, ""); });
        slugSource.addEventListener("input", () => { if (!touched) slugTarget.value = slugify(slugSource.value); });
    }

    /* ---- Brand colour picker + live preview ---------------------------------------------- */
    const readableInk = (hex) => {
        const c = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16) / 255).map((v) => (v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4));
        const l = 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
        return 1.05 / (l + 0.05) >= (l + 0.05) / 0.05 ? "#ffffff" : "#0f172a";
    };
    $$("form").forEach((form) => {
        const picker = $("[data-color-picker]", form);
        const text = $("[data-color-text]", form);
        const sample = $("[data-brand-sample]", form);
        if (!picker || !text) return;
        const apply = (hex) => {
            if (!/^#[0-9a-f]{6}$/i.test(hex)) return;
            picker.value = hex;
            text.value = hex.toUpperCase();
            if (sample) {
                sample.style.background = hex;
                sample.style.color = readableInk(hex);
            }
        };
        picker.addEventListener("input", () => apply(picker.value));
        text.addEventListener("input", () => apply(text.value.startsWith("#") ? text.value : "#" + text.value));
        $$("[data-swatch]", form).forEach((swatch) => swatch.addEventListener("click", () => apply(swatch.dataset.swatch)));
        apply(text.value);
    });

    /* ---- Logo preview ------------------------------------------------------------------------ */
    $$("[data-logo-input]").forEach((input) => {
        const form = input.closest("form");
        input.addEventListener("change", () => {
            const file = input.files?.[0];
            if (!file || !form) return;
            const preview = $("[data-logo-preview]", form);
            const name = $("[data-logo-name]", form);
            if (name) name.textContent = `${file.name} · ${(file.size / 1024).toFixed(0)} KB`;
            if (file.size > 1048576 && name) name.textContent += " (too large, max 1 MB)";
            if (preview && file.type.startsWith("image/")) {
                const img = document.createElement("img");
                img.src = URL.createObjectURL(file);
                img.alt = "New logo preview";
                img.className = "h-full w-full object-contain p-1";
                preview.replaceChildren(img);
            }
        });
    });

    /* ---- Chart tooltips -------------------------------------------------------------------- */
    $$("[data-chart]").forEach((chart) => {
        const tip = $("[data-chart-tip]", chart);
        if (!tip) return;
        const show = (bar) => {
            const box = bar.getBoundingClientRect();
            const host = chart.getBoundingClientRect();
            const fill = bar.firstElementChild?.getBoundingClientRect() ?? box;
            tip.textContent = bar.dataset.tip || "";
            tip.style.left = `${box.left - host.left + box.width / 2}px`;
            tip.style.top = `${fill.top - host.top - 8}px`;
            tip.classList.remove("hidden");
        };
        const hide = () => tip.classList.add("hidden");
        $$("[data-tip]", chart).forEach((bar) => {
            bar.addEventListener("pointerenter", () => show(bar));
            bar.addEventListener("focus", () => show(bar));
            bar.addEventListener("pointerleave", hide);
            bar.addEventListener("blur", hide);
        });
    });

    /* ---- Motion ------------------------------------------------------------------------------ */
    const calm = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    // Staggered entrance for direct children of [data-stagger].
    $$("[data-stagger]").forEach((group) => {
        [...group.children].forEach((child, index) => {
            if (child.tagName === "STYLE" || child.tagName === "SCRIPT") return;
            child.classList.add("reveal");
            child.style.setProperty("--d", `${Math.min(index, 8) * 70}ms`);
        });
    });
    const revealAll = () => $$(".reveal").forEach((node) => node.classList.add("is-in"));
    if (calm || !("IntersectionObserver" in window)) {
        revealAll();
    } else {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-in");
                    observer.unobserve(entry.target);
                }
            });
        }, { rootMargin: "0px 0px -6% 0px" });
        $$(".reveal").forEach((node) => observer.observe(node));
        window.setTimeout(revealAll, 1800); // Never leave content hidden.
    }

    // Count-up numbers.
    const format = new Intl.NumberFormat("en-NG");
    $$("[data-count]").forEach((node) => {
        const target = Number(node.dataset.count || 0);
        if (calm || !Number.isFinite(target) || target === 0) return;
        const start = performance.now();
        const duration = Math.min(1400, 600 + target * 4);
        node.textContent = "0";
        const tick = (now) => {
            const t = Math.min(1, (now - start) / duration);
            node.textContent = format.format(Math.round(target * (1 - (1 - t) ** 3)));
            if (t < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    });

    // Showcase slider (sign-in pages).
    $$("[data-slider]").forEach((slider) => {
        const arts = $$("[data-slide-art]", slider);
        const texts = $$("[data-slide-text]", slider);
        const dots = $$("[data-slide-dot]", slider);
        if (arts.length < 2) return;
        const interval = 5500;
        let current = 0;
        let timer = 0;
        let started = 0;
        let paused = false;
        let elapsed = 0;
        const hide = ["pointer-events-none", "translate-y-6", "scale-95", "opacity-0"];
        const hideText = ["pointer-events-none", "translate-y-3", "opacity-0"];
        const show = (next) => {
            current = (next + arts.length) % arts.length;
            arts.forEach((art, i) => { art.classList.toggle("opacity-100", i === current); hide.forEach((c) => art.classList.toggle(c, i !== current)); });
            texts.forEach((text, i) => {
                text.classList.toggle("opacity-100", i === current);
                hideText.forEach((c) => text.classList.toggle(c, i !== current));
                text.setAttribute("aria-hidden", String(i !== current));
            });
            dots.forEach((dot, i) => {
                dot.classList.toggle("w-10", i === current);
                dot.classList.toggle("w-4", i !== current);
                dot.setAttribute("aria-selected", String(i === current));
                const bar = $("[data-slide-progress]", dot);
                if (bar) { bar.style.transition = "none"; bar.style.width = "0"; }
            });
            restart();
        };
        const restart = () => {
            window.clearTimeout(timer);
            if (calm) return;
            elapsed = 0;
            run();
        };
        const run = () => {
            started = performance.now();
            const bar = $("[data-slide-progress]", dots[current]);
            const remaining = interval - elapsed;
            if (bar) {
                requestAnimationFrame(() => {
                    bar.style.transition = `width ${remaining}ms linear`;
                    bar.style.width = "100%";
                });
            }
            timer = window.setTimeout(() => show(current + 1), remaining);
        };
        const pause = () => {
            if (paused || calm) return;
            paused = true;
            window.clearTimeout(timer);
            elapsed += performance.now() - started;
            const bar = $("[data-slide-progress]", dots[current]);
            if (bar) {
                bar.style.width = getComputedStyle(bar).width;
                bar.style.transition = "none";
            }
        };
        const resume = () => {
            if (!paused) return;
            paused = false;
            run();
        };
        $("[data-slide-next]", slider)?.addEventListener("click", () => show(current + 1));
        $("[data-slide-prev]", slider)?.addEventListener("click", () => show(current - 1));
        dots.forEach((dot, i) => dot.addEventListener("click", () => show(i)));
        slider.addEventListener("pointerenter", pause);
        slider.addEventListener("pointerleave", resume);
        slider.addEventListener("focusin", pause);
        slider.addEventListener("focusout", resume);
        slider.addEventListener("keydown", (event) => {
            if (event.key === "ArrowRight") show(current + 1);
            if (event.key === "ArrowLeft") show(current - 1);
        });
        document.addEventListener("visibilitychange", () => (document.hidden ? pause() : resume()));
        restart();
    });

    /* ---- Network helper: every request has a deadline, so nothing waits forever ------------- */
    const csrfToken = () => $('meta[name="csrf-token"]')?.content || "";
    const request = async (url, options = {}, timeoutMs = 30000) => {
        const controller = new AbortController();
        const timer = window.setTimeout(() => controller.abort(), timeoutMs);
        try {
            return await fetch(url, { credentials: "same-origin", ...options, signal: controller.signal });
        } finally {
            window.clearTimeout(timer);
        }
    };

    /* ---- Dropdown menus ---------------------------------------------------------------------- */
    $$("[data-menu]").forEach((menu) => {
        const button = $("[data-menu-button]", menu);
        const panel = $("[data-menu-panel]", menu);
        const close = () => { panel.classList.add("hidden"); button.setAttribute("aria-expanded", "false"); };
        button?.addEventListener("click", (event) => {
            event.stopPropagation();
            const open = panel.classList.toggle("hidden") === false;
            button.setAttribute("aria-expanded", String(open));
        });
        document.addEventListener("click", (event) => { if (!menu.contains(event.target)) close(); });
        menu.addEventListener("keydown", (event) => { if (event.key === "Escape") { close(); button.focus(); } });
    });

    /* ---- Filters submit as soon as a dropdown changes ---------------------------------------------- */
    $$("form[data-auto-filter] select").forEach((select) => select.addEventListener("change", () => select.form.requestSubmit()));

    /* ---- Photo: upload as soon as one is chosen ----------------------------------------------------- */
    $$("form[data-auto-upload] input[type=file]").forEach((input) => input.addEventListener("change", () => {
        if (input.files?.length) {
            toast("Uploading photo…");
            input.form.submit();
        }
    }));

    /* ---- Drop zone ------------------------------------------------------------------------------------- */
    $$("[data-dropzone]").forEach((zone) => {
        const input = $("[data-dropzone-input]", zone);
        const label = $("[data-dropzone-label]", zone);
        const show = () => { if (input.files?.[0] && label) label.textContent = `${input.files[0].name} · ${(input.files[0].size / 1024).toFixed(0)} KB`; };
        input?.addEventListener("change", show);
        ["dragenter", "dragover"].forEach((type) => zone.addEventListener(type, (event) => { event.preventDefault(); zone.classList.add("border-brand", "bg-brand/[.04]"); }));
        ["dragleave", "drop"].forEach((type) => zone.addEventListener(type, () => zone.classList.remove("border-brand", "bg-brand/[.04]")));
        zone.addEventListener("drop", (event) => {
            event.preventDefault();
            if (event.dataTransfer?.files?.length && input) {
                input.files = event.dataTransfer.files;
                show();
            }
        });
    });

    /* ---- Chunked student import with live progress ------------------------------------------------- */
    $$("form[data-import-start]").forEach((form) => {
        const panel = $("[data-import-progress]");
        const bar = $("[data-import-bar]");
        const label = $("[data-import-label]");
        const count = $("[data-import-count]");
        form.addEventListener("submit", async (event) => {
            event.preventDefault();
            const button = $("button", form);
            button.disabled = true;
            panel?.classList.remove("hidden");
            let failures = 0;
            for (;;) {
                try {
                    const response = await request(form.action, {
                        method: "POST",
                        headers: { Accept: "application/json", "X-CSRF-Token": csrfToken(), "Content-Type": "application/x-www-form-urlencoded" },
                        body: new URLSearchParams(new FormData(form)).toString(),
                    }, 90000);
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.error || "Import stopped.");
                    failures = 0;
                    const pct = data.total ? Math.floor((data.processed / data.total) * 100) : 100;
                    if (bar) bar.style.width = `${pct}%`;
                    if (label) label.textContent = data.status === "completed" ? "Finished" : `Importing… ${pct}%`;
                    if (count) count.textContent = `${data.processed.toLocaleString()} / ${data.total.toLocaleString()} · ${data.created.toLocaleString()} added`;
                    if (data.status === "completed") {
                        window.location.assign(data.done_url);
                        return;
                    }
                } catch (error) {
                    failures += 1;
                    if (failures >= 6) {
                        if (label) label.textContent = "Connection lost. Click Start import again to continue where it stopped.";
                        button.disabled = false;
                        return;
                    }
                    if (label) label.textContent = `Connection slow, retrying (${failures})…`;
                    await new Promise((resolve) => window.setTimeout(resolve, 1500 * failures));
                }
            }
        });
    });
    // Without JS each chunk is a page load; with JS keep going automatically.
    $$("form[data-auto-continue]").forEach((form) => window.setTimeout(() => form.submit(), 400));

    /* ---- Quick search (typeahead) ------------------------------------------------------------------------ */
    $$("[data-quick-search]").forEach((form) => {
        const input = $("[data-quick-search-input]", form);
        const results = $("[data-quick-search-results]", form);
        let timer = 0;
        let inflight = null;
        const render = (data) => {
            const groups = [["Students", data.students || []], ["Staff", data.staff || []]].filter(([, list]) => list.length);
            results.replaceChildren();
            if (!groups.length) {
                const empty = document.createElement("p");
                empty.className = "px-5 py-4 text-sm text-muted";
                empty.textContent = "No matches. Press Enter to search everything.";
                results.append(empty);
            }
            groups.forEach(([title, list]) => {
                const heading = document.createElement("p");
                heading.className = "eyebrow px-5 pb-1 pt-4";
                heading.textContent = title;
                results.append(heading);
                list.slice(0, 5).forEach((item) => {
                    const link = document.createElement("a");
                    link.href = item.url;
                    link.className = "mx-2 block rounded-2xl px-3 py-2 hover:bg-soft focus:bg-soft focus:outline-none";
                    const name = document.createElement("span");
                    name.className = "block truncate text-sm font-semibold text-ink";
                    name.textContent = item.name;
                    const meta = document.createElement("span");
                    meta.className = "block truncate text-xs text-muted";
                    meta.textContent = item.meta;
                    link.append(name, meta);
                    results.append(link);
                });
            });
            const pad = document.createElement("div");
            pad.className = "h-2";
            results.append(pad);
            results.classList.remove("hidden");
        };
        input?.addEventListener("input", () => {
            window.clearTimeout(timer);
            const q = input.value.trim();
            if (q.length < 2) { results.classList.add("hidden"); return; }
            timer = window.setTimeout(async () => {
                inflight?.abort();
                inflight = new AbortController();
                try {
                    const response = await fetch(`${form.action}?q=${encodeURIComponent(q)}`, { headers: { Accept: "application/json" }, credentials: "same-origin", signal: inflight.signal });
                    if (response.ok) render(await response.json());
                } catch { /* aborted or offline */ }
            }, 220);
        });
        document.addEventListener("click", (event) => { if (!form.contains(event.target)) results.classList.add("hidden"); });
        input?.addEventListener("keydown", (event) => { if (event.key === "Escape") results.classList.add("hidden"); });
    });

    /* ---- Form drafts: typing survives a dropped connection or an accidental refresh ----------------- */
    $$("form[data-draft]").forEach((form) => {
        const key = `examcore-draft:${location.pathname}:${form.dataset.draft}`;
        const status = $("[data-draft-status]", form);
        const skip = (el) => !el.name || el.type === "password" || el.type === "file" || el.name.startsWith("_");
        try {
            const saved = JSON.parse(localStorage.getItem(key) || "null");
            if (saved && typeof saved === "object") {
                Object.entries(saved).forEach(([name, value]) => {
                    const el = form.elements.namedItem(name);
                    if (el && !skip(el) && !el.value) el.value = value;
                });
                if (status) status.textContent = "Restored your unsaved draft.";
            }
        } catch { /* storage unavailable */ }
        let timer = 0;
        form.addEventListener("input", () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => {
                const data = {};
                [...form.elements].forEach((el) => { if (!skip(el) && el.value) data[el.name] = el.value; });
                try { localStorage.setItem(key, JSON.stringify(data)); if (status) status.textContent = "Draft saved on this device."; } catch { /* full or private */ }
            }, 400);
        });
        form.addEventListener("submit", () => { if (navigator.onLine) { try { localStorage.removeItem(key); } catch { /* ignore */ } } });
    });

    /* ---- Offline guard: never submit into the void ------------------------------------------------- */
    document.addEventListener("submit", (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || (form.method || "get").toLowerCase() !== "post" || navigator.onLine) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        toast("You are offline. Your entries are kept on this page; submit again when your connection is back.");
    }, true);

    /* ---- Question live preview (mirrors the server's rich_text()) ------------------------------ */
    const richText = (text) => {
        const esc = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
        return esc
            .replace(/\^\{([^}]{1,20})\}/g, "<sup>$1</sup>").replace(/\^(-?[\p{L}\p{N}.]{1,6})/gu, "<sup>$1</sup>")
            .replace(/(?<=[\p{L}\p{N})\]])_\{([^}]{1,20})\}/gu, "<sub>$1</sub>").replace(/(?<=[\p{L})\]])_(\d{1,3})/gu, "<sub>$1</sub>")
            .replace(/\*\*(.+?)\*\*/g, "<strong>$1</strong>").replace(/\n/g, "<br>");
    };
    $$("form[data-question-form] [data-preview-source]").forEach((input) => {
        const key = input.dataset.previewSource;
        const target = $(`[data-preview="${key}"]`);
        const row = $(`[data-preview-row="${key}"]`);
        input.addEventListener("input", () => {
            if (target) target.innerHTML = input.value.trim() ? richText(input.value) : (key === "body" ? '<span class="text-subtle">Your question appears here…</span>' : "");
            row?.classList.toggle("hidden", input.value.trim() === "");
        });
    });
    $$("[data-auto-submit]").forEach((el) => el.addEventListener("change", () => el.form?.requestSubmit()));
    $$("[data-submit-on-change]").forEach((el) => el.addEventListener("change", () => el.form?.requestSubmit()));

    /* ---- Password strength (guidance only; the server enforces the rules) ------------------ */
    $$("[data-strength]").forEach((input) => {
        const form = input.closest("form");
        const bars = form ? $$("[data-strength-bars] span", form) : [];
        const label = form ? $("[data-strength-label]", form) : null;
        const colours = ["bg-red-500", "bg-amber-500", "bg-lime-500", "bg-emerald-500"];
        input.addEventListener("input", () => {
            const value = input.value;
            let score = 0;
            if (value.length >= 8) score++;
            if (value.length >= 12) score++;
            if (/[a-z]/i.test(value) && /\d/.test(value)) score++;
            if (/[^a-z0-9]/i.test(value) || value.length >= 16) score++;
            bars.forEach((bar, index) => {
                bar.classList.remove("bg-ink/10", ...colours);
                bar.classList.add(index < score ? colours[Math.max(0, score - 1)] : "bg-ink/10");
            });
            if (label) label.textContent = value === "" ? "" : ["Too weak", "Weak", "Good", "Strong", "Very strong"][score];
        });
    });
})();

/* ---- Exam lobby: countdown to opening, device check; result bars ----------------------- */
(() => {
    const $ = (s, r = document) => r.querySelector(s);
    document.querySelectorAll("[data-width]").forEach((el) => requestAnimationFrame(() => { el.style.width = `${Math.min(100, Number(el.dataset.width) || 0)}%`; }));
    const cd = $("[data-countdown-to]");
    if (cd) {
        const target = Number(cd.dataset.countdownTo);
        const tick = () => {
            const left = Math.max(0, target - Math.floor(Date.now() / 1000));
            if (left === 0) { location.reload(); return; }
            const d = Math.floor(left / 86400), h = Math.floor((left % 86400) / 3600), m = Math.floor((left % 3600) / 60), s = left % 60;
            cd.textContent = d ? `${d}d ${h}h ${m}m` : `${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}`;
        };
        tick();
        setInterval(tick, 1000);
    }
    const box = $("[data-device-check]");
    if (!box) return;
    const set = (k, text, ok) => { const el = $(`[data-check="${k}"]`, box); if (el) { el.textContent = text; el.classList.toggle("text-red-600", !ok); el.classList.toggle("text-emerald-600", ok); } };
    const netCheck = () => set("net", navigator.onLine ? "Online" : "Offline", navigator.onLine);
    netCheck();
    addEventListener("online", netCheck);
    addEventListener("offline", netCheck);
    set("screen", innerWidth < 360 ? "Very small — rotate or use a bigger screen" : `${innerWidth}×${innerHeight} OK`, innerWidth >= 360);
    try { localStorage.setItem("xr-test", "1"); localStorage.removeItem("xr-test"); set("storage", "Available", true); } catch { set("storage", "Blocked — answers save online only", false); }
    navigator.getBattery?.().then((b) => {
        const pc = Math.round(b.level * 100);
        set("battery", `${pc}%${b.charging ? " · charging" : ""}`, b.charging || pc >= 30);
    }).catch(() => {});
})();
