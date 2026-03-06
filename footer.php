<?php if(!empty($main_open)) : ?>
</main>
<?php endif; ?>
<footer class="footer bg-gray-800 dark:bg-dark-card text-gray-200 dark:text-gray-300 py-8 border-t border-gray-700 dark:border-gray-600 shadow-lg transition-colors duration-300">
  <div class="max-w-6xl mx-auto py-5 px-4">
    <div class="grid grid-cols-1 md:grid-cols-5 gap-5 mb-5">
      <!-- About Section -->
      <div class="md:col-span-1">
        <div class="footer-section">
<h5 class="footer-title mb-3 text-white dark:text-white"><img src="assets/Footer Logo.png" alt="Vikrayam" class="h-10 w-auto"></h5>
          <p class="footer-text mb-3 text-gray-300 dark:text-gray-400">Your trusted marketplace for buying, selling, and discovering amazing deals in your local community.</p>
          <div class="social-links">
            <a href="https://facebook.com" target="_blank" class="social-icon text-gray-400 hover:text-blue-400" title="Facebook"><i class="bi bi-facebook"></i></a>
            <a href="https://twitter.com" target="_blank" class="social-icon text-gray-400 hover:text-blue-400" title="Twitter"><i class="bi bi-twitter"></i></a>
            <a href="https://instagram.com" target="_blank" class="social-icon text-gray-400 hover:text-pink-400" title="Instagram"><i class="bi bi-instagram"></i></a>
            <a href="https://linkedin.com" target="_blank" class="social-icon text-gray-400 hover:text-blue-500" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
          </div>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="md:col-span-1">
        <div class="footer-section">
          <h5 class="footer-title mb-3 text-white dark:text-white"><i class="bi bi-link-45deg"></i> Quick Links</h5>
          <ul class="list-unstyled footer-list">
            <li><a href="index" class="text-gray-400 dark:text-gray-400 hover:text-blue-400 dark:hover:text-blue-400"><i class="bi bi-chevron-right"></i> Home</a></li>
            <li><a href="create_listing" class="text-gray-400 dark:text-gray-400 hover:text-blue-400 dark:hover:text-blue-400"><i class="bi bi-chevron-right"></i> Post Listing</a></li>
            <li><a href="my_listings" class="text-gray-400 dark:text-gray-400 hover:text-blue-400 dark:hover:text-blue-400"><i class="bi bi-chevron-right"></i> My Listings</a></li>
            <li><a href="favorites" class="text-gray-400 dark:text-gray-400 hover:text-blue-400 dark:hover:text-blue-400"><i class="bi bi-chevron-right"></i> Favorites</a></li>
            <li><a href="index" class="text-gray-400 dark:text-gray-400 hover:text-blue-400 dark:hover:text-blue-400"><i class="bi bi-chevron-right"></i> Browse All</a></li>
          </ul>
        </div>
      </div>

      <!-- Categories -->
      <div class="md:col-span-1">
        <div class="footer-section">
          <h5 class="footer-title mb-3 text-white dark:text-white"><i class="bi bi-tags"></i> Categories</h5>
          <ul class="list-unstyled footer-list">
            <?php foreach($pdo->query("SELECT id,name FROM categories LIMIT 5")->fetchAll() as $catLink): ?>
              <li><a href="index?cat=<?=$catLink['id']?>" class="text-gray-400 dark:text-gray-400 hover:text-blue-400 dark:hover:text-blue-400"><i class="bi bi-chevron-right"></i> <?=htmlspecialchars($catLink['name'])?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <!-- Contact & Support -->
      <div class="md:col-span-1">
        <div class="footer-section">
          <h5 class="footer-title mb-3 text-white dark:text-white"><i class="bi bi-headset"></i> Support</h5>
          <ul class="list-unstyled footer-list">
            <li><a href="help" class="text-gray-400 dark:text-gray-400 hover:text-blue-400 dark:hover:text-blue-400"><i class="bi bi-chevron-right"></i> Help Center</a></li>
            <li><a href="safety" class="text-gray-400 dark:text-gray-400 hover:text-blue-400 dark:hover:text-blue-400"><i class="bi bi-chevron-right"></i> Safety Tips</a></li>
            <li><a href="terms" class="text-gray-400 dark:text-gray-400 hover:text-blue-400 dark:hover:text-blue-400"><i class="bi bi-chevron-right"></i> Terms & Conditions</a></li>
            <li><a href="privacy" class="text-gray-400 dark:text-gray-400 hover:text-blue-400 dark:hover:text-blue-400"><i class="bi bi-chevron-right"></i> Privacy Policy</a></li>
            <li><a href="contact" class="text-gray-400 dark:text-gray-400 hover:text-blue-400 dark:hover:text-blue-400"><i class="bi bi-chevron-right"></i> Contact Us</a></li>
          </ul>
        </div>
      </div>

      <!-- Newsletter -->
      <div class="md:col-span-1">
        <div class="footer-section">
          <h5 class="footer-title mb-3 text-white dark:text-white"><i class="bi bi-envelope"></i> Newsletter</h5>
          <p class="footer-text small mb-3 text-gray-300 dark:text-gray-400">Subscribe to get the latest deals and listings delivered to your inbox.</p>
          <form class="newsletter-form">
            <div class="input-group">
              <input type="email" class="form-control newsletter-input bg-gray-700 dark:bg-gray-600 border border-gray-600 dark:border-gray-500 text-white placeholder-gray-400" placeholder="Your email" required>
              <button class="px-3 py-1 bg-blue-600 text-white newsletter-btn rounded hover:bg-blue-700 transition" type="submit"><i class="bi bi-send"></i></button>
            </div>
          </form>
          <small class="footer-text mt-2 text-gray-400 dark:text-gray-500">We respect your privacy. Unsubscribe anytime.</small>
        </div>
      </div>
    </div>

    <!-- Divider -->
    <hr class="footer-divider border-gray-700 dark:border-gray-600">

    <!-- Bottom Footer -->
    <div class="flex flex-col md:flex-row md:justify-between items-center">
      <div class="text-center md:text-left">
