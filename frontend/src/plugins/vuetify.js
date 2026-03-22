import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import '@mdi/font/css/materialdesignicons.css'

export default createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        dark: false,
        colors: {
          primary: '#FF8F3F',
          secondary: '#F97316',
          accent: '#FFD6B0',
          error: '#FF5252',
          info: '#2196F3',
          success: '#22C55E',
          warning: '#F59E0B',
          background: '#FFF8F2',
          surface: '#FFFFFF',
          'on-surface': '#2D1B12',
        },
      },
      dark: {
        dark: true,
        colors: {
          primary: '#FF9F5A',
          secondary: '#EA580C',
          accent: '#FFD6B0',
          error: '#FF5252',
          info: '#38BDF8',
          success: '#22C55E',
          warning: '#F59E0B',
          background: '#1F140F',
          surface: '#2A1D17',
          'on-surface': '#FFE9D6',
        },
      },
    },
  },
  defaults: {
    VBtn: {
      style: 'text-transform: none; letter-spacing: 0;',
    },
    VCard: {
      elevation: 0,
    },
  },
})