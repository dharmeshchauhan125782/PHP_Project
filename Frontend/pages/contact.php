<?php
require_once __DIR__ . '/../../Backend/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact — LuxuryStay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Frontend/css/style.css">
</head>
<body>
<?php include __DIR__ . '/../components/header.php'; ?>

<section class="section-tight" style="padding-top:40px;">
    <div class="container grid-2">
        <div>
            <div class="eyebrow">Get in Touch</div>
            <h1 style="font-size:2.4rem;">We'd Love to Hear From You</h1>
            <p style="max-width:440px;">Questions about a reservation, group bookings, or anything else — send us a note and our team will respond shortly.</p>

            <div style="margin-top:32px;">
                <p><strong>📍 Address</strong><br>42 Marina Promenade, Mumbai, India</p>
                <p><strong>📞 Phone</strong><br>+91 12345 67890</p>
                <p><strong>✉️ Email</strong><br>stay@luxurystay.example</p>
            </div>
        </div>
        <div class="card" style="padding:32px;">
            <div id="contact-msg" class="form-msg"></div>
            <form onsubmit="event.preventDefault(); submitContact();">
                <div class="form-group"><label>Name</label><input type="text" id="c-name" required></div>
                <div class="form-group"><label>Email</label><input type="email" id="c-email" required></div>
                <div class="form-group"><label>Message</label><textarea id="c-message" rows="5" required></textarea></div>
                <button type="submit" class="btn btn-brass btn-block" id="c-btn">Send Message</button>
            </form>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../components/footer.php'; ?>
<script>
async function submitContact() {
  const btn = document.getElementById('c-btn');
  const msg = document.getElementById('contact-msg');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner spinner-dark"></span> Sending…';

  const res = await apiPostJson('/api/contact_submit.php', {
    name: document.getElementById('c-name').value.trim(),
    email: document.getElementById('c-email').value.trim(),
    message: document.getElementById('c-message').value.trim(),
  });

  btn.disabled = false;
  btn.textContent = 'Send Message';
  msg.textContent = res.message;
  msg.className = 'form-msg ' + (res.success ? 'success' : 'error');
  if (res.success) document.querySelector('form').reset();
}
</script>
</body>
</html>