<p class="footer-text mb-0 text-gray-400 dark:text-gray-400">&copy; 2026 Vikrayam. All rights reserved.</p>
      </div>
      <div class="text-center md:text-right">
        <p class="footer-text mb-0 text-gray-400 dark:text-gray-400">Made with <i class="bi bi-heart-fill" style="color:#e74c3c;"></i> by Your Team</p>
      </div>
    </div>
  </div>
  <!-- Back to Top Button -->
  <div class="back-to-top" id="backToTop" title="Back to top">
    <i class="bi bi-arrow-up"></i>
  </div>
</footer>
<style>
  .back-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #4a90e2, #17a2b8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.3s, transform 0.3s;
    box-shadow: 0 4px 12px rgba(74, 144, 226, 0.4);
    z-index: 999;
    color: white;
    font-size: 1.2rem;
  }
  .back-to-top.show {
    opacity: 1;
  }
  .back-to-top:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(74, 144, 226, 0.6);
  }
  @media (max-width: 768px) {
    .back-to-top {
      bottom: 20px;
      right: 20px;
      width: 40px;
      height: 40px;
      font-size: 1rem;
    }
  }
</style>
<script>
// Back to top button functionality
const backToTopBtn = document.getElementById('backToTop');
if (backToTopBtn) {
  window.addEventListener('scroll', () => {
    if (window.pageYOffset > 300) {
      backToTopBtn.classList.add('show');
    } else {
      backToTopBtn.classList.remove('show');
    }
  });

  backToTopBtn.addEventListener('click', () => {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  });
}

// intersection observer to fade elements in when they scroll into view
document.addEventListener('DOMContentLoaded', function() {
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.card, .footer-section').forEach(el => {
    el.classList.add('fade-in');
    observer.observe(el);
  });
});

// mobile nav toggle
(function(){
  const navToggle = document.getElementById('navToggle');
  const navLinks = document.getElementById('navLinks');
  if (navToggle && navLinks) {
    navLinks.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => {
        const cb = document.getElementById('navToggleCheckbox');
        if (cb) cb.checked = false;
      });
    });
  }
})();

// newsletter submission handler
(function(){
  const form = document.querySelector('.newsletter-form');
  if(form){
    form.addEventListener('submit', e=>{
      e.preventDefault();
      const input = form.querySelector('.newsletter-input');
      if(input && input.checkValidity()){
        alert('Thank you for subscribing!');
        input.value='';
      }
    });
  }
})();
</script>
</body>
</html>
