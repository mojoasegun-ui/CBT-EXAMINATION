"use strict";

/* Phone notifications: asks permission (only after a tap), registers this device, and keeps it registered. */
(() => {
    const banner = document.querySelector("[data-push-banner]");
    if (!banner) return;
    const base = banner.dataset.pushBase;
    const key = banner.dataset.pushKey;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";
    const title = banner.querySelector("[data-push-title]");
    const text = banner.querySelector("[data-push-text]");
    const enable = banner.querySelector("[data-push-enable]");
    const dismiss = banner.querySelector("[data-push-dismiss]");
    const show = () => { banner.classList.remove("hidden"); banner.classList.add("flex"); };
    const hide = () => { banner.classList.add("hidden"); banner.classList.remove("flex"); };
    const ua = navigator.userAgent || "";
    const isIos = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1);
    const standalone = window.matchMedia("(display-mode: standalone)").matches || navigator.standalone === true;
    const DISMISS_KEY = "examcore-push-dismissed";
    const remembered = () => { try { return Number(localStorage.getItem(DISMISS_KEY) || 0) > Date.now(); } catch (e) { return false; } };

    const toKey = (b64) => {
        const raw = atob((b64 + "=".repeat((4 - (b64.length % 4)) % 4)).replace(/-/g, "+").replace(/_/g, "/"));
        return Uint8Array.from(raw, (c) => c.charCodeAt(0));
    };
    const post = (path, body) => fetch(`${base}/${path}`, {
        method: "POST", credentials: "same-origin",
        headers: { "Content-Type": "application/json", "X-CSRF-Token": csrf, Accept: "application/json" },
        body: JSON.stringify(body || {}),
    });

    const supported = "serviceWorker" in navigator && "PushManager" in window && "Notification" in window;
    // iPhone only allows notifications for apps added to the Home Screen.
    if (!supported) {
        if (isIos && !standalone && !remembered()) {
            title.textContent = "Get alerts on your iPhone";
            text.textContent = "Tap the Share button, choose “Add to Home Screen”, then open ExamCore from your Home Screen and turn on alerts.";
            enable.classList.add("hidden");
            show();
        }
        dismiss?.addEventListener("click", () => { try { localStorage.setItem(DISMISS_KEY, String(Date.now() + 14 * 864e5)); } catch (e) { /* ignore */ } hide(); });
        return;
    }

    const sync = async () => {
        const reg = await navigator.serviceWorker.ready;
        let sub = await reg.pushManager.getSubscription();
        if (!sub && Notification.permission === "granted") {
            sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: toKey(key) });
        }
        if (sub) await post("subscribe", sub.toJSON());
        return sub;
    };

    const start = async () => {
        if (Notification.permission === "denied") {
            if (!remembered()) {
                title.textContent = "Notifications are blocked";
                text.textContent = "Allow notifications for this site in your phone or browser settings, then come back and turn them on.";
                enable.classList.add("hidden");
                show();
            }
            return;
        }
        if (Notification.permission === "granted") {
            try { await sync(); } catch (e) { /* will retry next visit */ }
            hide();
            return;
        }
        if (!remembered()) show();
    };

    enable.addEventListener("click", async () => {
        enable.disabled = true;
        try {
            const result = await Notification.requestPermission();
            if (result !== "granted") {
                title.textContent = "Notifications are off";
                text.textContent = "You can turn them on later from your browser or phone settings.";
                enable.classList.add("hidden");
                return;
            }
            await sync();
            title.textContent = "Alerts are on";
            text.textContent = "You will be notified about exams, results and announcements. A test alert is on its way.";
            enable.classList.add("hidden");
            post("test");
            window.setTimeout(hide, 5000);
        } catch (e) {
            title.textContent = "Could not turn on alerts";
            text.textContent = "Please try again in a moment.";
        } finally {
            enable.disabled = false;
        }
    });
    dismiss?.addEventListener("click", () => {
        try { localStorage.setItem(DISMISS_KEY, String(Date.now() + 7 * 864e5)); } catch (e) { /* ignore */ }
        hide();
    });
    start();
})();
