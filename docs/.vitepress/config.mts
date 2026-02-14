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
    base: BASE,
    sitemap: {
      hostname: SITE_URL + '/',
      lastmod: true,
      transformItems: (items) =>
        items.map((it) => ({
          ...it,
          url: it.url.startsWith('http') ? it.url : SITE_URL + (it.url.startsWith('/') ? it.url : '/' + it.url),
        })),
    },
    head: [
      ['link', { rel: 'icon', type: 'image/svg+xml', href: BASE + 'wordpress-logo-svgrepo-com.svg' }],
      ['link', { rel: 'manifest', href: BASE + 'manifest.webmanifest' }],
      ['meta', { name: 'theme-color', content: '#42b883' }],
      ['meta', { name: 'author', content: 'Native Content Relationships' }],
      ['meta', { name: 'robots', content: 'index, follow' }],
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
            target: [SITE_URL + '/', SITE_URL + '/getting-started/installation', SITE_URL + '/api/php-api'],
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
        {
          text: 'Docs',
          items: [
            { text: 'Introduction', link: '/' },
            { text: 'Quick Start', link: '/getting-started/quick-start' },
            { text: 'Developer Documentation', link: '/documentation' },
            { text: 'Installation', link: '/getting-started/installation' },
            { text: 'Basic Relationships', link: '/getting-started/basic-relationships' },
            { text: 'Blogs', link: '/getting-started/blogs' },
            { text: 'Snippets', link: '/getting-started/snippets' },
          ],
        },
        {
          text: 'API',
          items: [
            { text: 'PHP API', link: '/api/php-api' },
            { text: 'WP_Query', link: '/api/wp-query' },
            { text: 'REST API', link: '/api/rest-api' },
            { text: 'WP-CLI', link: '/api/wp-cli' },
            { text: 'Hooks & Filters', link: '/api/hooks-filters' },
          ],
        },
        {
          text: 'Resources',
          items: [
            { text: 'AI & LLM Index', link: '/llm' },
            { text: 'Roadmap', link: '/roadmap' },
            { text: 'Contributing', link: '/contributing' },
            { text: 'WordPress.org', link: 'https://wordpress.org/plugins/native-content-relationships/' },
            { text: 'GitHub', link: 'https://github.com/chetanupare/WP-Native-Content-Relationships' },
          ],
        },
      ],
      sidebar: [
        {
          text: 'Getting Started',
          items: [
            { text: 'Introduction', link: '/' },
            { text: 'Developer Documentation', link: '/documentation' },
            { text: 'Installation', link: '/getting-started/installation' },
            { text: 'Quick Start', link: '/getting-started/quick-start' },
            { text: 'Basic Relationships', link: '/getting-started/basic-relationships' },
            { text: 'Blogs', link: '/getting-started/blogs' },
            { text: 'Snippets', link: '/getting-started/snippets' },
          ],
        },
        {
          text: 'Core Concepts',
          items: [
            { text: 'Relationship Types', link: '/core-concepts/relationship-types' },
            { text: 'Direction', link: '/core-concepts/direction' },
            { text: 'Architecture', link: '/core-concepts/architecture' },
            { text: 'Database Schema', link: '/core-concepts/database-schema' },
          ],
        },
        {
          text: 'API',
          items: [
            { text: 'PHP API', link: '/api/php-api' },
            { text: 'WP_Query', link: '/api/wp-query' },
            { text: 'REST API', link: '/api/rest-api' },
            { text: 'WP-CLI', link: '/api/wp-cli' },
            { text: 'Hooks & Filters', link: '/api/hooks-filters' },
          ],
        },
        {
          text: 'Integrations',
          items: [
            { text: 'WooCommerce', link: '/integrations/woocommerce' },
            { text: 'Elementor', link: '/integrations/elementor' },
            { text: 'Gutenberg', link: '/integrations/gutenberg' },
            { text: 'Multilingual', link: '/integrations/multilingual' },
          ],
        },
        {
          text: 'Performance',
          items: [
            { text: 'Benchmarks', link: '/performance/benchmarks' },
            { text: 'Indexing', link: '/performance/indexing' },
            { text: 'Scaling Guide', link: '/performance/scaling-guide' },
          ],
        },
        {
          text: 'Extending',
          items: [
            { text: 'Custom Types', link: '/extending/custom-types' },
            { text: 'Building Addons', link: '/extending/building-addons' },
            { text: 'Extension Hooks', link: '/extending/extension-hooks' },
          ],
        },
        {
          text: 'Migration',
          items: [
            { text: 'From ACF', link: '/migration/from-acf' },
            { text: 'From Meta', link: '/migration/from-meta' },
            { text: 'From Posts 2 Posts', link: '/migration/from-posts-2-posts' },
          ],
        },
        {
          text: 'Project',
          items: [
            { text: 'AI & LLM Index', link: '/llm' },
            { text: 'Roadmap', link: '/roadmap' },
            { text: 'Contributing', link: '/contributing' },
          ],
        },
      ],
      outline: { level: [2, 3], label: 'On this page' },
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
