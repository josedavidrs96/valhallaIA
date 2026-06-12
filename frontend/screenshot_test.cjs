const { chromium } = require('/tmp/node_modules/playwright-core');

(async () => {
  const browser = await chromium.launch({
    executablePath: '/usr/bin/chromium-browser',
    args: ['--no-sandbox','--disable-setuid-sandbox','--disable-dev-shm-usage','--disable-gpu',
           '--host-resolver-rules=MAP localhost 172.18.0.5']
  });
  const page = await browser.newPage();
  await page.setViewportSize({ width: 1280, height: 800 });
  const BASE = 'http://172.18.0.2:5173';

  page.on('response', function(resp) {
    if (resp.url().includes('/api/') && resp.status() >= 400) {
      console.log('[API ' + resp.status() + '] ' + resp.url().replace('http://localhost:8000', ''));
    }
  });

  async function navTo(path, wait) {
    await page.goto(BASE + path, { waitUntil: 'networkidle', timeout: 15000 });
    await page.waitForTimeout(wait || 1000);
  }

  // ── 01 Public home ──────────────────────────────────────────────────────────
  await navTo('/', 1500);
  await page.screenshot({ path: '/app/frontend/public/ss-01-public.png' });
  console.log('OK 01 Public home');

  // ── 02 Login page ───────────────────────────────────────────────────────────
  await navTo('/login', 500);
  await page.screenshot({ path: '/app/frontend/public/ss-02-login.png' });
  console.log('OK 02 Login page');

  // ── 03 Admin login ──────────────────────────────────────────────────────────
  await page.fill('input[type="email"]', 'admin@valhallagym.com');
  await page.fill('input[type="password"]', 'Admin1234!');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/admin/**', { timeout: 8000 });
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: '/app/frontend/public/ss-03-admin.png' });
  console.log('OK 03 Admin [' + page.url().replace(BASE, '') + ']');

  // ── 04 Admin socios ─────────────────────────────────────────────────────────
  await page.screenshot({ path: '/app/frontend/public/ss-04-admin-socios.png' });
  console.log('OK 04 Admin socios');

  // ── 05 Admin clases ─────────────────────────────────────────────────────────
  await navTo('/admin/clases', 1000);
  await page.screenshot({ path: '/app/frontend/public/ss-05-admin-clases.png' });
  console.log('OK 05 Admin clases');

  // ── 06 Admin pagos ──────────────────────────────────────────────────────────
  await navTo('/admin/pagos', 1500);
  await page.screenshot({ path: '/app/frontend/public/ss-06-admin-pagos.png' });
  console.log('OK 06 Admin pagos');

  // ── 07 Admin morosos ─────────────────────────────────────────────────────────
  await navTo('/admin/pagos/morosos', 1500);
  await page.screenshot({ path: '/app/frontend/public/ss-07-admin-morosos.png' });
  console.log('OK 07 Admin morosos');

  // ── Logout admin — force clear auth ─────────────────────────────────────────
  await page.evaluate(function() { localStorage.clear(); sessionStorage.clear(); });

  // ── 08 Member login ─────────────────────────────────────────────────────────
  await navTo('/login', 500);
  await page.fill('input[type="email"]', 'carlos@test.com');
  await page.fill('input[type="password"]', 'NuevaSocio2026!');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/socio/**', { timeout: 8000 });
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: '/app/frontend/public/ss-08-member-home.png' });
  console.log('OK 08 Member [' + page.url().replace(BASE, '') + ']');

  // ── 09 Horario — cuota semanal ───────────────────────────────────────────────
  await navTo('/socio/horario', 1000);
  await page.waitForSelector('button:has-text("Reservar")', { timeout: 10000 }).catch(function(){});
  await page.waitForTimeout(2000);
  await page.screenshot({ path: '/app/frontend/public/ss-09-member-horario.png' });
  var quotaBadge = await page.locator('text=/Clases esta semana/i').count();
  var reservarCount = await page.locator('button:has-text("Reservar")').count();
  var limitBadge = await page.locator('text=/Limite semanal/i').count();
  var noSessions = await page.locator('text=/No hay sesiones/i').count();
  console.log('OK 09 Horario [' + page.url().replace(BASE, '') + '] ' +
    '(quota=' + quotaBadge + ' reservar=' + reservarCount + ' limit=' + limitBadge +
    ' noSessions=' + noSessions + ')');

  // ── 10 Reservar primera clase disponible ─────────────────────────────────────
  if (reservarCount > 0) {
    var reservarBtns = page.locator('button:has-text("Reservar")');
    var count = await reservarBtns.count();
    var booked = false;
    for (var i = 0; i < count && !booked; i++) {
      var btn = reservarBtns.nth(i);
      if (!(await btn.isDisabled())) {
        await btn.click();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1500);
        booked = true;
        break;
      }
    }
    await page.screenshot({ path: '/app/frontend/public/ss-10-after-booking.png' });
    var quotaText = await page.locator('text=/Clases esta semana/i').locator('..').textContent().catch(function() { return ''; });
    console.log('OK 10 Booking (booked=' + booked + ') badge: ' + quotaText.replace(/\s+/g, ' ').trim());
  } else {
    await page.screenshot({ path: '/app/frontend/public/ss-10-after-booking.png' });
    console.log('SKIP 10: no Reservar buttons');
  }

  // ── 11 Mis reservas — session_date, cuota, Finalizada, Cancelar ──────────────
  await navTo('/socio/reservas', 1000);
  await page.waitForSelector('table, text=/No tienes reservas/i', { timeout: 10000 }).catch(function(){});
  await page.waitForTimeout(2000);
  await page.screenshot({ path: '/app/frontend/public/ss-11-member-reservas.png' });
  var weeklyCounter = await page.locator('text=/Esta semana/i').count();
  var finalizadas   = await page.locator('td:has-text("Finalizada")').count();
  var cancelarBtns  = await page.locator('button:has-text("Cancelar")').count();
  var noReservas    = await page.locator('text=/No tienes reservas/i').count();
  console.log('OK 11 Reservas (weekly counter=' + weeklyCounter +
    ', finalizadas=' + finalizadas + ', cancelar=' + cancelarBtns +
    ', noReservas=' + noReservas + ')');

  // ── 12 Mis pagos ────────────────────────────────────────────────────────────
  await navTo('/socio/pagos', 1500);
  await page.screenshot({ path: '/app/frontend/public/ss-12-member-pagos.png' });
  console.log('OK 12 Member pagos [' + page.url().replace(BASE, '') + ']');

  await browser.close();
  console.log('ALL DONE');
})().catch(function(e) { console.error('FAIL: ' + e.message); process.exit(1); });
