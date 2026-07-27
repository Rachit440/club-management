<?php
/**
 * Elite Club Management Portal - Landing page (home).
 * Logged-in users are redirected to the dashboard; everyone else sees the
 * marketing landing page with hero, about, features, benefits, stats,
 * testimonials, gallery, FAQ, contact and footer.
 */
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) { redirect(APP_URL . '/dashboard.php'); }

$clubName = get_setting('club_name', 'Elite Club');
$siteName = get_setting('site_name', 'Elite Club Management Portal');
$features = [
  ['fa-users', 'Member Management', 'Add, edit and track every member with rich profiles, photos and membership history.'],
  ['fa-layer-group', 'Membership Plans', 'Flexible monthly, quarterly and yearly plans with auto-calculated expiry dates.'],
  ['fa-money-bill-wave', 'Payments & Receipts', 'Record payments, auto-generate receipt numbers and print professional invoices.'],
  ['fa-calendar-check', 'Attendance Tracking', 'Mark daily check-ins with automatic late detection and detailed reports.'],
  ['fa-calendar-day', 'Events & Registration', 'Create events and let members register themselves in real time.'],
  ['fa-chart-pie', 'Insightful Reports', 'Export members, payments, attendance and revenue as CSV or print.'],
];
$benefits = [
  ['fa-dumbbell', 'Premium Facilities', 'State-of-the-art gym, studios & lounge access.'],
  ['fa-user-tie', 'Expert Trainers', 'Certified coaches to guide your journey.'],
  ['fa-spa', 'Wellness Programs', 'Yoga, meditation & nutrition planning.'],
  ['fa-clock', '24/7 Access', 'Train on your schedule, any time of day.'],
];
$testimonials = [
  ['The portal transformed how we manage our 500+ members. Everything is one click away.', 'Aarav Mehta', 'Club President'],
  ['Beautiful, fast and so easy to use. Our members love the new dashboard.', 'Sara Khan', 'Membership Coordinator'],
  ['Reporting and payments became effortless. Best decision we made this year.', 'David Chen', 'Operations Manager'],
];
$faqs = [
  ['How do I become a member?', 'Visit the login page and contact the club admin to create your account, or register at the front desk. Once added, you can sign in with your email and password.'],
  ['Can I change my membership plan?', 'Yes. Speak to the club admin who can update your plan and recalculate your expiry date instantly.'],
  ['How do I pay my membership fee?', 'Payments are recorded by the admin and appear in your My Payments page with downloadable receipts.'],
  ['Is my data secure?', 'Yes. Passwords are hashed, all queries use prepared statements and access is role-restricted.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($siteName) ?> - Premium Club Management</title>
  <meta name="description" content="Elite Club Management Portal - a modern platform to manage members, memberships, payments, events and attendance.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='24' fill='%234F46E5'/><text x='50' y='68' font-size='58' text-anchor='middle' fill='white' font-family='sans-serif' font-weight='bold'>E</text></svg>">
  <script>window.APP_URL = <?= json_encode(APP_URL) ?>;</script>
</head>
<body class="landing">
<div class="boot-screen" id="bootScreen">
  <div class="logo"><i class="fa-solid fa-gem"></i></div>
  <div class="bar"><i></i></div>
  <div class="name">ELITE CLUB</div>
</div>

<nav class="lnav" id="lnav">
  <a class="brand" href="#top"><span class="mark"><i class="fa-solid fa-gem"></i></span> <?= e($clubName) ?></a>
  <div class="links">
    <a href="#about">About</a><a href="#features">Features</a><a href="#benefits">Benefits</a>
    <a href="#faq">FAQ</a><a href="#contact">Contact</a>
    <a href="<?= APP_URL ?>/login.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-right-to-bracket"></i> Sign In</a>
  </div>
</nav>

<!-- Hero -->
<header class="hero" id="top">
  <div class="blob a"></div><div class="blob b"></div>
  <div class="hero-grid">
    <div data-reveal>
      <span class="badge badge-primary mb-2"><i class="fa-solid fa-sparkles"></i> Next-gen club management</span>
      <h1>Run your club <span class="grad">the smart way</span> with Elite Club Portal</h1>
      <p class="lead">A premium, all-in-one platform to manage members, memberships, payments, events and attendance - beautifully designed and effortless to use.</p>
      <div class="cta">
        <a href="<?= APP_URL ?>/login.php" class="btn btn-primary btn-lg"><i class="fa-solid fa-rocket"></i> Get Started</a>
        <a href="#features" class="btn btn-secondary btn-lg"><i class="fa-solid fa-circle-play"></i> Explore Features</a>
      </div>
    </div>
    <div class="hero-card" data-reveal>
      <div class="mini-stat"><div class="ic" style="background:linear-gradient(135deg,#4F46E5,#6366f1)"><i class="fa-solid fa-users"></i></div><div><b style="font-family:var(--font-head);font-size:1.2rem" data-counter="2480">0</b><div class="muted" style="font-size:.8rem">Active members</div></div></div>
      <div class="mini-stat"><div class="ic" style="background:linear-gradient(135deg,#06B6D4,#0891b2)"><i class="fa-solid fa-money-bill-wave"></i></div><div><b style="font-family:var(--font-head);font-size:1.2rem" data-counter="86400" data-prefix="$">0</b><div class="muted" style="font-size:.8rem">Monthly revenue</div></div></div>
      <div class="mini-stat"><div class="ic" style="background:linear-gradient(135deg,#22C55E,#16a34a)"><i class="fa-solid fa-calendar-check"></i></div><div><b style="font-family:var(--font-head);font-size:1.2rem" data-counter="98" data-suffix="%">0</b><div class="muted" style="font-size:.8rem">Attendance rate</div></div></div>
    </div>
  </div>
</header>

<!-- About -->
<section id="about">
  <div class="container">
    <div class="sec-head" data-reveal>
      <span class="eyebrow">About the club</span>
      <h2>Where excellence meets community</h2>
      <p><?= e($clubName) ?> is more than a club - it's a lifestyle. Our portal brings every aspect of membership management into one elegant, modern experience.</p>
    </div>
    <div class="features-grid" style="grid-template-columns:repeat(3,1fr)">
      <div class="feature-card" data-reveal><div class="ic"><i class="fa-solid fa-medal"></i></div><h3>Award-winning facilities</h3><p>Premium equipment, studios and recovery zones designed for serious athletes and beginners alike.</p></div>
      <div class="feature-card" data-reveal><div class="ic"><i class="fa-solid fa-people-group"></i></div><h3>Vibrant community</h3><p>Connect with like-minded members through events, classes and exclusive club gatherings.</p></div>
      <div class="feature-card" data-reveal><div class="ic"><i class="fa-solid fa-shield-halved"></i></div><h3>Secure &amp; private</h3><p>Your data is protected with industry-standard security and role-based access control.</p></div>
    </div>
  </div>
</section>

<!-- Features -->
<section id="features" style="background:linear-gradient(180deg,transparent,rgba(79,70,229,.03))">
  <div class="container">
    <div class="sec-head" data-reveal>
      <span class="eyebrow">Everything you need</span>
      <h2>Powerful features, beautifully simple</h2>
      <p>From the first member to your ten-thousandth, the portal scales with your club.</p>
    </div>
    <div class="features-grid">
      <?php foreach ($features as $f): ?>
      <div class="feature-card" data-reveal><div class="ic"><i class="fa-solid <?= $f[0] ?>"></i></div><h3><?= e($f[1]) ?></h3><p><?= e($f[2]) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Benefits -->
<section id="benefits">
  <div class="container">
    <div class="sec-head" data-reveal>
      <span class="eyebrow">Membership benefits</span>
      <h2>Perks that keep members coming back</h2>
    </div>
    <div class="benefits-grid">
      <?php foreach ($benefits as $b): ?>
      <div class="benefit-card" data-reveal><div class="ic"><i class="fa-solid <?= $b[0] ?>"></i></div><h4><?= e($b[1]) ?></h4><p class="muted" style="font-size:.85rem"><?= e($b[2]) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Statistics -->
<section>
  <div class="container">
    <div class="stats-strip" data-reveal>
      <div class="stat"><b data-counter="2480">0</b><span>Happy Members</span></div>
      <div class="stat"><b data-counter="120">0</b><span>Monthly Events</span></div>
      <div class="stat"><b data-counter="15" data-suffix="+">0</b><span>Years of Excellence</span></div>
      <div class="stat"><b data-counter="98" data-suffix="%">0</b><span>Member Satisfaction</span></div>
    </div>
  </div>
</section>

<!-- Testimonials -->
<section style="background:linear-gradient(180deg,transparent,rgba(6,182,212,.03))">
  <div class="container">
    <div class="sec-head" data-reveal>
      <span class="eyebrow">Testimonials</span>
      <h2>Loved by clubs worldwide</h2>
    </div>
    <div class="testimonials-grid">
      <?php foreach ($testimonials as $t): ?>
      <div class="testimonial-card" data-reveal><div class="quote">&ldquo;</div><p><?= e($t[0]) ?></p><div class="who"><img src="https://images.pexels.com/photos/220453/pexels-photo-220453.jpeg?auto=compress&cs=tinysrgb&w=120" alt=""><div><b><?= e($t[1]) ?></b><span><?= e($t[2]) ?></span></div></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Gallery -->
<section>
  <div class="container">
    <div class="sec-head" data-reveal><span class="eyebrow">Gallery</span><h2>A glimpse of our club</h2></div>
    <div class="gallery-grid">
      <div class="gitem" data-reveal><img src="https://images.pexels.com/photos/1954524/pexels-photo-1954524.jpeg?auto=compress&cs=tinysrgb&w=600" alt="Gym"></div>
      <div class="gitem" data-reveal><img src="https://images.pexels.com/photos/3823038/pexels-photo-3823038.jpeg?auto=compress&cs=tinysrgb&w=600" alt="Yoga"></div>
      <div class="gitem" data-reveal><img src="https://images.pexels.com/photos/28080/pexels-photo.jpg?auto=compress&cs=tinysrgb&w=600" alt="Pool"></div>
      <div class="gitem" data-reveal><img src="https://images.pexels.com/photos/841130/pexels-photo-841130.jpeg?auto=compress&cs=tinysrgb&w=600" alt="Training"></div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section id="faq" style="background:linear-gradient(180deg,transparent,rgba(79,70,229,.03))">
  <div class="container">
    <div class="sec-head" data-reveal><span class="eyebrow">FAQ</span><h2>Frequently asked questions</h2></div>
    <div class="faq-list">
      <?php foreach ($faqs as $f): ?>
      <div class="faq-item" data-reveal><div class="faq-q"><span><?= e($f[0]) ?></span><i class="fa-solid fa-chevron-down"></i></div><div class="faq-a"><p><?= e($f[1]) ?></p></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Contact -->
<section id="contact">
  <div class="container">
    <div class="sec-head" data-reveal><span class="eyebrow">Get in touch</span><h2>We'd love to hear from you</h2></div>
    <div class="contact-grid">
      <div class="contact-info" data-reveal>
        <div class="row"><div class="ic"><i class="fa-solid fa-location-dot"></i></div><div><b>Address</b><p class="muted" style="font-size:.9rem">123 Fitness Avenue, Downtown District, City 45678</p></div></div>
        <div class="row"><div class="ic"><i class="fa-solid fa-phone"></i></div><div><b>Phone</b><p class="muted" style="font-size:.9rem">+1 (555) 123-4567</p></div></div>
        <div class="row"><div class="ic"><i class="fa-solid fa-envelope"></i></div><div><b>Email</b><p class="muted" style="font-size:.9rem">hello@eliteclub.com</p></div></div>
        <div class="row"><div class="ic"><i class="fa-solid fa-clock"></i></div><div><b>Hours</b><p class="muted" style="font-size:.9rem">Open 24/7 for members</p></div></div>
      </div>
      <div class="card" data-reveal><div class="card-body">
        <form onsubmit="event.preventDefault();App.toast('Thanks! We will be in touch soon.','success');this.reset();">
          <div class="form-group floating input-icon"><i class="fa-solid fa-user"></i><input type="text" placeholder=" " required><label>Full name</label></div>
          <div class="form-group floating input-icon"><i class="fa-solid fa-envelope"></i><input type="email" placeholder=" " required><label>Email address</label></div>
          <div class="form-group"><textarea placeholder="Your message" required style="min-height:120px"></textarea></div>
          <button class="btn btn-primary btn-block btn-lg"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
        </form>
      </div></div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="lfoot">
  <div class="fgrid">
    <div>
      <div class="brand"><span class="mark"><i class="fa-solid fa-gem"></i></span> <?= e($clubName) ?></div>
      <p style="color:#94a3b8;font-size:.9rem;max-width:300px">A premium club management platform designed for modern clubs that care about their members.</p>
    </div>
    <div><h4>Platform</h4><a href="#features">Features</a><a href="#benefits">Benefits</a><a href="<?= APP_URL ?>/login.php">Sign In</a></div>
    <div><h4>Support</h4><a href="#faq">FAQ</a><a href="#contact">Contact</a><a href="#">Help Center</a></div>
    <div><h4>Connect</h4><a href="#"><i class="fa-brands fa-facebook"></i> Facebook</a><a href="#"><i class="fa-brands fa-instagram"></i> Instagram</a><a href="#"><i class="fa-brands fa-x-twitter"></i> Twitter</a></div>
  </div>
  <div class="copyright">&copy; <?= date('Y') ?> <?= e($clubName) ?>. Crafted with the Elite Club Management Portal.</div>
</footer>

<button class="back-top" aria-label="Back to top"><i class="fa-solid fa-arrow-up"></i></button>
<div class="toast-wrap"></div>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<script>
// Sticky nav on scroll
const nav = document.getElementById('lnav');
window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 30));
// FAQ accordion
document.querySelectorAll('.faq-q').forEach(q => q.addEventListener('click', () => q.parentElement.classList.toggle('open')));
</script>
</body>
</html>
