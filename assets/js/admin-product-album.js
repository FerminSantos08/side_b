(() => {
  const params = new URLSearchParams(window.location.search);
  if (params.get('module') !== 'productos') return;
  const category = document.querySelector('select[name="categoria_id"]');
  const form = document.querySelector('.editorial-form');
  if (!category || !form) return;
  const productId = form.querySelector('input[name="id"]')?.value || '';
  fetch(`admin_product_albums.php?product=${encodeURIComponent(productId)}`, {credentials: 'same-origin'})
    .then(response => response.ok ? response.json() : Promise.reject())
    .then(data => {
      const field = document.createElement('div'); field.className = 'form-field product-album-field';
      const label = document.createElement('label'); label.htmlFor = 'album_id'; label.textContent = 'Álbum relacionado';
      const select = document.createElement('select'); select.id = 'album_id'; select.name = 'album_id';
      select.append(new Option('Selecciona un álbum', ''));
      data.albums.forEach(album => select.append(new Option(`${album.artista} — ${album.titulo}`, album.id, false, String(album.id) === String(data.selected ?? ''))));
      const note = document.createElement('small'); note.textContent = 'Solo para productos físicos de álbumes musicales.';
      field.append(label, select, note); category.closest('.form-field').after(field);
      const update = () => { const enabled = category.selectedOptions[0]?.textContent.trim().toLocaleLowerCase() === 'álbumes musicales'; field.hidden = !enabled; select.disabled = !enabled; if (!enabled) select.value = ''; };
      category.addEventListener('change', update); update();
    }).catch(() => {});
})();
