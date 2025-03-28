module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./app/Filament/**/*.php",
  ],
  theme: {
    extend: {},
  },
  plugins: [require("daisyui")],
}
