import { createRouter, createWebHistory } from 'vue-router'
import LandingPage from '../views/LandingPage.vue'
import NotFound from '../views/NotFound.vue'
import Login from '../views/Login.vue'
import Dashboard from '../views/Dashboard.vue'
import Account from '../views/Account.vue'
import AccountType from '../views/AccountType.vue'
import Company from '../views/Company.vue'
import GeneralLedgerImport from '../views/GeneralLedgerImport.vue'
import GeneralLedgerList from '../views/GeneralLedgerList.vue'
import GeneralLedgerEntry from '../views/GeneralLedgerEntry.vue'
import GeneralLedgerAdjustment from '../views/GeneralLedgerAdjustment.vue'
import GeneralLedgerDetail from '../views/GeneralLedgerDetail.vue'
import GeneralLedgerVoucherDetail from '../views/GeneralLedgerVoucherDetail.vue'
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
        path: '/jurnal',
        component: GeneralLedgerLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: 'daftar-import',
                component: GeneralLedgerList,
                meta: { title: 'Daftar Import Jurnal' }
            },
            {
                path: 'entri',
                component: GeneralLedgerEntry,
                meta: { title: 'Entri Jurnal' }
            },
            {
                path: 'import',
                component: GeneralLedgerImport,
                meta: { title: 'Import Jurnal' }
            },
            {
                path: 'detail/:importNo',
                component: GeneralLedgerDetail,
                meta: { title: 'Detail Jurnal' }
            },
            {
                path: 'daftar-bukti',
                component: GeneralLedgerVoucherDetail,
                meta: { title: 'Daftar Bukti Jurnal' }
            },
            {
                path: 'koreksi/:referenceNo',
                component: GeneralLedgerAdjustment,
                meta: { title: 'Koreksi Jurnal' }
            },
        ]
    },
    {
        path: '/master-data',
        component: MasterDataLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: 'tipe-akun',
                component: AccountType,
                meta: { title: 'Tipe Akun' }
            },
            {
                path: 'daftar-akun',
                component: Account,
                meta: { title: 'Daftar Akun' }
            },
            {
                path: 'daftar-perusahaan',
                component: Company,
                meta: { title: 'Daftar Perusahaan' }
            },
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
            next('/')
        } else {
            next()
        }
    }
})

export default router