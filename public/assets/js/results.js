(() => {
  const body = document.body;
  const toggle = document.querySelector('[data-theme-toggle]');
  const savedTheme = localStorage.getItem('examcore-theme');
  if (savedTheme === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
    if (toggle) toggle.textContent = '☾';
  }

  if (toggle) {
    toggle.addEventListener('click', () => {
      const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem('examcore-theme', next);
      toggle.textContent = next === 'dark' ? '☾' : '☀️';
    });
  }

  const menuButton = document.querySelector('[data-mobile-menu-button]');
  const mobilePanel = document.querySelector('[data-mobile-panel]');
  const closeButton = document.querySelector('[data-mobile-menu-close]');

  const setMenuState = (isOpen) => {
    body.classList.toggle('menu-open', isOpen);
    mobilePanel?.classList.toggle('is-open', isOpen);
    menuButton?.setAttribute('aria-expanded', String(isOpen));
  };

  menuButton?.addEventListener('click', () => {
    const isOpen = !mobilePanel?.classList.contains('is-open');
    setMenuState(isOpen);
  });

  closeButton?.addEventListener('click', () => setMenuState(false));
  mobilePanel?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => setMenuState(false));
  });

  const tabs = document.querySelectorAll('[data-review-tab]');
  const panels = document.querySelectorAll('[data-review-panel]');
  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      const next = tab.dataset.reviewTab;
      tabs.forEach((item) => item.classList.toggle('is-active', item === tab));
      panels.forEach((panel) => {
        panel.classList.toggle('is-visible', panel.dataset.reviewPanel === next);
      });
    });
  });

  const progressBar = document.querySelector('.progress-bar');
  if (progressBar) {
    const value = Number(progressBar.dataset.progress || 0);
    progressBar.style.width = `${Math.min(100, Math.max(0, value))}%`;
  }

  const revealItems = document.querySelectorAll('[data-reveal]');
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
      }
    });
  }, { threshold: 0.14 });

  revealItems.forEach((item) => revealObserver.observe(item));
})();





