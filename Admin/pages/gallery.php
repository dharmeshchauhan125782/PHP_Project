<?php
require_once __DIR__ . '/../../Backend/includes/functions.php';
if (!isAdminLoggedIn()) { header('Location: /Admin/pages/login.php'); exit; }
$activeSection = 'gallery';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gallery — LuxuryStay Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Frontend/css/style.css">
<style>.gallery-item { position:relative; border-radius:12px; overflow:hidden; aspect-ratio:4/3; } .gallery-item img { width:100%; height:100%; object-fit:cover; } .gallery-item__del { position:absolute; top:8px; right:8px; }</style>
</head>
<body>
<div class="dash-shell">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>
  <main class="dash-main">
    <div class="dash-topbar">
      <div><h2 style="margin-bottom:4px;">Gallery</h2><p style="color:var(--ink-soft);">Manage public gallery photos and testimonials.</p></div>
      <button class="btn btn-brass" onclick="document.getElementById('gallery-modal').classList.add('open')">+ Add Item</button>
    </div>

    <div class="grid-4" id="gallery-grid"><div class="skeleton" style="height:180px;"></div></div>
  </main>
</div>

<div class="modal-overlay" id="gallery-modal">
  <div class="modal-box" style="max-width:480px;">
    <div class="modal-header"><h3>Add Gallery Item</h3><button class="modal-close" onclick="document.getElementById('gallery-modal').classList.remove('open')">&times;</button></div>
    <div class="modal-body">
      <div id="gallery-msg" class="form-msg"></div>
      <div class="form-group"><label>Title</label><input type="text" id="g-title"></div>
      <div class="form-group">
        <label>Category</label>
        <select id="g-category"><option value="general">General</option><option value="rooms">Rooms</option><option value="dining">Dining</option><option value="testimonial">Testimonial</option></select>
      </div>
      <div class="form-group" id="g-image-group"><label>Image</label><input type="file" id="g-image" accept="image/jpeg,image/png,image/webp"></div>
      <div class="form-group" id="g-testimonial-group" style="display:none;">
        <label>Testimonial Text</label><textarea id="g-testimonial-text" rows="3"></textarea>
        <label style="margin-top:10px;">Author Name</label><input type="text" id="g-testimonial-author">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="document.getElementById('gallery-modal').classList.remove('open')">Cancel</button>
      <button class="btn btn-brass" onclick="saveGalleryItem()">Save</button>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/Frontend/js/api.js"></script>
<script>
function escapeHtml(str) { const d = document.createElement('div'); d.textContent = str ?? ''; return d.innerHTML; }

document.getElementById('g-category').addEventListener('change', function() {
  const isTestimonial = this.value === 'testimonial';
  document.getElementById('g-testimonial-group').style.display = isTestimonial ? 'block' : 'none';
});

async function loadGallery() {
  const res = await fetch('<?= BASE_URL ?>/Backend/api/admin_gallery.php', { credentials: 'same-origin' }).then(r => r.json());
  if (!res.success) { toast(res.message, 'error'); return; }
  setCsrfToken(res.data.csrf_token);
  const grid = document.getElementById('gallery-grid');
  if (!res.data.items.length) { grid.innerHTML = '<div class="empty-state"><p>No gallery items yet.</p></div>'; return; }
  grid.innerHTML = res.data.items.map(g => {
    if (g.category === 'testimonial') {
      return `<div class="card testimonial-card" style="position:relative;">
        <button class="btn btn-danger btn-sm gallery-item__del" onclick="deleteGalleryItem(${g.id})">✕</button>
        <p>&ldquo;${escapeHtml(g.testimonial_text || '')}&rdquo;</p>
        <div class="testimonial-card__author">${escapeHtml(g.testimonial_author || '')}</div>
      </div>`;
    }
    return `<div class="gallery-item">
      <img src="${g.image_path || 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&q=80'}" alt="${escapeHtml(g.title)}">
      <button class="btn btn-danger btn-sm gallery-item__del" onclick="deleteGalleryItem(${g.id})">✕</button>
    </div>`;
  }).join('');
}

async function saveGalleryItem() {
  const msg = document.getElementById('gallery-msg');
  const title = document.getElementById('g-title').value.trim();
  if (!title) { msg.textContent = 'Title is required.'; msg.className = 'form-msg error'; return; }

  const fd = new FormData();
  fd.append('action', 'save');
  fd.append('title', title);
  fd.append('category', document.getElementById('g-category').value);
  fd.append('testimonial_text', document.getElementById('g-testimonial-text').value.trim());
  fd.append('testimonial_author', document.getElementById('g-testimonial-author').value.trim());
  const file = document.getElementById('g-image').files[0];
  if (file) fd.append('image', file);

  const res = await apiPostForm('/api/admin_gallery.php', fd);
  if (!res.success) { msg.textContent = res.message; msg.className = 'form-msg error'; return; }
  toast('Gallery item added.', 'success');
  document.getElementById('gallery-modal').classList.remove('open');
  document.getElementById('g-title').value = '';
  loadGallery();
}

async function deleteGalleryItem(id) {
  if (!confirm('Delete this gallery item?')) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id', id);
  const res = await apiPostForm('/api/admin_gallery.php', fd);
  if (res.success) { toast('Deleted.', 'success'); loadGallery(); } else toast(res.message, 'error');
}

loadGallery();
</script>
</body>
</html>
