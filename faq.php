<?php
declare(strict_types=1);

$pageTitle = 'FAQ - Campus Market';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
?>

<header class="bg-primary text-white py-5 mb-5">
  <div class="container text-center">
    <h1 class="display-4 fw-bold mb-3">Frequently Asked Questions</h1>
    <p class="lead mb-0">Everything you need to know about buying and selling on Campus Market.</p>
  </div>
</header>

<div class="container mb-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="accordion shadow-sm" id="faqAccordion">
        <div class="accordion-item border-0 mb-3">
          <h2 class="accordion-header">
            <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
              What is Campus Market?
            </button>
          </h2>
          <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
            <div class="accordion-body text-muted">
              Campus Market is a dedicated student marketplace where students can buy and sell used electronics, lab materials, dorm essentials, notes, and project components within the university community.
            </div>
          </div>
        </div>

        <div class="accordion-item border-0 mb-3">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
              How do I buy an item?
            </button>
          </h2>
          <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body text-muted">
              Login, browse products, add items to cart, and place an order from checkout. For the project demo, no online payment is processed.
            </div>
          </div>
        </div>

        <div class="accordion-item border-0 mb-3">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
              How do I sell an item?
            </button>
          </h2>
          <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body text-muted">
              Login and go to “Post Item”, fill details, upload a photo (optional), and publish your listing.
            </div>
          </div>
        </div>

        <div class="accordion-item border-0 mb-3">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
              Is it campus-only?
            </button>
          </h2>
          <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body text-muted">
              Yes. The project is designed for campus communities and student-to-student exchanges.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

