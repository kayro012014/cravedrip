/* CraveDrip Inventory — wired to live API */

let items     = [];
let filtered  = [];
let activeCat = 'all';
let searchQ   = '';
let sortCol   = null;
let sortDir   = 1;
let editId    = null;
let adjId     = null;
let adjType   = 'add';

document.addEventListener('DOMContentLoaded', () => {
    initClock();
    loadItems();
});

async function loadItems() {
    try {
        const data = await apiGet('inventory.php');
        if (!data.ok) throw new Error(data.error);
        items = data.items;
        refreshStats();
        applyFilter();
    } catch (e) {
        console.error('Inventory load failed:', e);
        showToast('error', 'Failed to load inventory data.');
    }
}

// ── STATS ─────────────────────────────────────────────
function refreshStats() {
    const low = items.filter(i => i.stock > 0 && i.stock <= i.reorderLevel).length;
    const out = items.filter(i => i.stock <= 0).length;
    const val = items.reduce((s, i) => s + i.costPrice * i.stock, 0);

    document.getElementById('sTotalSKU').textContent = items.length;
    document.getElementById('sLow').textContent      = low;
    document.getElementById('sOut').textContent      = out;
    document.getElementById('sValue').textContent    =
        '₱' + val.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ── FILTER / SORT / RENDER ────────────────────────────
function applyFilter() {
    filtered = items.filter(i => {
        const catOk = activeCat === 'all' || i.category === activeCat;
        const qOk   = i.name.toLowerCase().includes(searchQ.toLowerCase());
        return catOk && qOk;
    });

    if (sortCol) {
        filtered.sort((a, b) => {
            let av = a[sortCol], bv = b[sortCol];
            if (typeof av === 'string') av = av.toLowerCase();
            if (typeof bv === 'string') bv = bv.toLowerCase();
            return av < bv ? -sortDir : av > bv ? sortDir : 0;
        });
    }
    renderTable();
}

function renderTable() {
    const tbody = document.getElementById('invTbody');
    if (!filtered.length) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:2.5rem;color:var(--color-latte)">
            <i class="fas fa-box-open" style="font-size:1.4rem;display:block;margin-bottom:0.5rem"></i>
            No items found.</td></tr>`;
        return;
    }

    tbody.innerHTML = filtered.map(item => {
        const sc    = item.stock <= 0 ? 'stock-out' : item.stock <= item.reorderLevel ? 'stock-low' : 'stock-good';
        const badge = item.stock <= 0
            ? `<span class="badge badge-danger">Out of Stock</span>`
            : item.stock <= item.reorderLevel
                ? `<span class="badge badge-warning">Low Stock</span>`
                : `<span class="badge badge-success">In Stock</span>`;
        const catColors = { ingredients: 'badge-default', supplies: 'badge-info', baked: 'badge-success' };

        return `<tr>
            <td>
                <div style="font-weight:600;color:var(--color-coffee);margin-bottom:3px">${item.name}</div>
                <span class="badge ${catColors[item.category] || 'badge-default'}">${item.category}</span>
            </td>
            <td><span class="${sc}">${item.stock} ${item.unit}</span></td>
            <td style="color:var(--color-latte)">${item.reorderLevel} ${item.unit}</td>
            <td>₱${item.costPrice.toLocaleString()}</td>
            <td>${item.sellPrice != null ? '₱' + item.sellPrice.toLocaleString() : '<span style="color:var(--color-latte)">—</span>'}</td>
            <td>${badge}</td>
            <td>
                <div style="display:flex;gap:0.35rem;flex-wrap:wrap">
                    <button class="btn btn-sm btn-secondary" onclick="openAdjust(${item.id})">
                        <i class="fas fa-boxes"></i> Adjust</button>
                    <button class="btn btn-sm btn-ghost" onclick="openEdit(${item.id})">
                        <i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger" onclick="deleteItem(${item.id})">
                        <i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function sortBy(col) {
    if (sortCol === col) sortDir *= -1; else { sortCol = col; sortDir = 1; }
    document.querySelectorAll('thead th[data-sort]').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
        if (th.dataset.sort === col) th.classList.add(sortDir === 1 ? 'sort-asc' : 'sort-desc');
    });
    applyFilter();
}

function setCat(cat) {
    activeCat = cat;
    document.querySelectorAll('.cat-pill').forEach(el =>
        el.classList.toggle('active', el.dataset.cat === cat));
    applyFilter();
}

