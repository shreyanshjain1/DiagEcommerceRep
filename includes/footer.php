</main>

<?php
  require_once __DIR__.'/settings.php';
  $whatsapp = (string)setting('contact_whatsapp', '+639453462354');
  $hotline = (string)setting('contact_hotline', '+639691229230');
  $contactEmail = (string)setting('contact_email', $ADMIN_EMAIL);
?>

<a class="float-whatsapp" href="<?php echo e(wa_link($whatsapp)); ?>" target="_blank" rel="noopener">
  <span class="wa-dot"></span>
  <span>Chat on WhatsApp</span>
</a>

<button class="to-top" type="button" data-to-top aria-label="Back to top">↑</button>

<footer class="footer">
  <div class="container">
    <div class="footer-top">
      <div>
        <div class="footer-brand">
          <img src="<?php echo asset('assets/img/logo.png'); ?>" alt="Pharmastar" class="footer-logo">
          <div>
            <div class="footer-name">Pharmastar Diagnostics</div>
            <div class="footer-tagline">Reliable analyzers, reagents, and after-sales support in the Philippines.</div>
          </div>
        </div>
      </div>

      <div>
        <h4>Shop</h4>
        <ul>
          <li><a href="<?php echo url('pages/products.php'); ?>">All Products</a></li>
          <li><a href="<?php echo url('pages/compare.php'); ?>">Compare</a></li>
          <li><a href="<?php echo url('pages/wishlist.php'); ?>">Wishlist</a></li>
          <li><a href="<?php echo url('pages/cart.php'); ?>">Cart</a></li>
        </ul>
      </div>

      <div>
        <h4>Support</h4>
        <ul>
          <li><a href="<?php echo url('pages/inquiry.php'); ?>">Request a Quote</a></li>
          <li><a href="<?php echo url('pages/terms.php'); ?>">Terms</a></li>
          <li><a href="<?php echo url('pages/privacy.php'); ?>">Privacy</a></li>
          <li><a href="<?php echo url('pages/returns.php'); ?>">Warranty / Returns</a></li>
        </ul>
      </div>

      <div>
        <h4>Contact</h4>
        <p class="footer-contact">
          Email: <a href="mailto:<?php echo e($contactEmail); ?>"><?php echo e($contactEmail); ?></a><br>
          Hotline: <a href="tel:<?php echo e($hotline); ?>"><?php echo e($hotline); ?></a><br>
          WhatsApp: <a href="<?php echo e(wa_link($whatsapp)); ?>" target="_blank" rel="noopener"><?php echo e($whatsapp); ?></a>
        </p>
      </div>
    </div>

    <div class="footer-bottom">
      <div>© <?php echo date('Y'); ?> Pharmastar Int'l Trading Corp.</div>
      <div class="footer-mini">
        <a href="<?php echo url('pages/login.php'); ?>">Login</a>
        <span class="sep">•</span>
        <a href="<?php echo url('pages/signup.php'); ?>">Create account</a>
      </div>
    </div>
  </div>
</footer>
</body>
</html>
