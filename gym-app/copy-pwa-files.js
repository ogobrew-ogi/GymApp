// vite-plugin-pwa emits sw.js/manifest.webmanifest/workbox-*.js into
// Vite's build.outDir — which laravel-vite-plugin points at public/build/
// for cache-busted assets. But a service worker's scope can never extend
// above its own script's URL, and the manifest is fetched from a fixed
// root-relative path — both need to be reachable at the actual site
// root, not nested under /build/. Run as a separate step after `vite
// build` finishes entirely (not a Vite plugin hook — vite-plugin-pwa
// writes these files asynchronously in a way that isn't reliably done
// by the time a same-run closeBundle hook fires).
import { readdirSync, readFileSync, writeFileSync, copyFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = dirname(fileURLToPath(import.meta.url));
const buildDir = resolve(root, 'public/build');
const publicDir = resolve(root, 'public');

for (const file of readdirSync(buildDir)) {
    if (file === 'manifest.webmanifest' || file.startsWith('workbox-')) {
        copyFileSync(resolve(buildDir, file), resolve(publicDir, file));
        console.log(`copied ${file} -> public/${file}`);
    } else if (file === 'sw.js') {
        // Its precache URLs are relative to the SW's own location
        // (Workbox's convention), e.g. "assets/app-xxx.css" — correct
        // when served from public/build/, but wrong once copied to the
        // public root, where that same file actually lives at
        // build/assets/app-xxx.css. Rewrite just that one reference.
        const contents = readFileSync(resolve(buildDir, file), 'utf8')
            .replaceAll('url:"assets/', 'url:"build/assets/');
        writeFileSync(resolve(publicDir, file), contents);
        console.log(`copied ${file} -> public/${file} (rewrote asset paths)`);
    }
}