function setSearch(q) { searchQ = q; applyFilter(); }

// ── ADD / EDIT ────────────────────────────────────────
function openAdd() {
    editId = null;
    document.getElementById('modalTitle').textContent = 'Add New Item';
    document.getElementById('itemForm').reset();
    openModal('itemModal');
}

function openEdit(id) {
    const item = items.find(i => i.id === id);
    if (!item) return;
    editId = id;
    document.getElementById('modalTitle').textContent = 'Edit Item';
    document.getElementById('fName').value      = item.name;
    document.getElementById('fCat').value        = item.category;
    document.getElementById('fUnit').value       = item.unit;
    document.getElementById('fStock').value      = item.stock;
    document.getElementById('fReorder').value    = item.reorderLevel;
    document.getElementById('fCostPrice').value  = item.costPrice;
    document.getElementById('fSellPrice').value  = item.sellPrice ?? '';
    openModal('itemModal');
}

async function saveItem() {
    const name      = document.getElementById('fName').value.trim();
    const category  = document.getElementById('fCat').value;
    const unit      = document.getElementById('fUnit').value;
    const stock     = document.getElementById('fStock').value;
    const reorder   = document.getElementById('fReorder').value;
    const costPrice = document.getElementById('fCostPrice').value;
    const sellPrice = document.getElementById('fSellPrice').value;

    if (!name || !category || !unit) { alert('Name, category, and unit are required.'); return; }

    const fd = new FormData();
    fd.append('action',     editId ? 'update' : 'add');
    if (editId) fd.append('id', editId);
    fd.append('name',       name);
    fd.append('category',   category);
    fd.append('unit',       unit);
    fd.append('stock',      stock);
    fd.append('reorder',    reorder);
    fd.append('cost_price', costPrice);
    if (sellPrice) fd.append('sell_price', sellPrice);

    const btn = document.querySelector('#itemModal .btn-primary');
    if (btn) btn.disabled = true;

    try {
        const data = await apiPost('inventory.php', fd);
        if (!data.ok) throw new Error(data.error || 'Save failed');
        closeModal('itemModal');
        showToast('success', editId ? 'Item updated!' : 'Item added!');
        await loadItems();
    } catch (e) {
        alert('Error: ' + e.message);
    } finally {
        if (btn) btn.disabled = false;
    }
}

async function deleteItem(id) {
    const item = items.find(i => i.id === id);
    if (!item) return;
    if (!confirm(`Delete "${item.name}"? This cannot be undone.`)) return;

    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);

    try {
        const data = await apiPost('inventory.php', fd);
        if (!data.ok) throw new Error(data.error || 'Delete failed');
        showToast('success', 'Item deleted.');
        await loadItems();
    } catch (e) {
        alert('Error: ' + e.message);
    }
}

// ── STOCK ADJUSTMENT ──────────────────────────────────
function openAdjust(id) {
    const item = items.find(i => i.id === id);
    if (!item) return;
    adjId = id;
    document.getElementById('adjName').textContent    = item.name;
    document.getElementById('adjCurrent').textContent = `Current stock: ${item.stock} ${item.unit}`;
    document.getElementById('adjQty').value    = '';
    document.getElementById('adjReason').value = '';
    setAdjType('add');
    openModal('adjModal');
}

function setAdjType(t) {
    adjType = t;
    document.querySelectorAll('.adj-btn').forEach(btn =>
        btn.classList.toggle('active', btn.dataset.type === t));
}

async function confirmAdj() {
    const qty = parseFloat(document.getElementById('adjQty').value);
    if (!qty || qty <= 0) { alert('Enter a valid quantity.'); return; }

    const fd = new FormData();
    fd.append('action', 'adjust');
    fd.append('id',     adjId);
    fd.append('type',   adjType);
    fd.append('qty',    qty);
    fd.append('reason', document.getElementById('adjReason').value.trim());

    const btn = document.querySelector('#adjModal .btn-primary');
    if (btn) btn.disabled = true;

    try {
        const data = await apiPost('inventory.php', fd);
        if (!data.ok) throw new Error(data.error || 'Adjust failed');
        closeModal('adjModal');
        showToast('success', 'Stock updated!');
        await loadItems();
    } catch (e) {
        alert('Error: ' + e.message);
    } finally {
        if (btn) btn.disabled = false;
    }
}

// ── MODALS ────────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
