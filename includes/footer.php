<?php /* Reusable minimal footer */ ?>
<style>
  .footer-min { background: #fff; border-top: 2px solid #e9e9e9; color: #222; margin-top: 24px; }
  .footer-min .wrap { padding: 36px 0 48px; }
  .footer-min .min-cols { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; align-items: start; }
  .footer-min h4 { font-size: 14px; font-weight: 800; letter-spacing: .4px; color: #222; text-transform: uppercase; margin: 0 0 14px; }
  .footer-min ul { list-style: none; padding: 0; margin: 0; display: grid; gap: 6px; line-height: 1.25; }
  .footer-min .list-2col { column-count: 2; column-gap: 24px; display: block; }
  .footer-min .list-2col li { break-inside: avoid; margin-bottom: 6px; }
  .footer-min a { color: #222; text-decoration: none; font-size: 13px; }
  .footer-min a:hover { text-decoration: underline; }
  .footer-min .contact li { color: #444; font-size: 13px; display: flex; align-items: center; gap: 8px; }
  .footer-min .ico { width: 20px; height: 20px; color: #222; display: inline-flex; }
  .footer-min .ico.wa {
    color: #fff;
    background: #25D366;
    border-radius: 50%;
    width: 22px; height: 22px;
    padding: 2px;
    box-sizing: content-box;
  }
  .footer-min .ico.mail { color: #222; }
  .footer-min .ico.phone { color: #222; }
  .footer-min .contact a { color: inherit; text-decoration: none; }
  .footer-min .contact a:hover { text-decoration: underline; }
  
  .footer-min .btn-outline { margin-top: 14px; padding: 8px 14px; border: 1px solid #ddd; color: #222; border-radius: 8px; background: #fff; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-block; }
  .footer-min .btn-outline:hover { background: #f6f6f6; }
  .footer-min .socials { display: flex; gap: 10px; align-items: center; }
  .footer-min .socials .ig { width: 28px; height: 28px; border-radius: 6px; border: 1px solid #eaeaea; display: inline-flex; align-items: center; justify-content: center; color: #222; }
  .footer-min .brand-small { display: flex; justify-content: center; margin-bottom: 18px; }
  .footer-min .brand-small img { height: 44px; width: auto; display: block; }
  @media (max-width: 900px) { 
    .footer-min .min-cols { grid-template-columns: 1fr; gap: 20px; }
    .footer-min .list-2col { column-count: 1; }
    .footer-min { text-align: center; }
    .footer-min h4 { text-align: center; }
    .footer-min .contact li { justify-content: center; }
    .footer-min .socials { justify-content: center; }
  }
</style>
<footer class="footer footer-min">
  <div class="container wrap">
    <div class="brand-small">
      <img src="uploads/flute_logo.png" alt="Flute Incensos" onerror="this.src='uploads/flute_logo.png'">
    </div>
    <div class="min-cols">
      <div>
        <h4>Institucional</h4>
        <ul class="list-2col">
          <li><a href="contato.php">Fale Conosco</a></li>
          <li><a href="#">Condições comerciais e frete</a></li>
          <li><a href="#">Contato</a></li>
          <li><a href="#">Política de privacidade</a></li>
          <li><a href="#">Política de trocas e devoluções</a></li>
          <li><a href="#">Quem somos</a></li>
        </ul>
      </div>
      <div>
        <h4>Atendimento</h4>
        <ul class="contact">
          <li>
            <svg class="ico phone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92V21a2 2 0 0 1-2.18 2A19.86 19.86 0 0 1 3 7.18 2 2 0 0 1 5 5h3.6a2 2 0 0 1 2 1.72l.32 2.1a2 2 0 0 1-.57 1.86l-1.1 1.1a16 16 0 0 0 6.36 6.36l1.1-1.1a2 2 0 0 1 1.86-.57l2.1.32A2 2 0 0 1 22 16.92z"></path></svg>
            <a href="tel:+5548996107541" aria-label="Ligar para (48) 99610-7541">(48) 99610-7541</a>
          </li>
          <li>
            <svg class="ico wa" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="12" cy="12" r="10" stroke="none"></circle>
              <path d="M16.5 15.5c-2 .6-5-2.2-5.6-3.7"/>
              <path d="M8.8 8.8l1.7-1.3"/>
              <path d="M12.3 13.7l1.3-1.7"/>
              <path d="M3 20l2-4 4 2-6 2z" stroke="none" fill="currentColor" opacity=".25"></path>
            </svg>
            <a href="https://wa.me/5548996107541" target="_blank" rel="noopener" aria-label="Falar no WhatsApp">Fale no WhatsApp</a>
          </li>
          
        </ul>
      </div>
      <div>
        <h4>Acompanhe nas redes</h4>
        <div class="socials">
          <a class="ig" href="https://instagram.com/" target="_blank" rel="noopener" aria-label="Instagram">
            <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
          </a>
        </div>
      </div>
    </div>
  </div>
</footer>

<script>
  (function() {
    function canonicalizeContactLinks() {
      var footerWa = document.querySelector('footer .contact a[href*="wa.me"], footer .contact a[href*="api.whatsapp.com"]');
      if (!footerWa) return;
      var waUrl = footerWa.getAttribute('href');
      if (!waUrl) return;

      var selectors = [
        'a.btn-whatsapp',
        'a[href*="wa.me"]',
        'a[href*="api.whatsapp.com"]',
        'a[href*="contato.php"]',
        'a[aria-label*="WhatsApp" i]'
      ];
      var nodes = document.querySelectorAll(selectors.join(','));
      nodes.forEach(function(a){
        a.setAttribute('href', waUrl);
        a.setAttribute('target', '_blank');
        a.setAttribute('rel', 'noopener');
      });
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', canonicalizeContactLinks);
    } else {
      canonicalizeContactLinks();
    }
    window.addEventListener('pageshow', canonicalizeContactLinks);
    document.addEventListener('visibilitychange', function(){ if (!document.hidden) canonicalizeContactLinks(); });
  })();
  </script>
