export default [
  {
    title: 'Dashboards',
    icon: { icon: 'ri-home-smile-line' },
    children: [
      {
        title: 'Utama / Ringkasan',
        to: 'dashboards-analytics',
        action: 'read',
        subject: 'Dashboard Analytics',
      },
      {
        title: 'Penjualan',
        to: 'dashboards-penjualan',
        action: 'read',
        subject: 'Dashboard Penjualan',
      },
      {
        title: 'Barang & Mutasi',
        to: 'dashboards-barang',
        action: 'read',
        subject: 'Dashboard Barang',
      },
      {
        title: 'Laba & Keuntungan',
        to: 'dashboards-keuntungan',
        action: 'read',
        subject: 'Dashboard Keuntungan',
      },
      {
        title: 'Audit & Keamanan',
        to: 'dashboards-audit',
        action: 'read',
        subject: 'Dashboard Audit',
      },
    ],
    badgeContent: '5',
    badgeClass: 'bg-error',
  },
  {
    title: 'Front Pages',
    icon: { icon: 'ri-file-copy-line' },
    children: [
      {
        title: 'Landing',
        to: 'front-pages-landing-page',
        target: '_blank',
      },
      {
        title: 'Pricing',
        to: 'front-pages-pricing',
        target: '_blank',
      },
      {
        title: 'Payment',
        to: 'front-pages-payment',
        target: '_blank',
      },
      {
        title: 'Checkout',
        to: 'front-pages-checkout',
        target: '_blank',
      },
      {
        title: 'Help Center',
        to: 'front-pages-help-center',
        target: '_blank',
      },
    ],
  },
]
