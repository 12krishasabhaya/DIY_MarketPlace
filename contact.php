<?php
declare(strict_types=1);

$pageTitle = 'Contact Us - Campus Market';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
?>

<div class="container py-5 mt-4">
  <div class="row g-5">
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm p-4 h-100">
        <h3 class="fw-bold mb-4">Send us a Message</h3>
        <form method="post" action="feedback.php">
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">First Name</label>
              <input type="text" class="form-control" name="first_name" placeholder="John" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Last Name</label>
              <input type="text" class="form-control" name="last_name" placeholder="Doe" required>
            </div>
            <div class="col-12">
              <label class="form-label small fw-bold">Email Address</label>
              <input type="email" class="form-control" name="email" placeholder="john@college.edu" required>
            </div>
            <div class="col-12">
              <label class="form-label small fw-bold">Subject</label>
              <input type="text" class="form-control" name="subject" placeholder="How can we help?" required>
            </div>
            <div class="col-12">
              <label class="form-label small fw-bold">Message</label>
              <textarea class="form-control" rows="6" name="message" placeholder="Your message here..." required></textarea>
            </div>
            <div class="col-12 mt-4">
              <button type="submit" class="btn btn-primary btn-lg px-5">Send Message</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card border-0 bg-primary text-white p-4 p-lg-5 h-100 shadow-sm">
        <h3 class="fw-bold mb-4">Contact Information</h3>
        <div class="mb-4">
          <div class="d-flex align-items-center mb-3">
            <div class="bg-white bg-opacity-25 rounded-circle p-3 me-3"><i class="fas fa-map-marker-alt"></i></div>
            <h5 class="fw-bold mb-0">Our Location</h5>
          </div>
          <p class="mb-0">Engineering Block, Campus Road</p>
        </div>
        <div class="mb-4">
          <div class="d-flex align-items-center mb-3">
            <div class="bg-white bg-opacity-25 rounded-circle p-3 me-3"><i class="fas fa-envelope"></i></div>
            <h5 class="fw-bold mb-0">Email</h5>
          </div>
          <p class="mb-0">support@campusmarket.local</p>
        </div>
        <div class="mb-0">
          <div class="d-flex align-items-center mb-3">
            <div class="bg-white bg-opacity-25 rounded-circle p-3 me-3"><i class="fas fa-phone"></i></div>
            <h5 class="fw-bold mb-0">Phone</h5>
          </div>
          <p class="mb-0">+91 9876543210</p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

