    .then(res => res.json())
    .then(data => {
      document.getElementById('billing-message').textContent = 'Card deleted.';
      document.getElementById('billing-message').style.display = 'block';
      loadUserCardInfo();
    });
  }

  // Initialize billing section when Billing tab is clicked
  const billingTab = document.querySelector('a[data-section="billing-section"]');
  billingTab?.addEventListener('click', function() {
    setTimeout(initBillingSection, 100); // Wait for panel to show
  });
});
