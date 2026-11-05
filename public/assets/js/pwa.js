"use strict";

/* Shared PWA glue for every page: service worker, install button, connection banner. */
(() => {
    const script = document.currentScript;
    const base = script ? script.src.replace(/\/assets\/js\/pwa\.js.*$/, "/") : "/";

    // ---- Service worker ---------------------------------------------------------------------
    if ("serviceWorker" in navigator && (location.protocol === "https:" || location.hostname === "localhost" || location.hostname === "127.0.0.1")) {
        window.addEventListener("load", () => {
            navigator.serviceWorker.register(`${base}sw.js`, { scope: base }).then((registration) => {
                // A new version was deployed: activate it at the next page load (never mid-exam).
                registration.addEventListener("updatefound", () => {
                    const worker = registration.installing;
                    worker?.addEventListener("statechange", () => {
                        if (worker.state === "installed" && navigator.serviceWorker.controller) worker.postMessage("skip-waiting");
                    });
                });
            }).catch(() => { /* Unsupported or blocked: the site still works normally. */ });
        });
    }

    // ---- Install button ---------------------------------------------------------------------
    let deferredPrompt = null;
    const installButtons = () => document.querySelectorAll("[data-install-app]");
    window.addEventListener("beforeinstallprompt", (event) => {
        event.preventDefault();
        deferredPrompt = event;
        installButtons().forEach((button) => button.classList.remove("hidden"));
    });
    document.addEventListener("click", async (event) => {
        const button = event.target instanceof Element ? event.target.closest("[data-install-app]") : null;
        if (!button || !deferredPrompt) return;
        deferredPrompt.prompt();
        await deferredPrompt.userChoice.catch(() => null);
        deferredPrompt = null;
        installButtons().forEach((b) => b.classList.add("hidden"));
    });
    window.addEventListener("appinstalled", () => installButtons().forEach((b) => b.classList.add("hidden")));

    // ---- Connection banner -------------------------------------------------------------------
    const banner = document.createElement("div");
    banner.setAttribute("role", "status");
    banner.setAttribute("aria-live", "polite");
    Object.assign(banner.style, {
        position: "fixed", left: "50%", bottom: "16px", transform: "translate(-50%, 140%)", zIndex: "80",
        padding: "10px 18px", borderRadius: "999px", font: "600 13px/1.4 system-ui, -apple-system, Segoe UI, sans-serif",
        color: "#fff", background: "#0c0c1d", boxShadow: "0 18px 40px -16px rgba(12,12,29,.55)", transition: "transform .35s cubic-bezier(.2,.9,.3,1.2)",
        maxWidth: "calc(100% - 32px)", textAlign: "center", pointerEvents: "none",
    });
    let hideTimer = 0;
    const show = (text, tone) => {
        banner.textContent = text;
        banner.style.background = tone === "ok" ? "#047857" : "#0c0c1d";
        banner.style.transform = "translate(-50%, 0)";
        window.clearTimeout(hideTimer);
        if (tone === "ok") hideTimer = window.setTimeout(() => { banner.style.transform = "translate(-50%, 140%)"; }, 2600);
    };
    const attach = () => { if (!banner.isConnected) document.body.append(banner); };
    window.addEventListener("offline", () => { attach(); show("You are offline. We will reconnect automatically.", "off"); });
    window.addEventListener("online", () => { attach(); show("Back online", "ok"); });
    if (!navigator.onLine) window.addEventListener("DOMContentLoaded", () => { attach(); show("You are offline. We will reconnect automatically.", "off"); });
})();
