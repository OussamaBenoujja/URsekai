  }
  
  return true;
}

// Make sure button links go to correct pages
function fixButtonLinks() {
  document.querySelectorAll('button[onclick*="window.location"]').forEach(button => {
    const onclickAttr = button.getAttribute('onclick');
    if (onclickAttr && onclickAttr.includes('window.location.href')) {
      // Extract the target URL
      const match = onclickAttr.match(/window\.location\.href\s*=\s*['"]([^'"]+)['"]/);
      if (match && match[1]) {
        const href = match[1];
        
        // Fix the path if needed
        if (!href.startsWith('/dev-portal/') && !href.startsWith('http')) {
          const newHref = `/dev-portal/pages/${href}`;
          button.setAttribute('onclick', `window.location.href='${newHref}'`);
        }
      }
    }
  });
}
