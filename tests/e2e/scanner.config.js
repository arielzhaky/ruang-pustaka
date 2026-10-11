import { defineConfig } from '@playwright/test';
import { existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
const localChrome = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
export default defineConfig({
    testDir: '.', testMatch: 'scanner.spec.js', timeout: 45000, workers: 1,
    use: { baseURL: 'http://127.0.0.1:8099', viewport: { width: 1100, height: 900 } },
    projects: [
        { name: 'chromium', use: { browserName: 'chromium', launchOptions: { ...(existsSync(localChrome) ? { executablePath: localChrome } : {}) } } },
        { name: 'webkit', use: { browserName: 'webkit' } },
    ],
    webServer: { command: 'php -S 127.0.0.1:8099 -t public tests/Support/scanner-router.php', cwd: fileURLToPath(new URL('../../', import.meta.url)), url: 'http://127.0.0.1:8099/scanner-test', reuseExistingServer: false },
    outputDir: '../../test-results/scanner', reporter: 'list',
});
