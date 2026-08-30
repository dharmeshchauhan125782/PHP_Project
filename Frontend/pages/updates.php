<?php
require_once __DIR__ . '/../../Backend/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hotel Updates — LuxuryStay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Frontend/css/style.css">
<style>
  .announce-card { padding: 24px; margin-bottom: 16px; border-left: 3px solid var(--brass); max-width: 720px; }
  .announce-type { font-family: var(--font-mono); font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--brass-deep); }
</style>
</head>
<body>
<?php include __DIR__ . '/../components/header.php'; ?>

<section class="section-tight" style="padding-top:40px;">
    <div class="container">
        <div class="eyebrow">Authority Notices</div>
        <h1 style="font-size:2.4rem;">Hotel Updates</h1>
        <p style="max-width:560px;">Policy changes, maintenance notices, and news from the LuxuryStay team.</p>
    </div>
</section>

<section class="section-tight">
    <div class="container">
        <div id="updates-list">
            <div class="skeleton" style="height:100px; margin-bottom:16px; max-width:720px;"></div>
            <div class="skeleton" style="height:100px; margin-bottom:16px; max-width:720px;"></div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../components/footer.php'; ?>
<script>
function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

async function loadUpdates() {
  const res = await apiGet('/api/announcements_list.php');
  const list = document.getElementById('updates-list');
  if (!res.success || !res.data.announcements.length) {
    list.innerHTML = `<div class="empty-state"><div class="empty-state__icon">📣</div><p>No announcements right now. Check back soon.</p></div>`;
    return;
  }
  list.innerHTML = res.data.announcements.map(a => `
    <div class="card announce-card">
      <div class="announce-type">${escapeHtml(a.type)}</div>
      <h3 style="margin:6px 0;">${escapeHtml(a.title)}</h3>
      <p style="white-space:pre-line;">${escapeHtml(a.message)}</p>
      <div style="font-family:var(--font-mono); font-size:11.5px; color:var(--ink-soft);">By ${escapeHtml(a.published_by)} · ${new Date(a.created_at).toLocaleDateString('en-IN')}</div>
    </div>
  `).join('');
}
loadUpdates();
</script>
</body>
</html>
