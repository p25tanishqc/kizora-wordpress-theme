/** Same design tokens as the prototype (kizora repo: tailwind.config.ts). */
module.exports = {
  content: ['./kizora/**/*.php', './kizora/inc/content/*.html', './kizora/assets/js/*.js'],
  theme: {
    extend: {
      colors: {
        cream: '#F6F1E8', paper: '#FBF8F2', ink: '#3A2F2A', ink2: '#6B5D55', line: '#E3D9C8',
        sage: { DEFAULT: '#879E81', deep: '#4F6449', mist: '#E7EDE3' },
        gold: { DEFAULT: '#D2AA55', deep: '#A47E2F' },
        butter: '#EFE3B5', blush: '#EBDAD3', clay: '#D9C3A8', dusk: '#4A3C33',
      },
      fontFamily: {
        serif: ['"Playfair Display"', 'Georgia', 'serif'],
        sans: ['Jost', 'system-ui', 'sans-serif'],
      },
      letterSpacing: { eyebrow: '0.2em' },
      maxWidth: { site: '1240px' },
    },
  },
  plugins: [],
};
