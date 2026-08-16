const emailRouteComponent = () => import('@/pages/apps/email/index.vue')

// 👉 Redirects
export const redirects = [
  // ℹ️ We are redirecting to different pages based on role.
  // NOTE: Role is just for UI purposes. ACL is based on abilities.
  {
    path: '/',
    name: 'index',
    redirect: to => {
      const userData = useCookie('userData')
      
      // Jika user sudah login (memiliki userData), periksa RBAC (ability) untuk menentukan halaman utama
      if (userData.value) {
        // Ambil rules dari localStorage karena instance ability global mungkin belum terupdate di luar komponen Vue
        let rules = []
        try {
          rules = JSON.parse(localStorage.getItem('userAbilityRules') || '[]')
        } catch (e) {
          console.error(e)
        }
        
        const can = (action, subject) => rules.some(r => r.action === action && (r.subject === subject || r.subject === 'all'))

        if (can('read', 'Dashboard Analytics')) {
          return { name: 'dashboards-analytics' }
        }
        if (can('read', 'Kasir (POS)')) {
          return { path: '/pos' }
        }
        
        // Fallback jika tidak ada akses spesifik yang cocok
        return { name: 'dashboards-analytics' }
      }
      
      return { name: 'login', query: to.query }
    },
  },
  {
    path: '/dashboard',
    name: 'dashboard-redirect',
    redirect: () => ({ name: 'dashboards-analytics' }),
  },
  {
    path: '/pages/user-profile',
    name: 'pages-user-profile',
    redirect: () => ({ name: 'pages-user-profile-tab', params: { tab: 'profile' } }),
  },
  {
    path: '/pages/account-settings',
    name: 'pages-account-settings',
    redirect: () => ({ name: 'pages-account-settings-tab', params: { tab: 'account' } }),
  },
]
export const routes = [
  // Email filter
  {
    path: '/apps/email/filter/:filter',
    name: 'apps-email-filter',
    component: emailRouteComponent,
    meta: {
      navActiveLink: 'apps-email',
      layoutWrapperClasses: 'layout-content-height-fixed',
    },
  },

  // Email label
  {
    path: '/apps/email/label/:label',
    name: 'apps-email-label',
    component: emailRouteComponent,
    meta: {
      // contentClass: 'email-application',
      navActiveLink: 'apps-email',
      layoutWrapperClasses: 'layout-content-height-fixed',
    },
  },
  {
    path: '/dashboards/logistics',
    name: 'dashboards-logistics',
    component: () => import('@/pages/apps/logistics/dashboard.vue'),
  },
  {
    path: '/dashboards/academy',
    name: 'dashboards-academy',
    component: () => import('@/pages/apps/academy/dashboard.vue'),
  },
  {
    path: '/apps/ecommerce/dashboard',
    name: 'apps-ecommerce-dashboard',
    component: () => import('@/pages/dashboards/ecommerce.vue'),
  },
]
