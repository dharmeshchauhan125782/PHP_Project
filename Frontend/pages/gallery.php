<?php
require_once __DIR__ . '/../../Backend/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gallery — LuxuryStay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Frontend/css/style.css">
<style>
  .masonry { columns: 3 220px; column-gap: 14px; }
  .masonry img { width: 100%; border-radius: var(--radius-md); margin-bottom: 14px; box-shadow: var(--shadow-sm); }
  @media (max-width: 700px) { .masonry { columns: 2 160px; } }
</style>
</head>
<body>
<?php include __DIR__ . '/../components/header.php'; ?>

<section class="section-tight" style="padding-top:40px;">
    <div class="container">
        <div class="eyebrow">Gallery</div>
        <h1 style="font-size:2.4rem;">A Glimpse Inside</h1>
        <p style="max-width:560px;">Rooms, common spaces, and moments from LuxuryStay.</p>
    </div>
</section>

<section class="section-tight">
    <div class="container">
        <div class="masonry" id="gallery-grid">
            <div class="skeleton" style="height:200px;"></div>
            <div class="skeleton" style="height:280px;"></div>
            <div class="skeleton" style="height:220px;"></div>
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

async function loadGallery() {
  const res = await apiGet('/api/gallery_list.php');
  const grid = document.getElementById('gallery-grid');
  if (!res.success || !res.data.images.length) {
    // Fall back to room photos so the page never looks broken
    const roomsRes = await apiGet('/api/rooms_list.php');
    const rooms = (roomsRes.data && roomsRes.data.rooms) || [];
    if (!rooms.length) { grid.innerHTML = '<div class="empty-state"><p>Gallery coming soon.</p></div>'; return; }
    grid.innerHTML = rooms.slice(0, 12).map(r => {
      const img = r.cover_image ? `${window.__BASE_URL__ || ""}/Frontend/images/rooms/${r.cover_image}` : 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=500&q=80';
      return `<img src="${img}" onerror="this.src='https://images.unsplash.com/photo-1590490360182-c33d57733427?w=500&q=80'" alt="${escapeHtml(r.room_type)}">`;
    }).join('');
    return;
  }
  grid.innerHTML = res.data.images.map(g => `<img src="${g.image_path || 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=500&q=80'}" alt="${escapeHtml(g.title)}">`).join('');
}
loadGallery();
</script>
</body>
</html>
