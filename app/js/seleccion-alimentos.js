/* =====================================================================
   js/seleccion-alimentos.js — NutriEscolar
   Selección MÚLTIPLE de alimentos para un menú (asignar-menu.php y
   modificar-menu.php).

   La página debe definir:
     window.MENU_ASIGNADOS = { "<menuId>": [<alimentoId>, ...], ... }
   y un <form data-seleccion-alimentos data-modo="agregar|sincronizar">:
     - agregar:     los alimentos que el menú ya tiene salen marcados y
                    bloqueados; solo se agregan los nuevos que marques.
     - sincronizar: los alimentos del menú salen marcados; al guardar se
                    agregan los marcados nuevos y se quitan los desmarcados.
   ===================================================================== */
(function () {
  var form = document.querySelector('form[data-seleccion-alimentos]');
  if (!form) return;

  var modo        = form.getAttribute('data-modo') || 'agregar';
  var asignados   = window.MENU_ASIGNADOS || {};
  var menuSelect  = form.querySelector('select[name="menu_id"]');
  var items       = Array.prototype.slice.call(form.querySelectorAll('.check-item'));
  var buscador    = form.querySelector('input[type="search"]');
  var contador    = form.querySelector('.contador-checks');
  var btnTodos    = form.querySelector('[data-accion="todos"]');
  var btnNinguno  = form.querySelector('[data-accion="ninguno"]');

  function checkbox(item) { return item.querySelector('input[type="checkbox"]'); }
  function idsAsignados() { return (asignados[menuSelect.value] || []).map(String); }
  function visible(item)  { return item.style.display !== 'none'; }

  function actualizarContador() {
    if (!menuSelect.value) { contador.textContent = 'Elige primero un menú'; return; }
    var ya = idsAsignados(), agregar = 0, quitar = 0;
    items.forEach(function (item) {
      var cb = checkbox(item);
      if (cb.disabled) return;
      var estaba = ya.indexOf(cb.value) !== -1;
      if (cb.checked && !estaba) agregar++;
      if (!cb.checked && estaba) quitar++;
    });
    contador.textContent = (modo === 'agregar')
      ? agregar + ' alimento(s) por agregar'
      : agregar + ' por agregar · ' + quitar + ' por quitar';
  }

  function aplicarMenu() {
    var ya = idsAsignados();
    items.forEach(function (item) {
      var cb = checkbox(item);
      var esta = ya.indexOf(cb.value) !== -1;
      cb.checked = esta;
      if (modo === 'agregar') {
        cb.disabled = esta;
        item.classList.toggle('ya', esta);
      }
      var etiqueta = item.querySelector('small');
      if (etiqueta) etiqueta.hidden = !esta;
    });
    actualizarContador();
  }

  function filtrar() {
    var q = (buscador.value || '').toLowerCase().trim();
    items.forEach(function (item) {
      var texto = item.getAttribute('data-nombre') || '';
      item.style.display = (q === '' || texto.indexOf(q) !== -1) ? '' : 'none';
    });
  }

  menuSelect.addEventListener('change', aplicarMenu);
  if (buscador)   buscador.addEventListener('input', filtrar);
  form.addEventListener('change', function (e) {
    if (e.target && e.target.type === 'checkbox') actualizarContador();
  });
  if (btnTodos) btnTodos.addEventListener('click', function () {
    items.forEach(function (item) {
      var cb = checkbox(item);
      if (visible(item) && !cb.disabled) cb.checked = true;
    });
    actualizarContador();
  });
  if (btnNinguno) btnNinguno.addEventListener('click', function () {
    items.forEach(function (item) {
      var cb = checkbox(item);
      if (visible(item) && !cb.disabled) cb.checked = false;
    });
    actualizarContador();
  });

  form.addEventListener('submit', function (e) {
    if (!menuSelect.value) {
      e.preventDefault();
      alert('Selecciona un menú.');
      return;
    }
    var nuevos = items.filter(function (i) { var c = checkbox(i); return c.checked && !c.disabled; }).length;
    if (modo === 'agregar' && nuevos === 0) {
      e.preventDefault();
      alert('Marca al menos un alimento nuevo para agregar al menú.');
      return;
    }
    if (modo === 'sincronizar') {
      var total = items.filter(function (i) { return checkbox(i).checked; }).length;
      if (total === 0 && !confirm('El menú quedará sin alimentos. ¿Continuar?')) {
        e.preventDefault();
      }
    }
  });

  if (menuSelect.value) aplicarMenu(); else actualizarContador();
})();
