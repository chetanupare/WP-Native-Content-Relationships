#!/usr/bin/env node
/**
 * Post-build: pretty-print sitemap.xml so Search Console and other crawlers can read it reliably.
 * Inserts newlines between tags for valid, readable XML.
 */
import { readFileSync, writeFileSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = join(__dirname, '..');
const sitemapPath = join(root, 'docs', '.vitepress', 'dist', 'sitemap.xml');

let xml;
try {
  xml = readFileSync(sitemapPath, 'utf8');
} catch (e) {
  console.warn('format-sitemap: sitemap not found at', sitemapPath);
  process.exit(0);
}

// Insert newline between adjacent tags so XML is readable and valid
const pretty = xml.replace(/></g, '>\n<');
writeFileSync(sitemapPath, pretty + '\n', 'utf8');
console.log('format-sitemap: pretty-printed sitemap.xml');
