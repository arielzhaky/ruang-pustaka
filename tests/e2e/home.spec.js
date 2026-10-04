import { test, expect } from '@playwright/test';

test('Ruang-Pustaka dapat dibuka', async ({ page }) => {
    await page.goto('http://127.0.0.1:8000');

    await expect(page).toHaveTitle(/.+/);
});