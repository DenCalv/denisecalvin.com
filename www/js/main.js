// Mobile hamburger menu
const navToggle = document.getElementById('nav-toggle');
const siteNav = document.getElementById('site-nav');

navToggle.addEventListener('click', () => {
  const open = siteNav.classList.toggle('open');
  navToggle.setAttribute('aria-expanded', String(open));
  navToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
});

// Close the menu when any link inside it is tapped
siteNav.addEventListener('click', (e) => {
  if (e.target.closest('a')) {
    siteNav.classList.remove('open');
    navToggle.setAttribute('aria-expanded', 'false');
    navToggle.setAttribute('aria-label', 'Open menu');
  }
});

// Contact form result banners (?sent=1 / ?error=1 set by contact.php redirect)
const params = new URLSearchParams(window.location.search);
if (params.has('sent')) {
  document.getElementById('form-success').hidden = false;
} else if (params.has('error')) {
  document.getElementById('form-error').hidden = false;
}
if (params.has('sent') || params.has('error')) {
  history.replaceState(null, '', window.location.pathname + window.location.hash);
}

// Footer year
document.getElementById('year').textContent = new Date().getFullYear();
