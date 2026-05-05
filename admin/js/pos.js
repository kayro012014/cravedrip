/* CraveDrip POS — Frontend Logic */

let cart = [];
let activeCat = 'all';
let searchQ = '';
let payMethod = 'cash';
let orderNum = parseInt(sessionStorage.getItem('lastOrderNum') || '1000') + 1;

document.addEventListener('DOMContentLoaded', () => {
    initClock();
    renderMenu();
    refreshCart();
});

// ── MENU ──────────────────────────────────────────────
function renderMenu() {
    const grid = document.getElementById('posGrid');
    const items = MENU_ITEMS.filter(item => {
        const catOk = activeCat === 'all' || item.category === activeCat;
        const qOk   = item.name.toLowerCase().includes(searchQ.toLowerCase());
        return catOk && qOk;
    });

    if (!items.length) {
        grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1">
            <i class="fas fa-search"></i><p>No items found.</p></div>`;
        return;
    }

    grid.innerHTML = items.map(item => `
        <div class="pos-item" onclick="addItem(${item.id})">
            <img src="${item.img}" alt="${item.name}"
                 onerror="this.src='../assets/images/espresso.jpg'">
            <div class="pos-item-info">
                <div class="pos-item-name">${item.name}</div>
                <div class="pos-item-price">₱${item.price.toLocaleString()}</div>
            </div>
        </div>
    `).join('');
}

function setCat(cat) {
    activeCat = cat;
    document.querySelectorAll('.pos-cats .cat-pill').forEach(el =>
        el.classList.toggle('active', el.dataset.cat === cat));
    renderMenu();
}

function setSearch(q) {
    searchQ = q;
    renderMenu();
}

// ── CART ──────────────────────────────────────────────
function addItem(id) {
    const found = cart.find(i => i.id === id);
    if (found) {
        found.qty++;
    } else {
        const item = MENU_ITEMS.find(i => i.id === id);
        if (item) cart.push({ ...item, qty: 1 });
    }
    refreshCart();
}

function changeQty(id, delta) {
    const found = cart.find(i => i.id === id);
    if (!found) return;
    found.qty += delta;
    if (found.qty <= 0) cart = cart.filter(i => i.id !== id);
    refreshCart();
}

function removeItem(id) {
    cart = cart.filter(i => i.id !== id);
    refreshCart();
}

function clearCart() {
    if (cart.length && !confirm('Clear the current order?')) return;
    cart = [];
    document.getElementById('discountInput').value = '';
    refreshCart();
}

function refreshCart() {
    const el = document.getElementById('cartItems');
    document.getElementById('cartOrderNum').textContent = `#${orderNum}`;

    if (!cart.length) {
        el.innerHTML = `<div class="empty-state">
            <i class="fas fa-coffee"></i>
            <p>Cart is empty.<br>Tap an item to add it.</p></div>`;
    } else {
        el.innerHTML = cart.map(item => `
            <div class="cart-item">
                <div class="cart-item-name">
                    ${item.name}
                    <small>₱${item.price.toLocaleString()} each</small>
                </div>
                <div class="qty-ctrl">
                    <button class="qty-btn" onclick="changeQty(${item.id},-1)">−</button>
                    <span class="qty-n">${item.qty}</span>
                    <button class="qty-btn" onclick="changeQty(${item.id},1)">+</button>
                </div>
                <span class="cart-sub">₱${(item.price * item.qty).toLocaleString()}</span>
                <button class="cart-remove" onclick="removeItem(${item.id})">
                    <i class="fas fa-times"></i></button>
            </div>`).join('');
    }

    calcTotals();
}

function calcTotals() {
    const subtotal  = cart.reduce((s, i) => s + i.price * i.qty, 0);
    const discount  = Math.min(parseFloat(document.getElementById('discountInput').value) || 0, subtotal);
    const total     = subtotal - discount;
    const vatIn     = total / 1.12 * 0.12;

    document.getElementById('subtotalAmt').textContent = `₱${subtotal.toLocaleString()}`;
    document.getElementById('vatAmt').textContent      = `₱${vatIn.toFixed(2)}`;
    document.getElementById('totalAmt').textContent    = `₱${total.toLocaleString()}`;

    document.getElementById('processBtn').disabled = cart.length === 0 || total < 0;
}

// ── PAYMENT MODAL ─────────────────────────────────────
function openPayment() {
    const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
    const discount = Math.min(parseFloat(document.getElementById('discountInput').value) || 0, subtotal);
    const total    = subtotal - discount;

    document.getElementById('pSubtotal').textContent  = `₱${subtotal.toLocaleString()}`;
    document.getElementById('pDiscount').textContent  = discount > 0 ? `-₱${discount.toLocaleString()}` : '—';
    document.getElementById('pTotal').textContent     = `₱${total.toLocaleString()}`;
    document.getElementById('cashInput').value        = '';
    document.getElementById('changeBox').style.display = 'none';

    selectMethod('cash');
    openModal('payModal');
}

function selectMethod(m) {
    payMethod = m;
    document.querySelectorAll('.pay-tab').forEach(el =>
        el.classList.toggle('active', el.dataset.m === m));
    document.getElementById('cashSection').style.display = m === 'cash' ? 'block' : 'none';
    document.getElementById('changeBox').style.display   = 'none';
}

function calcChange() {
    const total = parseFloat(document.getElementById('pTotal').textContent.replace(/[₱,]/g, ''));
    const cash  = parseFloat(document.getElementById('cashInput').value) || 0;
    const cb    = document.getElementById('changeBox');
    if (cash >= total && cash > 0) {
        document.getElementById('changeAmt').textContent = `₱${(cash - total).toLocaleString()}`;
        cb.style.display = 'block';
    } else {
        cb.style.display = 'none';
    }
}

function confirmPayment() {
    const total = parseFloat(document.getElementById('pTotal').textContent.replace(/[₱,]/g, ''));

    if (payMethod === 'cash') {
        const cash = parseFloat(document.getElementById('cashInput').value) || 0;
        if (cash < total) { alert('Cash amount is less than the total!'); return; }
    }

    closeModal('payModal');
    buildReceipt(total);
    saveSession(total);
}

// ── RECEIPT ───────────────────────────────────────────
function buildReceipt(total) {
    const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
    const discount = subtotal - total;
    const vatIn    = total / 1.12 * 0.12;
    const cash     = parseFloat(document.getElementById('cashInput').value) || total;
    const change   = payMethod === 'cash' ? cash - total : 0;
    const now      = new Date();
    const labels   = { cash: 'Cash', gcash: 'GCash', card: 'Card' };

    const rows = cart.map(i =>
        `<div class="rr"><span>${i.name} x${i.qty}</span><span>₱${(i.price*i.qty).toLocaleString()}</span></div>`
    ).join('');

    document.getElementById('receiptBody').innerHTML = `
        <div class="receipt">
            <div class="receipt-head">
                <div class="receipt-shop-name">CraveDrip Coffee</div>
                <div>Davao City, Philippines</div>
                <div>+63 912 345 6789</div>
            </div>
            <hr class="rh">
            <div class="rr"><span>Order</span><span>#${orderNum}</span></div>
            <div class="rr"><span>Date</span><span>${now.toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'})}</span></div>
            <div class="rr"><span>Time</span><span>${now.toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'})}</span></div>
            <hr class="rh">
            ${rows}
            <hr class="rh">
            <div class="rr"><span>Subtotal</span><span>₱${subtotal.toLocaleString()}</span></div>
            ${discount > 0 ? `<div class="rr"><span>Discount</span><span>-₱${discount.toLocaleString()}</span></div>` : ''}
            <div class="rr bold"><span>TOTAL</span><span>₱${total.toLocaleString()}</span></div>
            <div class="rr" style="font-size:0.72rem"><span>VAT included</span><span>₱${vatIn.toFixed(2)}</span></div>
            <hr class="rh">
            <div class="rr"><span>Payment</span><span>${labels[payMethod]}</span></div>
            ${payMethod==='cash' ? `<div class="rr"><span>Cash</span><span>₱${cash.toLocaleString()}</span></div>` : ''}
            ${payMethod==='cash' ? `<div class="rr"><span>Change</span><span>₱${change.toLocaleString()}</span></div>` : ''}
            <hr class="rh">
            <div style="text-align:center">Thank you for your visit!</div>
            <div style="text-align:center;font-size:0.72rem">Please come again ☕</div>
        </div>`;

    openModal('receiptModal');
}

function printReceipt() {
    const html = document.getElementById('receiptBody').innerHTML;
    const w = window.open('', '_blank');
    w.document.write(`<html><head><title>Receipt #${orderNum}</title>
        <style>body{font-family:'Courier New',monospace;font-size:12px;margin:16px;max-width:300px}
        .rr{display:flex;justify-content:space-between}.rh{border:none;border-top:1px dashed #bbb;margin:5px 0}
        .bold{font-weight:700}.receipt-head{text-align:center}.receipt-shop-name{font-size:15px;font-weight:700}
        </style></head><body>${html}</body></html>`);
    w.document.close();
    w.print();
}

function newOrder() {
    cart = [];
    document.getElementById('discountInput').value = '';
    activeCat = 'all';
    searchQ = '';
    document.getElementById('searchInput').value = '';
    document.querySelectorAll('.pos-cats .cat-pill').forEach(el =>
        el.classList.toggle('active', el.dataset.cat === 'all'));
    closeModal('receiptModal');
    renderMenu();
    refreshCart();
}

// ── SESSION STORAGE ───────────────────────────────────
function saveSession(total) {
    let orders = JSON.parse(sessionStorage.getItem('posOrders') || '[]');
    orders.push({
        id: `ORD-${String(orderNum).padStart(3,'0')}`,
        time: new Date().toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'}),
        items: cart.map(i => i.qty > 1 ? `${i.name} x${i.qty}` : i.name),
        total,
        payment: payMethod.charAt(0).toUpperCase() + payMethod.slice(1),
    });
    sessionStorage.setItem('posOrders', JSON.stringify(orders));
    sessionStorage.setItem('lastOrderNum', String(orderNum));
    orderNum++;
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
