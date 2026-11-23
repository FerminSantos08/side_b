const adminMenus = document.querySelectorAll('.admin-menu');

for (const menu of adminMenus) {
  menu.addEventListener('toggle', () => {
    if (!menu.open) return;
    for (const other of adminMenus) {
      if (other !== menu) other.open = false;
    }
  });
}

document.addEventListener('click', (event) => {
  for (const menu of adminMenus) {
    if (menu.open && !menu.contains(event.target)) menu.open = false;
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') return;
  const openMenu = document.querySelector('.admin-menu[open]');
  if (!openMenu) return;
  openMenu.open = false;
  openMenu.querySelector('summary').focus();
});
