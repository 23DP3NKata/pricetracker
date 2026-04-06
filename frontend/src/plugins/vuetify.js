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
          primary: '#1D4ED8',
          secondary: '#1E3A8A',
          accent: '#93C5FD',
          error: '#FF5252',
          info: '#2196F3',
          success: '#22C55E',
          warning: '#F59E0B',
          background: '#F3F7FF',
          surface: '#FFFFFF',
          'on-surface': '#111827',
        },
      },
      dark: {
        dark: true,
        colors: {
          primary: '#60A5FA',
          secondary: '#1D4ED8',
          accent: '#93C5FD',
          error: '#FF5252',
          info: '#38BDF8',
          success: '#22C55E',
          warning: '#F59E0B',
          background: '#0B1220',
          surface: '#111827',
          'on-surface': '#E5EEFF',
        },
      },
    },
  },
  defaults: {
    VBtn: {
      rounded: 'lg',
      style: 'text-transform: none; letter-spacing: 0;',
    },
    VListItem: {
      rounded: 'lg',
    },
    VCard: {
      elevation: 0,
    },
  },
})