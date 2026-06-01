<?php
declare(strict_types=1);

$pageTitle = 'About Us - Campus Market';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
?>

<section class="py-5 bg-light border-bottom">
  <div class="container text-center">
    <h6 class="text-primary fw-bold text-uppercase mb-3">Our Story</h6>
    <h1 class="display-4 fw-bold mb-3">Empowering Student Innovation</h1>
    <p class="lead text-muted mx-auto" style="max-width: 700px;">We believe every student deserves affordable access to the tools they need to build the future.</p>
  </div>
</section>

<div class="container py-5">
  <div class="row align-items-center g-5 mb-5">
    <div class="col-lg-6">
      <img src="img/mission.avif" alt="Students Working" class="img-fluid rounded-4 shadow">
    </div>
    <div class="col-lg-6">
      <h2 class="fw-bold mb-4">Our Mission</h2>
      <p class="lead text-muted mb-4">Started in a dorm room in 2026, Campus Market addressed a simple problem: engineering supplies are expensive and often go to waste after one semester.</p>
      <p class="text-muted">We created a safe, campus-centric platform where students can buy, sell, and trade electronics, textbooks, and project materials. By keeping resources circulating within the community, we save students money and reduce waste.</p>
      <div class="row g-4 mt-2">
        <div class="col-sm-6"><div class="d-flex align-items-center"><i class="fas fa-check-circle text-primary me-2"></i><span class="fw-bold">Affordable Tools</span></div></div>
        <div class="col-sm-6"><div class="d-flex align-items-center"><i class="fas fa-check-circle text-primary me-2"></i><span class="fw-bold">Campus-Centric</span></div></div>
        <div class="col-sm-6"><div class="d-flex align-items-center"><i class="fas fa-check-circle text-primary me-2"></i><span class="fw-bold">Eco-Friendly</span></div></div>
        <div class="col-sm-6"><div class="d-flex align-items-center"><i class="fas fa-check-circle text-primary me-2"></i><span class="fw-bold">Verified Profiles</span></div></div>
      </div>
    </div>
  </div>

  <div class="row align-items-center g-5 mt-5">
    <div class="col-lg-6 order-lg-2">
      <img src="img/vision.avif" alt="Students Collaborating" class="img-fluid rounded-4 shadow">
    </div>
    <div class="col-lg-6 order-lg-1">
      <h2 class="fw-bold mb-4">Our Vision</h2>
      <p class="lead text-muted mb-4">We envision a future where every student has access to affordable tools and resources that empower them to innovate, create, and succeed in their academic journey.</p>
      <p class="text-muted">Through building a sustainable ecosystem of sharing and collaboration, we aim to foster innovation across campuses nationwide.</p>
      <div class="row g-4 mt-2">
        <div class="col-sm-6"><div class="d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i><span class="fw-bold">Innovation For All</span></div></div>
        <div class="col-sm-6"><div class="d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i><span class="fw-bold">Sustainable Future</span></div></div>
        <div class="col-sm-6"><div class="d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i><span class="fw-bold">Community Building</span></div></div>
        <div class="col-sm-6"><div class="d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i><span class="fw-bold">Reduced Waste</span></div></div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

