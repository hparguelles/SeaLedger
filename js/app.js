/* ==========================================================================
   SeaLedger – fish vendor inventory, restocking, and sales tracker
   Runs on every page; each page sets <body data-page="..."> to pick its logic.
   Pages: login, dashboard, inventory, restocking, sales, reports
   All data is saved in the browser's localStorage (no server).
   ========================================================================== */

(() => {

  /* ------------------------------------------------------------------------
     1. DATE HELPERS
     ------------------------------------------------------------------------ */

  // Format a Date as "YYYY-MM-DD"
  const formatISODate = (d) =>
    `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

  // Today's date as "YYYY-MM-DD"
  const today = () => formatISODate(new Date());

  // Date N days from today (negative = past) as "YYYY-MM-DD"
  const offset = (n) => {
    const d = new Date();
    d.setDate(d.getDate() + n);
    return formatISODate(d);
  };


  /* ------------------------------------------------------------------------
     2. DATA STORE (localStorage)
     ------------------------------------------------------------------------ */

  const STORAGE_KEY = 'sealedgerStaticV1';

  // Demo data used the first time the app is opened
  const starter = () => ({
    products: [
      { id: 1, name: 'Bangus',     category: 'Milkfish',        price: 180, stock: 4,  minStock: 10, available: true },
      { id: 2, name: 'Tilapia',    category: 'Freshwater fish', price: 140, stock: 3,  minStock: 8,  available: true },
      { id: 3, name: 'Galunggong', category: 'Round scad',      price: 160, stock: 2,  minStock: 6,  available: true },
      { id: 4, name: 'Tulingan',   category: 'Mackerel tuna',   price: 190, stock: 14, minStock: 5,  available: true },
      { id: 5, name: 'Tamban',     category: 'Sardine',         price: 120, stock: 25, minStock: 8,  available: true },
      { id: 6, name: 'Pampano',    category: 'Pomfret',         price: 280, stock: 18, minStock: 5,  available: true },
    ],
    plans: [
      {
        id: 1,
        title: 'Morning supply run',
        date: offset(2),
        time: '04:30',
        items: [
          { productId: 1, quantity: 15 },
          { productId: 2, quantity: 10 },
        ],
        notes: 'Buy fresh stock before market opens',
        status: 'Pending',
      },
    ],
    sales: [],
    activities: [
      {
        id: 1,
        text: 'SeaLedger started',
        detail: 'Demo data loaded in this browser',
        date: new Date().toISOString(),
      },
    ],
  });

  // Load saved data, or fall back to the starter data
  let db;
  try {
    db = JSON.parse(localStorage.getItem(STORAGE_KEY)) || starter();
  } catch (e) {
    db = starter();
  }

  const save = () => localStorage.setItem(STORAGE_KEY, JSON.stringify(db));


  /* ------------------------------------------------------------------------
     3. SMALL UTILITIES
     ------------------------------------------------------------------------ */

  // 1234 -> "₱1,234.00"
  const money = (n) =>
    new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(n) || 0);

  // "2026-10-23" -> "Oct 23, 2026"  (or "—" if empty)
  const datefmt = (d) =>
    d
      ? new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
      : '—';

  // Escape text so it is safe to insert into HTML
  const esc = (s) =>
    String(s ?? '').replace(/[&<>"']/g, (c) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;',
    }[c]));

  // Look up a product by id
  const product = (id) => db.products.find((p) => p.id === Number(id));

  // Available products at or below their minimum stock
  const low = () =>
    db.products.filter((p) => p.available && Number(p.stock) <= Number(p.minStock));

  // Total kg of all available products
  const totalStock = () =>
    db.products
      .filter((p) => p.available)
      .reduce((sum, p) => sum + Number(p.stock || 0), 0);

  // Add an entry to the activity history (keeps the latest 100) and save
  const log = (text, detail) => {
    db.activities.unshift({ id: Date.now(), text, detail, date: new Date().toISOString() });
    db.activities = db.activities.slice(0, 100);
    save();
  };

  // Show a small popup message for 2.5 seconds
  const toast = (message) => {
    const el = document.getElementById('appToast');
    if (el) {
      el.textContent = message;
      el.classList.add('show');
      setTimeout(() => el.classList.remove('show'), 2500);
    }
  };

  // A table row that says "nothing here" across n columns
  const empty = (columns, message) =>
    `<tr><td colspan="${columns}" class="empty-state">${message}</td></tr>`;

  // Sum of sale amounts
  const sumAmounts = (sales) => sales.reduce((sum, s) => sum + Number(s.amount), 0);

  // Pending plans, soonest first
  const pendingPlans = () =>
    db.plans
      .filter((p) => p.status === 'Pending')
      .sort((a, b) => a.date.localeCompare(b.date));

  // Label + CSS class for a product's stock status
  const stockStatus = (p) => {
    if (!p.available)          return { label: 'Unavailable', css: 'status-muted' };
    if (p.stock <= 0)          return { label: 'Sold out',    css: 'status-low' };
    if (p.stock <= p.minStock) return { label: 'Low stock',   css: 'status-low' };
    return { label: 'In stock', css: '' };
  };


  /* ------------------------------------------------------------------------
     4. SHARED PAGE SHELL (sidebar, nav highlight, page title, pending badge)
     ------------------------------------------------------------------------ */

  function initShell() {
    const page = document.body.dataset.page;

    // Highlight the active sidebar link
    document.querySelectorAll('[data-nav]').forEach((a) => {
      a.classList.toggle('active', a.dataset.nav === page);
    });

    // Page title in the top bar
    const labels = {
      dashboard:  'Dashboard',
      inventory:  'Fish Inventory',
      restocking: 'Restocking Plans',
      sales:      'Daily Sales',
      reports:    'Reports & History',
    };
    const label = document.getElementById('pageLabel');
    if (label) label.textContent = labels[page] || '';

    // Number of pending plans on the sidebar badge
    const badge = document.getElementById('pendingBadge');
    if (badge) badge.textContent = pendingPlans().length;

    // Mobile sidebar open/close
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    document.getElementById('mobileMenuButton')?.addEventListener('click', () => {
      sidebar.classList.toggle('sidebar-open');
      overlay.classList.toggle('overlay-visible');
    });

    overlay?.addEventListener('click', () => {
      sidebar.classList.remove('sidebar-open');
      overlay.classList.remove('overlay-visible');
    });
  }


  /* ------------------------------------------------------------------------
     5. DASHBOARD PAGE
     ------------------------------------------------------------------------ */

  function initDashboard() {
    // Summary cards
    document.getElementById('dashProducts').textContent = db.products.filter((p) => p.available).length;
    document.getElementById('dashStock').innerHTML = totalStock().toFixed(1) + ' <small>kg</small>';
    document.getElementById('dashLow').textContent = low().length;
    document.getElementById('dashPlans').textContent = pendingPlans().length;

    const todaySales = db.sales.filter((s) => s.date === today());
    document.getElementById('dashSales').textContent = money(sumAmounts(todaySales));

    // Low-stock table (first 6)
    const lowProducts = low();
    document.getElementById('dashLowRows').innerHTML = lowProducts.length
      ? lowProducts.slice(0, 6).map((p) => `
          <tr>
            <td><strong>${esc(p.name)}</strong></td>
            <td>${p.stock} kg</td>
            <td>${p.minStock} kg</td>
            <td><span class="status-pill status-low">${p.stock <= 0 ? 'Sold out' : 'Low stock'}</span></td>
          </tr>`).join('')
      : empty(4, 'No low-stock products.');

    // Upcoming pending plans (first 4)
    const pending = pendingPlans();
    document.getElementById('dashPlansRows').innerHTML = pending.length
      ? pending.slice(0, 4).map((p) => `
          <div class="activity-item">
            <span class="activity-icon"><i class="bi bi-calendar-event"></i></span>
            <div>
              <strong>${esc(p.title)}</strong>
              <span>${datefmt(p.date)} · ${esc(p.time)}</span>
            </div>
          </div>`).join('')
      : '<p class="muted-small">No pending plans.</p>';

    // Recent activity (latest 4)
    document.getElementById('dashActivity').innerHTML =
      db.activities.slice(0, 4).map((a) => `
        <div class="activity-item">
          <span class="activity-icon"><i class="bi bi-activity"></i></span>
          <div>
            <strong>${esc(a.text)}</strong>
            <span>${esc(a.detail)}</span>
            <small>${new Date(a.date).toLocaleString()}</small>
          </div>
        </div>`).join('')
      || '<p class="muted-small">No activity recorded.</p>';
  }


  /* ------------------------------------------------------------------------
     6. INVENTORY PAGE (add / edit / hide / delete fish products)
     ------------------------------------------------------------------------ */

  function initInventory() {
    const modal  = bootstrap.Modal.getOrCreateInstance(document.getElementById('productModal'));
    const search = document.getElementById('inventorySearch');
    const filter = document.getElementById('inventoryFilter');
    const tbody  = document.getElementById('inventoryRows');

    // --- Draw the stats and product table ---
    const render = () => {
      document.getElementById('invProducts').textContent = db.products.length;
      document.getElementById('invStock').innerHTML = totalStock().toFixed(1) + ' <small>kg</small>';
      document.getElementById('invLow').textContent = low().length;
      document.getElementById('invUnavailable').textContent = db.products.filter((p) => !p.available).length;

      const query = search.value.trim().toLowerCase();
      const mode  = filter.value;

      const rows = db.products
        // text search on name + category
        .filter((p) => (p.name + ' ' + p.category).toLowerCase().includes(query))
        // dropdown filter
        .filter((p) =>
          mode === 'all' ||
          (mode === 'low' && p.available && p.stock <= p.minStock) ||
          (mode === 'available' && p.available) ||
          (mode === 'unavailable' && !p.available)
        );

      tbody.innerHTML = rows.length
        ? rows.map((p) => {
            const status = stockStatus(p);
            return `
              <tr>
                <td>
                  <span class="fish-avatar">${esc(p.name.slice(0, 2).toUpperCase())}</span>
                  <strong>${esc(p.name)}</strong>
                </td>
                <td>${esc(p.category || '—')}</td>
                <td>${money(p.price)}</td>
                <td>${Number(p.stock).toFixed(1)} kg</td>
                <td>${Number(p.minStock).toFixed(1)} kg</td>
                <td><span class="status-pill ${status.css}">${status.label}</span></td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-light edit-product" data-id="${p.id}" title="Edit"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-light toggle-product" data-id="${p.id}" title="Toggle availability"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-sm btn-light delete-product" data-id="${p.id}" title="Delete"><i class="bi bi-trash3"></i></button>
                  </div>
                </td>
              </tr>`;
          }).join('')
        : empty(7, 'No products match your search.');

      document.getElementById('inventoryCount').textContent =
        `Showing ${rows.length} of ${db.products.length} products`;
    };

    // --- Open the add/edit form (pass a product to edit, nothing to add) ---
    const openForm = (p) => {
      document.getElementById('productForm').reset();
      document.getElementById('productId').value = p?.id || '';
      document.getElementById('productModalTitle').textContent = p ? 'Edit fish product' : 'Add fish product';

      // [input id, product field] pairs
      const fields = [
        ['productName',     'name'],
        ['productCategory', 'category'],
        ['productPrice',    'price'],
        ['productStock',    'stock'],
        ['productMinStock', 'minStock'],
      ];
      for (const [inputId, key] of fields) {
        document.getElementById(inputId).value = p?.[key] ?? '';
      }

      document.getElementById('productAvailable').checked = p?.available ?? true;
      modal.show();
    };

    // --- Button events ---
    document.getElementById('addProductButton').addEventListener('click', () => openForm());

    // Edit / toggle / delete buttons inside the table
    tbody.addEventListener('click', (e) => {
      const button = e.target.closest('button');
      if (!button) return;

      const p = product(button.dataset.id);
      if (!p) return;

      if (button.classList.contains('edit-product')) {
        openForm(p);
      }

      if (button.classList.contains('toggle-product')) {
        p.available = !p.available;
        log('Product availability changed', p.name);
        save();
        render();
        toast('Product status updated.');
      }

      if (button.classList.contains('delete-product') && confirm(`Delete ${p.name} from inventory?`)) {
        db.products = db.products.filter((x) => x.id !== p.id);
        log('Product removed', p.name);
        save();
        render();
        toast('Product removed.');
      }
    });

    // --- Save the add/edit form ---
    document.getElementById('productForm').addEventListener('submit', (e) => {
      e.preventDefault();

      const id = Number(document.getElementById('productId').value);
      const data = {
        name:      document.getElementById('productName').value.trim(),
        category:  document.getElementById('productCategory').value.trim(),
        price:     Number(document.getElementById('productPrice').value),
        stock:     Number(document.getElementById('productStock').value),
        minStock:  Number(document.getElementById('productMinStock').value),
        available: document.getElementById('productAvailable').checked,
      };

      // Validation: name required, numbers must be valid and not negative
      const numbersValid = [data.price, data.stock, data.minStock].every(Number.isFinite);
      if (!data.name || !numbersValid || data.price < 0 || data.stock < 0 || data.minStock < 0) return;

      if (id) {
        // Editing an existing product
        Object.assign(product(id), data);
        log('Product updated', data.name);
      } else {
        // Adding a new product
        data.id = Math.max(0, ...db.products.map((p) => p.id)) + 1;
        db.products.push(data);
        log('Product added', data.name);
      }

      save();
      modal.hide();
      render();
      toast('Product saved successfully.');
    });

    // --- Live search / filter ---
    search.addEventListener('input', render);
    filter.addEventListener('change', render);

    render();
  }


  /* ------------------------------------------------------------------------
     7. RESTOCKING PAGE (plan what fish to buy and when)
     ------------------------------------------------------------------------ */

  function initRestocking() {
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('planModal'));
    const items = document.getElementById('planItems');   // list of fish rows inside the form
    const list  = document.getElementById('plansGrid');   // plan cards

    // One "fish + target kg" row in the form
    const itemRow = (id = '', qty = '') => `
      <div class="plan-item-row">
        <select class="form-select plan-product">
          <option value="">Choose fish variety</option>
          ${db.products.map((p) =>
            `<option value="${p.id}" ${Number(id) === p.id ? 'selected' : ''}>${esc(p.name)}</option>`
          ).join('')}
        </select>
        <input class="form-control plan-quantity" type="number" min=".1" step=".1" placeholder="Target kg" value="${qty}">
        <button type="button" class="btn btn-light remove-item"><i class="bi bi-x-lg"></i></button>
      </div>`;

    // --- Draw stats and plan cards ---
    const render = () => {
      const statusFilter = document.getElementById('planFilter').value;

      const plans = [...db.plans]
        .filter((p) => statusFilter === 'all' || p.status === statusFilter)
        .sort((a, b) => a.date.localeCompare(b.date));

      const pending = pendingPlans();

      document.getElementById('planTotal').textContent = db.plans.length;
      document.getElementById('planPending').textContent = pending.length;
      document.getElementById('planCompleted').textContent = db.plans.filter((p) => p.status === 'Completed').length;
      document.getElementById('planNext').textContent = pending[0] ? datefmt(pending[0].date) : '—';
      document.getElementById('pendingBadge').textContent = pending.length;

      list.innerHTML = plans.length
        ? plans.map((p) => {
            const day   = new Date(p.date + 'T00:00:00').getDate();
            const month = new Date(p.date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short' });

            const fishList = p.items.length
              ? p.items.map((i) => `
                  <div class="plan-variety">
                    <span>${esc(product(i.productId)?.name || 'Removed product')}</span>
                    <b>${i.quantity} kg</b>
                  </div>`).join('')
              : '<p>No varieties listed.</p>';

            const statusButton = p.status === 'Pending'
              ? `<button class="btn btn-sm btn-outline-teal complete-plan" data-id="${p.id}">Mark completed</button>`
              : `<button class="btn btn-sm btn-light reopen-plan" data-id="${p.id}">Reopen</button>`;

            return `
              <article class="plan-card">
                <div class="plan-card-top">
                  <span class="date-tile"><strong>${day}</strong> ${month}</span>
                  <span class="status-pill ${p.status === 'Pending' ? 'status-pending' : ''}">${p.status}</span>
                </div>
                <h3>${esc(p.title)}</h3>
                <p><i class="bi bi-calendar3"></i> ${datefmt(p.date)} · ${esc(p.time)}</p>
                <div class="plan-varieties">
                  <strong>Fish to buy</strong>
                  ${fishList}
                </div>
                ${p.notes ? `<p>${esc(p.notes)}</p>` : ''}
                <div class="plan-card-actions">
                  ${statusButton}
                  <button class="btn btn-sm btn-light edit-plan" data-id="${p.id}"><i class="bi bi-pencil"></i></button>
                  <button class="btn btn-sm btn-light delete-plan" data-id="${p.id}"><i class="bi bi-trash3"></i></button>
                </div>
              </article>`;
          }).join('')
        : '<div class="empty-panel">No plans found. Create a restocking plan to get started.</div>';
    };

    // --- Open the create/edit form ---
    const openForm = (p) => {
      document.getElementById('planForm').reset();
      document.getElementById('planId').value = p?.id || '';
      document.getElementById('planModalTitle').textContent = p ? 'Edit restocking plan' : 'Create restocking plan';
      document.getElementById('planTitle').value = p?.title || '';
      document.getElementById('planDate').value = p?.date || offset(1);
      document.getElementById('planTime').value = p?.time || '04:30';
      document.getElementById('planNotes').value = p?.notes || '';

      items.innerHTML = p?.items?.length
        ? p.items.map((i) => itemRow(i.productId, i.quantity)).join('')
        : itemRow();

      modal.show();
    };

    // --- Button events ---
    document.getElementById('addPlanButton').addEventListener('click', () => openForm());
    document.getElementById('addPlanItem').addEventListener('click', () => {
      items.insertAdjacentHTML('beforeend', itemRow());
    });

    // Remove a fish row (if it's the last row, just clear it)
    items.addEventListener('click', (e) => {
      if (!e.target.closest('.remove-item')) return;

      const row = e.target.closest('.plan-item-row');
      if (items.querySelectorAll('.plan-item-row').length > 1) {
        row.remove();
      } else {
        row.querySelector('select').value = '';
        row.querySelector('input').value = '';
      }
    });

    // Buttons on plan cards: complete / reopen / edit / delete
    list.addEventListener('click', (e) => {
      const button = e.target.closest('button');
      if (!button) return;

      const p = db.plans.find((x) => x.id === Number(button.dataset.id));
      if (!p) return;

      if (button.classList.contains('edit-plan')) {
        openForm(p);
      }

      if (button.classList.contains('complete-plan')) {
        p.status = 'Completed';
        log('Restocking plan completed', p.title);
        save();
        render();
      }

      if (button.classList.contains('reopen-plan')) {
        p.status = 'Pending';
        save();
        render();
      }

      if (button.classList.contains('delete-plan') && confirm('Delete this restocking plan?')) {
        db.plans = db.plans.filter((x) => x.id !== p.id);
        log('Restocking plan removed', p.title);
        save();
        render();
      }
    });

    document.getElementById('planFilter').addEventListener('change', render);

    // --- Save the create/edit form ---
    document.getElementById('planForm').addEventListener('submit', (e) => {
      e.preventDefault();

      const id = Number(document.getElementById('planId').value);
      const data = {
        title: document.getElementById('planTitle').value.trim(),
        date:  document.getElementById('planDate').value,
        time:  document.getElementById('planTime').value,
        notes: document.getElementById('planNotes').value.trim(),
        // Read each fish row; ignore rows with no fish or no quantity
        items: [...items.querySelectorAll('.plan-item-row')]
          .map((row) => ({
            productId: Number(row.querySelector('select').value),
            quantity:  Number(row.querySelector('input').value),
          }))
          .filter((i) => i.productId && i.quantity > 0),
        status: 'Pending',
      };

      if (!data.title || !data.date || !data.time) return;

      if (id) {
        // Editing: keep the plan's existing status
        const existing = db.plans.find((p) => p.id === id);
        data.id = id;
        data.status = existing.status;
        Object.assign(existing, data);
        log('Restocking plan updated', data.title);
      } else {
        // Creating a new plan
        data.id = Math.max(0, ...db.plans.map((p) => p.id)) + 1;
        db.plans.push(data);
        log('Restocking plan created', data.title);
      }

      save();
      modal.hide();
      render();
      toast('Restocking plan saved.');
    });

    render();
  }


  /* ------------------------------------------------------------------------
     8. SALES PAGE (record daily sales)
     ------------------------------------------------------------------------ */

  function initSales() {
    const modal  = bootstrap.Modal.getOrCreateInstance(document.getElementById('saleModal'));
    const editor = document.getElementById('saleItems');  // fish rows inside the form
    const rows   = document.getElementById('salesRows');  // sales table body

    // One "fish + kg sold" row in the form (optional detail)
    const itemRow = (id = '', qty = '') => `
      <div class="plan-item-row">
        <select class="form-select sale-product">
          <option value="">Choose fish variety</option>
          ${db.products.map((p) =>
            `<option value="${p.id}" ${Number(id) === p.id ? 'selected' : ''}>${esc(p.name)}</option>`
          ).join('')}
        </select>
        <input class="form-control sale-quantity" type="number" min=".1" step=".1" placeholder="Quantity kg" value="${qty}">
        <button type="button" class="btn btn-light remove-sale-item"><i class="bi bi-x-lg"></i></button>
      </div>`;

    // --- Draw stats and sales table ---
    const render = () => {
      const from = document.getElementById('salesFrom').value;
      const to   = document.getElementById('salesTo').value;

      const all = [...db.sales];
      const filtered = all
        .filter((s) => (!from || s.date >= from) && (!to || s.date <= to))
        .sort((a, b) => b.date.localeCompare(a.date)); // newest first

      const thisMonth = today().slice(0, 7); // "YYYY-MM"

      document.getElementById('salesTotal').textContent = money(sumAmounts(all));
      document.getElementById('salesRecords').textContent = all.length;
      document.getElementById('salesMonth').textContent =
        money(sumAmounts(all.filter((s) => s.date.slice(0, 7) === thisMonth)));

      rows.innerHTML = filtered.length
        ? filtered.map((s) => {
            const fishSold = s.items?.length
              ? s.items.map((i) => `${esc(product(i.productId)?.name || 'Removed')}: ${i.quantity} kg`).join(', ')
              : '<span class="muted-small">Not specified</span>';

            return `
              <tr>
                <td><strong>${datefmt(s.date)}</strong></td>
                <td>${money(s.amount)}</td>
                <td>${fishSold}</td>
                <td>${esc(s.notes || '—')}</td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-light edit-sale" data-id="${s.id}"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-light delete-sale" data-id="${s.id}"><i class="bi bi-trash3"></i></button>
                  </div>
                </td>
              </tr>`;
          }).join('')
        : empty(5, 'No sales records for this date range.');

      document.getElementById('salesCount').textContent =
        `Showing ${filtered.length} of ${all.length} records`;
    };

    // --- Open the record/edit form ---
    const openForm = (s) => {
      document.getElementById('saleForm').reset();
      document.getElementById('saleId').value = s?.id || '';
      document.getElementById('saleModalTitle').textContent = s ? 'Edit sales record' : 'Record daily sales';
      document.getElementById('saleDate').value = s?.date || today();
      document.getElementById('saleAmount').value = s?.amount ?? '';
      document.getElementById('saleNotes').value = s?.notes || '';

      editor.innerHTML = s?.items?.length
        ? s.items.map((i) => itemRow(i.productId, i.quantity)).join('')
        : '';

      modal.show();
    };

    // --- Button events ---
    document.getElementById('newSaleButton').addEventListener('click', () => openForm());
    document.getElementById('addSaleItem').addEventListener('click', () => {
      editor.insertAdjacentHTML('beforeend', itemRow());
    });

    editor.addEventListener('click', (e) => {
      const button = e.target.closest('.remove-sale-item');
      if (button) button.closest('.plan-item-row').remove();
    });

    // --- Save the form ---
    document.getElementById('saleForm').addEventListener('submit', (e) => {
      e.preventDefault();

      const id = Number(document.getElementById('saleId').value);
      const data = {
        date:   document.getElementById('saleDate').value,
        amount: Number(document.getElementById('saleAmount').value),
        notes:  document.getElementById('saleNotes').value.trim(),
        items:  [...editor.querySelectorAll('.plan-item-row')]
          .map((row) => ({
            productId: Number(row.querySelector('select').value),
            quantity:  Number(row.querySelector('input').value),
          }))
          .filter((i) => i.productId && i.quantity > 0),
      };

      if (!data.date || !Number.isFinite(data.amount) || data.amount < 0) return;

      if (id) {
        Object.assign(db.sales.find((s) => s.id === id), data);
        log('Daily sales record updated', datefmt(data.date));
      } else {
        data.id = Math.max(0, ...db.sales.map((s) => s.id)) + 1;
        db.sales.push(data);
        log('Daily sales recorded', datefmt(data.date));
      }

      save();
      modal.hide();
      render();
      toast('Sales record saved.');
    });

    // Edit / delete buttons inside the table
    rows.addEventListener('click', (e) => {
      const button = e.target.closest('button');
      if (!button) return;

      const s = db.sales.find((x) => x.id === Number(button.dataset.id));
      if (!s) return;

      if (button.classList.contains('edit-sale')) {
        openForm(s);
      }

      if (button.classList.contains('delete-sale') && confirm('Delete this sales record?')) {
        db.sales = db.sales.filter((x) => x.id !== s.id);
        save();
        render();
      }
    });

    // --- Date range filter ---
    ['salesFrom', 'salesTo'].forEach((id) => {
      document.getElementById(id).addEventListener('change', render);
    });

    document.getElementById('clearSalesFilter').addEventListener('click', () => {
      document.getElementById('salesFrom').value = '';
      document.getElementById('salesTo').value = '';
      render();
    });

    render();
  }


  /* ------------------------------------------------------------------------
     9. REPORTS PAGE (totals, chart, best sellers, inventory, history)
     ------------------------------------------------------------------------ */

  function initReports() {
    const from = document.getElementById('reportFrom');
    const to   = document.getElementById('reportTo');

    // Set the date range to "first of this month → today"
    const setThisMonth = () => {
      const d = new Date();
      from.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`;
      to.value = today();
    };

    const render = () => {
      // Sales inside the selected range, oldest first
      const sales = db.sales
        .filter((s) => (!from.value || s.date >= from.value) && (!to.value || s.date <= to.value))
        .sort((a, b) => a.date.localeCompare(b.date));

      const total = sumAmounts(sales);

      // --- Stat cards ---
      document.getElementById('reportStats').innerHTML = `
        <article class="stat-card">
          <span class="stat-kicker">SALES TOTAL</span>
          <div class="stat-value">${money(total)}</div>
          <div class="stat-label">For selected period</div>
        </article>
        <article class="stat-card">
          <span class="stat-kicker">SALES RECORDS</span>
          <div class="stat-value">${sales.length}</div>
          <div class="stat-label">Saved daily entries</div>
        </article>
        <article class="stat-card">
          <span class="stat-kicker">DAILY AVERAGE</span>
          <div class="stat-value">${money(sales.length ? total / sales.length : 0)}</div>
          <div class="stat-label">Per saved record</div>
        </article>
        <article class="stat-card">
          <span class="stat-kicker">LOW STOCK</span>
          <div class="stat-value">${low().length}</div>
          <div class="stat-label">Products at/below minimum</div>
        </article>`;

      // --- Sales table (newest first) ---
      document.getElementById('reportSalesRows').innerHTML = sales.length
        ? [...sales].reverse().map((s) => `
            <tr>
              <td>${datefmt(s.date)}</td>
              <td><strong>${money(s.amount)}</strong></td>
              <td>${esc(s.notes || '—')}</td>
            </tr>`).join('')
        : empty(3, 'No sales records in this period.');

      // --- Bar chart: total sales per day (last 14 days with sales) ---
      const perDay = {};
      sales.forEach((s) => {
        perDay[s.date] = (perDay[s.date] || 0) + Number(s.amount);
      });

      const days = Object.entries(perDay).slice(-14);
      const maxValue = Math.max(1, ...days.map(([, value]) => value));

      document.getElementById('salesChart').innerHTML = days.length
        ? days.map(([date, value]) => `
            <div class="chart-bar-item">
              <div class="chart-bar-value">${money(value)}</div>
              <div class="chart-bar" style="height:${Math.max(5, (value / maxValue) * 120)}px"></div>
              <div class="chart-bar-label">${new Date(date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}</div>
            </div>`).join('')
        : '<div class="empty-panel">Record daily sales to see a chart here.</div>';

      // --- Inventory snapshot ---
      document.getElementById('reportInventoryRows').innerHTML = db.products.map((p) => {
        const status = stockStatus(p);
        // (the report shows "Low stock" for sold-out items too)
        const label = status.label === 'Sold out' ? 'Low stock' : status.label;
        return `
          <tr>
            <td><strong>${esc(p.name)}</strong></td>
            <td>${p.stock} kg</td>
            <td>${p.minStock} kg</td>
            <td><span class="status-pill ${status.css}">${label}</span></td>
          </tr>`;
      }).join('') || empty(4, 'No products.');

      // --- Best sellers: total kg per fish, from the optional sale items ---
      const kgByFish = {};
      sales.forEach((s) => {
        (s.items || []).forEach((i) => {
          const p = product(i.productId);
          if (p) kgByFish[p.name] = (kgByFish[p.name] || 0) + Number(i.quantity || 0);
        });
      });

      const topFive = Object.entries(kgByFish)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 5);

      document.getElementById('bestSellers').innerHTML = topFive.length
        ? topFive.map(([name, kg], index) => `
            <div class="best-seller-item">
              <span class="rank-number">${index + 1}</span>
              <span class="best-seller-name">${esc(name)}</span>
              <strong>${kg.toFixed(1)} kg</strong>
            </div>`).join('')
        : '<div class="empty-panel">Optional quantities on sales records will appear here.</div>';

      // --- Activity history (latest 30) ---
      document.getElementById('historyRows').innerHTML = db.activities.length
        ? db.activities.slice(0, 30).map((a) => `
            <div class="activity-item">
              <span class="activity-icon"><i class="bi bi-activity"></i></span>
              <div>
                <strong>${esc(a.text)}</strong>
                <span>${esc(a.detail)}</span>
                <small>${new Date(a.date).toLocaleString()}</small>
              </div>
            </div>`).join('')
        : '<p class="empty-state">No history entries.</p>';
    };

    // Start with "this month"
    setThisMonth();
    render();

    // --- Button events ---
    document.getElementById('applyReport').addEventListener('click', render);

    document.getElementById('resetReport').addEventListener('click', () => {
      setThisMonth();
      render();
    });

    document.getElementById('printReport').addEventListener('click', () => window.print());

    document.getElementById('clearHistory').addEventListener('click', () => {
      if (confirm('Clear all activity history?')) {
        db.activities = [];
        save();
        render();
        toast('History cleared.');
      }
    });
  }


  /* ------------------------------------------------------------------------
     10. START THE APP – pick the right setup for the current page
     ------------------------------------------------------------------------ */

  document.addEventListener('DOMContentLoaded', () => {
    const page = document.body.dataset.page;

    // Login page (demo only – credentials are visible in this file)
    if (page === 'login') {
      document.getElementById('loginForm').addEventListener('submit', (e) => {
        e.preventDefault();

        const ok =
          document.getElementById('username').value === 'vendor' &&
          document.getElementById('password').value === 'SeaLedger123!';

        if (ok) {
          location.href = 'index.html';
        } else {
          document.getElementById('loginError').textContent = 'Incorrect demo username or password.';
        }
      });
      return;
    }

    // All other pages
    initShell();

    const pageInit = {
      dashboard:  initDashboard,
      inventory:  initInventory,
      restocking: initRestocking,
      sales:      initSales,
      reports:    initReports,
    }[page];

    if (pageInit) pageInit();
  });

})();
