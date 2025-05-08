import { createApp } from 'vue'
import './assets/tailwind.css'
import './style.css'
import 'primeicons/primeicons.css'
import App from './App.vue'
import router from './router/router'
import PrimeVue from 'primevue/config'
import { definePreset } from '@primevue/themes'
import ToastService from 'primevue/toastservice'
import Aura from '@primevue/themes/aura'
import { config, library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import axios from 'axios'
import { faBarsStaggered, faBars, faMoon, faSun, faUserCircle, faEnvelope, faFileArrowDown, faCheckCircle, faCircleExclamation, faCaretDown, faCircleCheck, faTimes, faFrown, faCog, faSignOutAlt, faCaretUp, faHome, faDashboard, faEdit, faThumbTack, faNewspaper, faProjectDiagram, faUser, faEye, faPlus, faPencilAlt, faThLarge, faLayerGroup, faTrash, faSearch, faBookOpen, faExchangeAlt, faUserCog, faBook, faFileInvoiceDollar, faCogs, faCaretRight, faFileImport, faFile, faFolder, faFileExcel, faFilePdf, faPrint, faPlusCircle, faUpload, faDownload, faBuilding } from '@fortawesome/free-solid-svg-icons'

// add icons
library.add(
    faFileExcel, faFilePdf, faPrint,
    faEnvelope, faFileArrowDown, faCheckCircle,
    faCircleExclamation, faCaretDown, faCaretUp,
    faUserCircle, faBarsStaggered, faBars, faMoon,
    faSun, faCircleCheck, faCircleExclamation, faTimes,
    faFrown, faCog, faSignOutAlt, faHome, faDashboard,
    faEdit, faThumbTack, faNewspaper, faProjectDiagram,
    faUser, faEye, faPlus, faPencilAlt, faThLarge, faLayerGroup,
    faEdit, faTrash, faSearch, faBookOpen, faExchangeAlt, faUserCog,
    faBook, faFileInvoiceDollar, faCogs, faCaretRight, faFileImport,
    faFile, faFolder, faPlusCircle, faUpload, faDownload, faBuilding
)

const app = createApp(App)


const configPrimary = definePreset(Aura, {
    semantic: {
        primary: {
            50: '{zinc.50}',
            100: '{zinc.100}',
            200: '{zinc.200}',
            300: '{zinc.300}',
            400: '{zinc.400}',
            500: '{zinc.500}',
            600: '{zinc.600}',
            700: '{zinc.700}',
            800: '{zinc.800}',
            900: '{zinc.900}',
            950: '{zinc.950}'
        },
        colorScheme: {
            light: {
                primary: {
                    color: '{zinc.950}',
                    inverseColor: '#ffffff',
                    hoverColor: '{zinc.900}',
                    activeColor: '{zinc.800}'
                },
                highlight: {
                    background: '{zinc.950}',
                    focusBackground: '{zinc.700}',
                    color: '#ffffff',
                    focusColor: '#ffffff'
                }
            },
            dark: {
                primary: {
                    color: '{zinc.50}',
                    inverseColor: '{zinc.950}',
                    hoverColor: '{zinc.100}',
                    activeColor: '{zinc.200}'
                },
                highlight: {
                    background: 'rgba(250, 250, 250, .16)',
                    focusBackground: 'rgba(250, 250, 250, .24)',
                    color: 'rgba(255,255,255,.87)',
                    focusColor: 'rgba(255,255,255,.87)'
                }
            }
        }
    }
})

app.component('font-awesome-icon', FontAwesomeIcon)

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL,
    withCredentials: true,
})

api.interceptors.request.use((config) => {
    const db = localStorage.getItem("db")
    if (db) {
        config.params = config.params || {}; // Pastikan params ada
        config.params.db = db; // Tambahkan db ke params
    } else {
        console.warn("⚠️ Warning: 'db' tidak ditemukan di localStorage!");
    }

    return config
}, (error) => {
    return Promise.reject(error)
})

app.config.globalProperties.$api = api

app.use(router)
app.use(PrimeVue, {
    theme: {
        preset: configPrimary,
        options: {
            prefix: 'p',
            darkModeSelector: false || 'none',
            cssLayer: false
        }
    }
 })
app.use(ToastService)
app.mount('#app')

