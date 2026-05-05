/* CraveDrip Inventory — Frontend Logic */

let items = INVENTORY_ITEMS.map(i => ({ ...i }));
let filtered = [];
let activeCat = 'all';
let searchQ   = '';
let sortCol   = null;
let sortDir   = 1;
let editId    = null;
let adjId     = null;
let adjType   = 'add';

document.addEventListener('DOMContentLoaded', () => {
    initClock();
    refreshStats();
    applyFilter();
});

// ── STATS ─────────────────────────────────────────────
function refreshStats() {
    const low  = items.filter(i => i.stock > 0 && i.stock <= i.reorderLevel).length;
    const out  = items.filter(i => i.stock <= 0).length;
    const val  = items.reduce((s, i) => s + i.costPrice * i.stock, 0);

    document.getElementById('sTotalSKU').textContent = items.length;
    document.getElementById('sLow').textContent      = low;
    document.getElementById('sOut').textContent      = out;
    document.getElementById('sValue').textContent    = '₱' + val.toLocaleString('en-PH',
        {minimumFractionDigits:2, maximumFractionDigits:2});
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
        const sc  = item.stock <= 0 ? 'stock-out' : item.stock <= item.reorderLevel ? 'stock-low' : 'stock-good';
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
            <td>${item.sellPrice ? '₱' + item.sellPrice.toLocaleString() : '<span style="color:var(--color-latte)">—</span>'}</td>
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
        th.classList.remove('sort-asc','sort-desc');
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

// ── ADD / EDIT MODAL ──────────────────────────────────
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
    document.getElementById('fSellPrice').value  = item.sellPrice || '';
    openModal('itemModal');
}

function saveItem() {
    const name      = document.getElementById('fName').value.trim();
    const category  = document.getElementById('fCat').value;
    const unit      = document.getElementById('fUnit').value;
    const stock     = parseFloat(document.getElementById('fStock').value)     || 0;
    const reorder   = parseFloat(document.getElementById('fReorder').value)   || 0;
    const costPrice = parseFloat(document.getElementById('fCostPrice').value) || 0;
    const sellPrice = parseFloat(document.getElementById('fSellPrice').value) || null;

    if (!name || !category || !unit) { alert('Name, category, and unit are required.'); return; }

    if (editId) {
        const item = items.find(i => i.id === editId);
        Object.assign(item, { name, category, unit, stock, reorderLevel: reorder, costPrice, sellPrice });
    } else {
        const newId = Math.max(0, ...items.map(i => i.id)) + 1;
        items.push({ id: newId, name, category, unit, stock, reorderLevel: reorder, costPrice, sellPrice });
    }

    closeModal('itemModal');
    refreshStats();
    applyFilter();
}

function deleteItem(id) {
    const item = items.find(i => i.id === id);
    if (!item) return;
    if (!confirm(`Delete "${item.name}"? This cannot be undone.`)) return;
    items = items.filter(i => i.id !== id);
    refreshStats();
    applyFilter();
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

function confirmAdj() {
    const qty = parseFloat(document.getElementById('adjQty').value);
    if (!qty || qty <= 0) { alert('Enter a valid quantity.'); return; }

    const item = items.find(i => i.id === adjId);
    if (!item) return;

    if (adjType === 'add') {
        item.stock = parseFloat((item.stock + qty).toFixed(3));
    } else {
        if (qty > item.stock) { alert('Cannot deduct more than current stock!'); return; }
        item.stock = parseFloat((item.stock - qty).toFixed(3));
    }

    closeModal('adjModal');
    refreshStats();
    applyFilter();
}

// ── MODALS & CLOCK ────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function initClock() {
    const el = document.getElementById('live-clock');
    const tick = () => el.textContent = new Date().toLocaleString('en-PH',
        {weekday:'short',month:'short',day:'numeric',hour:'2-digit',minute:'2-digit',second:'2-digit'});
    tick();
    setInterval(tick, 1000);
}
