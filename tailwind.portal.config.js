/** Tailwind build for the portals (Owner Console, school admin, sign-in). Separate from the student pages' app.css. */
/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./resources/views/layouts/portal.php",
        "./resources/views/layouts/auth.php",
        "./resources/views/layouts/exam.php",
        "./resources/views/layouts/print.php",
        "./resources/views/layouts/practice.php",
        "./resources/views/layouts/practice-run.php",
        "./resources/views/practice/**/*.php",
        "./public/assets/js/practice.js",
        "./resources/views/shared/**/*.php",
        "./public/assets/js/omr-scan.js",
        "./resources/views/public/**/*.php",
        "./resources/views/partials/report-card.php",
        "./public/assets/js/exam-room.js",
        "./public/assets/js/proctor.js",
        "./resources/views/auth/**/*.php",
        "./resources/views/account/**/*.php",
        "./resources/views/console/**/*.php",
        "./resources/views/school/**/*.php",
        "./resources/views/student/**/*.php",
        "./resources/views/bank/**/*.php",
        "./resources/views/partials/portal/**/*.php",
        "./app/Helpers/icons.php",
        "./public/assets/js/portal.js",
    ],
    darkMode: ["selector", '[data-theme="dark"]'],
    theme: {
        extend: {
            colors: {
                // Per-school brand colour, injected as RGB channels: --brand: 79 70 229
                brand: "rgb(var(--brand) / <alpha-value>)",
                "brand-ink": "rgb(var(--brand-ink) / <alpha-value>)",
                canvas: "rgb(var(--canvas) / <alpha-value>)",
                soft: "rgb(var(--soft) / <alpha-value>)",
                gold: "rgb(var(--gold) / <alpha-value>)",
                card: "rgb(var(--card) / <alpha-value>)",
                line: "rgb(var(--line) / <alpha-value>)",
                ink: "rgb(var(--ink) / <alpha-value>)",
                muted: "rgb(var(--muted) / <alpha-value>)",
                subtle: "rgb(var(--subtle) / <alpha-value>)",
            },
            fontFamily: {
                sans: ["InterVariable", "Inter", "ui-sans-serif", "system-ui", "-apple-system", "Segoe UI", "sans-serif"],
                display: ["JakartaVariable", "InterVariable", "ui-sans-serif", "system-ui", "sans-serif"],
            },
            boxShadow: {
                card: "0 1px 2px rgb(15 23 42 / 0.04), 0 1px 3px rgb(15 23 42 / 0.06)",
                lift: "0 12px 32px -12px rgb(15 23 42 / 0.25)",
            },
            keyframes: {
                "toast-in": { "0%": { opacity: "0", transform: "translateY(-8px) scale(.98)" }, "100%": { opacity: "1", transform: "none" } },
                "fade-up": { "0%": { opacity: "0", transform: "translateY(6px)" }, "100%": { opacity: "1", transform: "none" } },
            },
            animation: {
                "toast-in": "toast-in .22s ease-out",
                "fade-up": "fade-up .35s ease-out both",
            },
        },
    },
    plugins: [],
};
