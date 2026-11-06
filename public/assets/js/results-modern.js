"use strict";

/* Result page: animated score ring + count-up, and the question filter (wrong ones first). */
(() => {
    const calm = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    // Ring and number
    const ring = document.querySelector("[data-ring]");
    if (ring) {
        const to = ring.dataset.ring;
        if (calm) ring.setAttribute("stroke-dashoffset", to);
        else requestAnimationFrame(() => { ring.style.transition = "stroke-dashoffset 1.2s cubic-bezier(.2,.8,.2,1)"; ring.setAttribute("stroke-dashoffset", to); });
    }
    const num = document.querySelector("[data-count]");
    if (num && !calm) {
        const end = Number(num.dataset.count), t0 = performance.now();
        const tick = (now) => {
            const k = Math.min(1, (now - t0) / 1100);
            num.textContent = String(Math.round(end * (1 - Math.pow(1 - k, 3))));
            if (k < 1) requestAnimationFrame(tick);
        };
        num.textContent = "0";
        requestAnimationFrame(tick);
    }

    // Filter tabs
    const root = document.querySelector("[data-review]");
    if (!root) return;
    const tabs = [...root.querySelectorAll("[data-tab]")];
    const cards = [...root.querySelectorAll(".rm-q")];
    const empty = root.querySelector("[data-empty]");
    const show = (name) => {
        let shown = 0;
        cards.forEach((card) => {
            const state = card.dataset.state;
            const visible = name === "all" || (name === "correct" && state === "correct") || (name === "review" && state !== "correct");
            card.classList.toggle("hidden", !visible);
            if (visible) shown++;
        });
        tabs.forEach((tab) => tab.setAttribute("aria-selected", String(tab.dataset.tab === name)));
        empty?.classList.toggle("hidden", shown !== 0);
    };
    tabs.forEach((tab) => tab.addEventListener("click", () => show(tab.dataset.tab)));
    show(tabs.find((t) => t.getAttribute("aria-selected") === "true")?.dataset.tab || "all");
})();
