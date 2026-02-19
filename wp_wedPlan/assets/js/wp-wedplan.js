(function ($) {
  'use strict';

  async function fetchExpenses() {
    try {
      const response = await fetch(`${wpWedPlan.apiBase}/expenses`, {
        headers: { 'X-WP-Nonce': wpWedPlan.nonce },
      });

      if (!response.ok) {
        return;
      }

      const data = await response.json();
      document.dispatchEvent(
        new CustomEvent('wp_wedplan_expenses_loaded', { detail: data })
      );
    } catch (error) {
      // Silencieux pour éviter de casser le front.
    }
  }

  $(document).ready(function () {
    fetchExpenses();
  });
})(jQuery);
