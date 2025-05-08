import { createRouter, createWebHistory } from 'vue-router'
import LandingPage from '../views/LandingPage.vue'
import NotFound from '../views/NotFound.vue'
import Login from '../views/Login.vue'
import Dashboard from '../views/Dashboard.vue'
import Account from '../views/Account.vue'
import AccountType from '../views/AccountType.vue'
import GeneralLedgerImport from '../views/GeneralLedgerImport.vue'
import GeneralLedgerEntry from '../views/GeneralLedgerEntry.vue'
import GeneralLedgerCreate from '../views/GeneralLedgerCreate.vue'
import GeneralLedgerDetail from '../views/GeneralLedgerDetail.vue'
import BalanceSheetStandard from '../views/BalanceSheetStandard.vue'
import IncomeStatement from '../views/IncomeStatement.vue'
import Ledger from '../views/Ledger.vue'

// layout
import MasterDataLayout from '../components/layouts/MasterDataLayout.vue'
import GeneralLedgerLayout from '../components/layouts/GeneralLedgerLayout.vue'
import FinancialReportLayout from '../components/layouts/FinancialReportLayout.vue'


const routes = [
    {
        path: '/',
        name: 'LandingPage',
        component: LandingPage,
        meta: { title: 'Landing Page', requiresGuest: true }
    },
    {
        path: '/dashboard',
        component: Dashboard,
        meta: { title: 'Dashboard', requiresAuth: true }
    },
    {
        path: '/jurnal-umum',
        component: GeneralLedgerLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: 'daftar',
                component: GeneralLedgerEntry,
                meta: { title: 'Daftar Jurnal Umum' }
            },
            {
                path: 'buat',
                component: GeneralLedgerCreate,
                meta: { title: 'Buat Jurnal Umum' }
            },
            {
                path: 'import',
                component: GeneralLedgerImport,
                meta: { title: 'Import Jurnal Umum' }
            },
            {
                path: 'detail/:importNo',
                component: GeneralLedgerDetail,
                meta: { title: 'Detail Jurnal Umum' }
            },
        ]
    },
    {
        path: '/master-data',
        component: MasterDataLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: 'daftar-akun',
                component: Account,
                meta: { title: 'Daftar Akun' }
            },
            {
                path: 'tipe-akun',
                component: AccountType,
                meta: { title: 'Tipe Akun' }
            },
            // {
            //     path: '/master-data/periode-akuntansi',
            //     component: AccountingPeriode,
            //     meta: { title: 'Periode Akuntansi' }
            // },
        ]
    },
    {
        path: '/laporan-keuangan',
        component: FinancialReportLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: 'neraca',
                component: BalanceSheetStandard,
                meta: { title: 'Neraca' }
            },
            {
                path: 'laba-rugi',
                component: IncomeStatement,
                meta: { title: 'Laba Rugi' }
            },
            {
                path: 'buku-besar',
                component: Ledger,
                meta: { title: 'Buku Besar' }
            },
        ]
    },
    {
        path: '/login',
        component: Login,
        meta: { title: 'Login' },
        meta: { title: 'Login', requiresGuest: true }
    },
    {
        path: '/:pathMatch(.*)*',
        component: NotFound,
        meta: { title: '404 Not Found' }
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

// navigation guard
router.beforeEach((to, from , next) => {
    const token = localStorage.getItem('accessToken')

    if (token) {
        // if user is logged in, prevent access to landing page and login, redirect to dashboard
        if (to.meta.requiresGuest) {
            next('/dashboard')
        } else {
            // if is not logged in, only access landing page and login, after that, to /login
            next()
        }
    } else {
        if (to.meta.requiresAuth) {
            localStorage.setItem('authError', 'Silakan login terlebih dahulu.')
            next('/login')
        } else {
            next()
        }
    }
})

export default router