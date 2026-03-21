/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./public/**/*.{html,js}",
    "./src/views/**/*.{php,html}",
  ],
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        primary: "#137fec",
        primaryDark: "#1D4ED8",
        surface: "#FFFFFF",
        bg: "#F3F6FA",
        textMain: "#1E293B",
        textSub: "#64748B",
        success: "#10B981",
        warning: "#6366F1",
        danger: "#EF4444",
        // Tokens for add_medicine.html
        "text-main-light": "#0d141b",
        "text-main-dark": "#e0e6ed",
        "text-sub-light": "#4c739a",
        "text-sub-dark": "#94a3b8",
        "background-light": "#f6f7f8",
        "background-dark": "#101922",
        "card-light": "#ffffff",
        "card-dark": "#182635",
        "input-border-light": "#cfdbe7",
        "input-border-dark": "#2d3b4e",
      },
      fontFamily: {
        display: ["Plus Jakarta Sans", "sans-serif"],
        body: ["Inter", "sans-serif"]
      },
      boxShadow: {
        soft: "0 8px 30px rgba(0,0,0,0.05)"
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/container-queries'),
  ],
}
