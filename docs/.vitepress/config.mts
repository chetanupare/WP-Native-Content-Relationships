import { defineConfig } from 'vitepress'

export default defineConfig({
    title: 'Native Content Relationships',
    description:
      'A native, scalable relationship layer for WordPress — posts, users, and terms. Documentation, architecture, and performance.',
    base: '/WP-Native-Content-Relationships/',
    head: [
      [
        'link',
        {
          rel: 'icon',
          type: 'image/svg+xml',
          href: '/WP-Native-Content-Relationships/wordpress-logo-svgrepo-com.svg',
        },
      ],
    ],
    themeConfig: {
      logo: '/WP-Native-Content-Relationships/wordpress-logo-svgrepo-com.svg',
      nav: [
        { text: 'Guide', link: '/guide/getting-started' },
        { text: 'Architecture', link: '/technical/architecture' },
        { text: 'Performance', link: '/technical/performance' },
        { text: 'Features', link: '/product/features' },
        {
          text: 'Links',
          items: [
            { text: 'WordPress.org', link: 'https://wordpress.org/plugins/native-content-relationships/' },
            { text: 'GitHub', link: 'https://github.com/chetanupare/WP-Native-Content-Relationships' },
          ],
        },
      ],
      sidebar: [
        {
          text: 'Introduction',
          items: [
            { text: 'Home', link: '/' },
            { text: 'Getting started', link: '/guide/getting-started' },
          ],
        },
        {
          text: 'Technical',
          items: [
            { text: 'Architecture', link: '/technical/architecture' },
            { text: 'Performance', link: '/technical/performance' },
          ],
        },
        {
          text: 'Product',
          items: [
            { text: 'Feature overview', link: '/product/features' },
          ],
        },
        {
          text: 'Internal',
          collapsed: true,
          items: [
            { text: '90-day plan', link: '/internal/expansion-plan' },
          ],
        },
      ],
      socialLinks: [
        { icon: 'github', link: 'https://github.com/chetanupare/WP-Native-Content-Relationships' },
      ],
      outline: { level: [2, 3] },
      search: {
        provider: 'local',
      },
      footer: {
        message: 'Schema stable from 1.x onward. Backward compatibility guaranteed.',
        copyright: 'GPLv2 or later · Native Content Relationships',
      },
    },
  })
