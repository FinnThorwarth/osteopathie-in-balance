export function initMobileMenu() {
  const menuToggle = document.querySelector('[data-menu-toggle]');
  const menuClose = document.querySelector('[data-menu-close]');
  const mobileMenu = document.querySelector('[data-mobile-menu]');
  const submenuToggles = document.querySelectorAll('[data-submenu-toggle]');

  if (!menuToggle || !menuClose || !mobileMenu) return;

  // Open menu
  menuToggle.addEventListener('click', () => {
    mobileMenu.classList.remove('translate-x-full');
    mobileMenu.classList.add('translate-x-0');
    document.body.style.overflow = 'hidden';
  });

  // Close menu
  menuClose.addEventListener('click', () => {
    mobileMenu.classList.add('translate-x-full');
    mobileMenu.classList.remove('translate-x-0');
    document.body.style.overflow = '';
  });

  // Close menu on escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !mobileMenu.classList.contains('translate-x-full')) {
      mobileMenu.classList.add('translate-x-full');
      mobileMenu.classList.remove('translate-x-0');
      document.body.style.overflow = '';
    }
  });

  // Handle submenu toggles
  submenuToggles.forEach(toggle => {
    toggle.addEventListener('click', () => {
      const submenu = toggle.closest('li').querySelector('[data-submenu]');
      const isOpen = !submenu.classList.contains('hidden');
      
      if (isOpen) {
        submenu.classList.add('hidden');
        toggle.textContent = '+';
      } else {
        submenu.classList.remove('hidden');
        toggle.textContent = '-';
      }
    });
  });
}