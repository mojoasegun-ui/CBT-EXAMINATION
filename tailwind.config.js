/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./resources/views/**/*.php",
        "./public/assets/js/**/*.js",
    ],
    safelist: [
        "w-[10%]", "w-[20%]", "w-[30%]", "w-[40%]", "w-[50%]",
        "w-[60%]", "w-[70%]", "w-[80%]", "w-[90%]", "w-full",
    ],
    theme: {
        extend: {
            colors: {
                primary: "#1E40AF",
                "primary-600": "#1D4ED8",
                accent: "#F59E0B",
                success: "#10B981",
                danger: "#DC2626",
                ink: "#0F172A",
                muted: "#64748B",
                surface: "#F8FAFC",
                card: "#FFFFFF",
            },
            fontFamily: {
                display: ["Plus Jakarta Sans", "Inter", "ui-sans-serif", "system-ui"],
                sans: ["Inter", "Plus Jakarta Sans", "ui-sans-serif", "system-ui"],
            },
        },
    },
    plugins: [],
};