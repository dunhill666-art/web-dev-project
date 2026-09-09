/**
 * AeroGlide Global Interactivity Script
 */
(function() {
  'use strict';

  // Micro-interaction helpers
  window.AeroGlide = {
    showAlert: function(msg, type = 'info') {
      console.log(`[AeroGlide ${type.toUpperCase()}] ${msg}`);
    }
  };

  document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll for internal hash links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href.length > 1) {
          const target = document.querySelector(href);
          if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth' });
          }
        }
      });
    });
  });
})();
