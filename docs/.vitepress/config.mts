import { defineConfig } from 'vitepress'

const SITE_URL = 'https://chetanupare.github.io/WP-Native-Content-Relationships'
const BASE = '/WP-Native-Content-Relationships/'
/** For versioned docs: set to the preferred base URL for canonicals (e.g. /latest/ or main branch). Leave as SITE_URL to use current deployment as canonical. */
const CANONICAL_BASE = SITE_URL
const DESCRIPTION =
  'Native Content Relationships (NCR) — a native, scalable relationship layer for WordPress. Link posts, users, and terms with PHP API, WP_Query, REST, shortcodes, and blocks. Schema stable, backward compatible.'

export default defineConfig({
    title: 'Native Content Relationships',
    description: DESCRIPTION,
    titleTemplate: '%s | Native Content Relationships',
    base: BASE,
    sitemap: {
      hostname: SITE_URL,
      lastmod: true,
      transformItems: (items) =>
        items.map((it) => {
          const fullUrl = it.url.startsWith('http')
            ? it.url
            : SITE_URL + (it.url.startsWith('/') ? it.url : '/' + it.url);
          return { ...it, url: fullUrl };
        }),
    },
    head: [
      ['link', { rel: 'sitemap', type: 'application/xml', href: SITE_URL + '/sitemap.xml' }],
      // Favicon: SVG (primary), ICO fallback and apple-touch-icon for SEO and bookmarks
      ['link', { rel: 'icon', type: 'image/svg+xml', href: BASE + 'wordpress-logo-svgrepo-com.svg' }],
      ['link', { rel: 'icon', type: 'image/x-icon', href: BASE + 'favicon.ico' }],
      ['link', { rel: 'apple-touch-icon', href: BASE + 'apple-touch-icon.png' }],
      ['link', { rel: 'manifest', href: BASE + 'manifest.webmanifest' }],
      ['meta', { name: 'theme-color', content: '#0d9488' }],
      ['meta', { name: 'viewport', content: 'width=device-width, initial-scale=1' }],
      ['link', { rel: 'preconnect', href: 'https://fonts.googleapis.com' }],
      ['link', { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' }],
      ['meta', { name: 'author', content: 'Native Content Relationships' }],
      ['meta', { name: 'robots', content: 'index, follow' }],
      ['meta', { name: 'format-detection', content: 'telephone=no' }],
      // Title & description (default; overridden per-page in transformHead)
      ['meta', { name: 'description', content: DESCRIPTION }],
      // Canonical: set only in transformHead (per-page) to avoid duplicate indexing; supports versioned docs via CANONICAL_BASE
      // Open Graph (default)
      ['meta', { property: 'og:type', content: 'website' }],
      ['meta', { property: 'og:url', content: SITE_URL + '/' }],
      ['meta', { property: 'og:title', content: 'Native Content Relationships — WordPress relationship layer' }],
      ['meta', { property: 'og:description', content: DESCRIPTION }],
      ['meta', { property: 'og:image', content: SITE_URL + '/wordpress-logo-svgrepo-com.svg' }],
      ['meta', { property: 'og:image:alt', content: 'Native Content Relationships' }],
      ['meta', { property: 'og:image:type', content: 'image/svg+xml' }],
      ['meta', { property: 'og:locale', content: 'en_US' }],
      ['meta', { property: 'og:site_name', content: 'Native Content Relationships' }],
      // Twitter Card (default)
      ['meta', { name: 'twitter:card', content: 'summary' }],
      ['meta', { name: 'twitter:title', content: 'Native Content Relationships — WordPress relationship layer' }],
      ['meta', { name: 'twitter:description', content: DESCRIPTION }],
      ['meta', { name: 'twitter:image', content: SITE_URL + '/wordpress-logo-svgrepo-com.svg' }],
      ['meta', { name: 'twitter:image:alt', content: 'Native Content Relationships' }],
      // JSON-LD: Organization (publisher)
      [
        'script',
        { type: 'application/ld+json' },
        JSON.stringify({
          '@context': 'https://schema.org',
          '@type': 'Organization',
          '@id': SITE_URL + '/#organization',
          name: 'Native Content Relationships',
          url: SITE_URL + '/',
          description: DESCRIPTION,
          sameAs: [
            'https://github.com/chetanupare/WP-Native-Content-Relationships',
            'https://wordpress.org/plugins/native-content-relationships/',
          ],
        }),
      ],
      // JSON-LD: WebSite
      [
        'script',
        { type: 'application/ld+json' },
        JSON.stringify({
          '@context': 'https://schema.org',
          '@type': 'WebSite',
          '@id': SITE_URL + '/#website',
          name: 'Native Content Relationships',
          url: SITE_URL + '/',
          description: DESCRIPTION,
          publisher: { '@id': SITE_URL + '/#organization' },
          potentialAction: {
            '@type': 'ReadAction',
            target: [SITE_URL + '/', SITE_URL + '/guide/installation.html', SITE_URL + '/api/php-api.html'],
          },
        }),
      ],
      // JSON-LD: SoftwareApplication (plugin as application)
      [
        'script',
        { type: 'application/ld+json' },
        JSON.stringify({
          '@context': 'https://schema.org',
          '@type': 'SoftwareApplication',
          '@id': SITE_URL + '/#softwareapplication',
          name: 'Native Content Relationships',
          applicationCategory: 'DeveloperApplication',
          operatingSystem: 'WordPress',
          description: DESCRIPTION,
          url: 'https://wordpress.org/plugins/native-content-relationships/',
          author: { '@id': SITE_URL + '/#organization' },
        }),
      ],
      // JSON-LD: SoftwareSourceCode / Open Source Project (codebase & repo)
      [
        'script',
        { type: 'application/ld+json' },
        JSON.stringify({
          '@context': 'https://schema.org',
          '@type': 'SoftwareSourceCode',
          '@id': SITE_URL + '/#opensourceproject',
          name: 'Native Content Relationships',
          description: DESCRIPTION,
          url: SITE_URL + '/',
          codeRepository: 'https://github.com/chetanupare/WP-Native-Content-Relationships',
          programmingLanguage: 'PHP',
          runtimePlatform: 'WordPress',
          license: 'https://spdx.org/licenses/GPL-2.0-or-later.html',
          author: { '@id': SITE_URL + '/#organization' },
        }),
      ],
    ],
    transformHead(context) {
      // Canonical page URL: strip .md, use .html (cleanUrls false), index => /
      let path = context.page.replace(/\/$/, '').replace(/\.md$/, '') || ''
      if (path === 'index' || path === '') path = '/'
      else path = (path.startsWith('/') ? path : '/' + path) + '.html'
      // Use CANONICAL_BASE so versioned docs (e.g. /v1/, /v2/) can point canonicals to preferred version
      const pageUrl = path === '/' ? CANONICAL_BASE + '/' : CANONICAL_BASE + path
      const title = context.title || 'Native Content Relationships'
      const description = context.pageData?.description || DESCRIPTION
      const lastUpdated = context.pageData?.lastUpdated
      const imageUrl = SITE_URL + '/wordpress-logo-svgrepo-com.svg'
      // Per-page meta: description, Open Graph, Twitter Card (override defaults)
      const meta = [
        ['meta', { name: 'description', content: description }],
        // Single canonical per page (prevents duplicate indexing; critical if docs are versioned)
        ['link', { rel: 'canonical', href: pageUrl }],
        ['meta', { property: 'og:type', content: 'website' }],
        ['meta', { property: 'og:url', content: pageUrl }],
        ['meta', { property: 'og:title', content: title }],
        ['meta', { property: 'og:description', content: description }],
        ['meta', { property: 'og:image', content: imageUrl }],
        ['meta', { property: 'og:image:alt', content: title }],
        ['meta', { property: 'og:image:type', content: 'image/svg+xml' }],
        ['meta', { property: 'og:locale', content: 'en_US' }],
        ['meta', { property: 'og:site_name', content: 'Native Content Relationships' }],
        ['meta', { name: 'twitter:card', content: 'summary' }],
        ['meta', { name: 'twitter:title', content: title }],
        ['meta', { name: 'twitter:description', content: description }],
        ['meta', { name: 'twitter:image', content: imageUrl }],
        ['meta', { name: 'twitter:image:alt', content: title }],
      ]
      const webPage = {
        '@context': 'https://schema.org',
        '@type': 'TechArticle',
        '@id': pageUrl + '#webpage',
        url: pageUrl,
        name: title,
        description: description,
        isPartOf: { '@id': SITE_URL + '/#website' },
        about: {
          '@type': 'SoftwareApplication',
          name: 'Native Content Relationships',
        },
        ...(lastUpdated && {
          dateModified: new Date(lastUpdated).toISOString(),
        }),
      }
      return [
        ...meta,
        ['script', { type: 'application/ld+json' }, JSON.stringify(webPage)],
      ]
    },
    themeConfig: {
      logo: '/WP-Native-Content-Relationships/wordpress-logo-svgrepo-com.svg',
      nav: [
        { text: 'Guide', link: '/guide/introduction' },
        { text: 'API', link: '/api/php-api' },
        { text: 'Architecture', link: '/architecture/overview' },
        { text: 'Performance', link: '/performance/benchmarks' },
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
          text: 'Guide',
          items: [
            { text: 'Introduction', link: '/guide/introduction' },
            { text: 'Installation', link: '/guide/installation' },
            { text: 'Quick Start', link: '/guide/quick-start' },
            { text: 'Relationships', link: '/guide/relationships' },
            { text: 'Use Cases', link: '/guide/use-cases' },
            { text: 'Widget', link: '/guide/widget' },
            { text: 'FAQ', link: '/guide/faq' },
            { text: 'Troubleshooting', link: '/guide/troubleshooting' },
          ],
        },
        {
          text: 'API',
          items: [
            { text: 'PHP API', link: '/api/php-api' },
            { text: 'Fluent API', link: '/api/fluent-api' },
            { text: 'WP_Query', link: '/api/wp-query' },
            { text: 'REST API', link: '/api/rest-api' },
            { text: 'Shortcodes', link: '/api/shortcodes' },
            { text: 'WP-CLI', link: '/api/wp-cli' },
            { text: 'Hooks & Filters', link: '/api/hooks-filters' },
          ],
        },
        {
          text: 'Integrations',
          items: [
            { text: 'Gutenberg', link: '/integrations/gutenberg' },
            { text: 'Elementor', link: '/integrations/elementor' },
            { text: 'WooCommerce', link: '/integrations/woocommerce' },
            { text: 'Multilingual', link: '/integrations/multilingual' },
            { text: 'SEO (Yoast & Rank Math)', link: '/integrations/seo' },
            { text: 'Duplicate Post', link: '/integrations/duplicate-post' },
          ],
        },
        {
          text: 'Tools',
          items: [
            { text: 'Admin & Tools', link: '/tools/admin-tools' },
          ],
        },
        {
          text: 'Extending',
          items: [
            { text: 'Custom Types', link: '/extending/custom-types' },
            { text: 'Extension Hooks', link: '/extending/extension-hooks' },
            { text: 'Building Addons', link: '/extending/building-addons' },
            { text: 'Capabilities', link: '/extending/capabilities' },
          ],
        },
        {
          text: 'Core concepts',
          items: [
            { text: 'Relationship types', link: '/core-concepts/relationship-types' },
            { text: 'Direction', link: '/core-concepts/direction' },
            { text: 'Database schema', link: '/core-concepts/database-schema' },
            { text: 'Architecture', link: '/core-concepts/architecture' },
          ],
        },
        {
          text: 'Migration',
          items: [
            { text: 'From ACF', link: '/migration/from-acf' },
            { text: 'From post meta', link: '/migration/from-meta' },
            { text: 'From P2P', link: '/migration/from-posts-2-posts' },
          ],
        },
        {
          text: 'Architecture',
          items: [
            { text: 'Overview', link: '/architecture/overview' },
            { text: 'Schema', link: '/architecture/schema' },
            { text: 'Indexing', link: '/architecture/indexing' },
          ],
        },
        {
          text: 'Performance',
          items: [
            { text: 'Benchmarks', link: '/performance/benchmarks' },
            { text: 'Scaling guide', link: '/performance/scaling-guide' },
            { text: 'Indexing', link: '/performance/indexing' },
          ],
        },
      ],
      outline: { level: [2, 3], label: 'On this page' },
      docFooter: {
        prev: 'Previous',
        next: 'Next',
      },
      editLink: {
        pattern: 'https://github.com/chetanupare/WP-Native-Content-Relationships/edit/main/docs/:path',
        text: 'Edit this page on GitHub',
      },
      lastUpdated: { text: 'Updated at', formatOptions: { dateStyle: 'short' } },
      socialLinks: [
        { icon: 'github', link: 'https://github.com/chetanupare/WP-Native-Content-Relationships' },
      ],
      search: {
        provider: 'local',
      },
      footer: {
        message: 'Schema stable from 1.x onward. Backward compatibility guaranteed.',
        copyright: 'GPLv2 or later · Native Content Relationships',
      },
    },
  })
