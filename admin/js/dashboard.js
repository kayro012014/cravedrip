/* CraveDrip Dashboard — Frontend Logic */

document.addEventListener('DOMContentLoaded', () => {
    initClock();
    loadDashboard();
});

function loadDashboard() {
    const session = JSON.parse(sessionStorage.getItem('posOrders') || '[]');
    const all = [...SAMPLE_ORDERS, ...session];

    const revenue = all.reduce((s, o) => s + o.total, 0);
    const avg     = all.length ? Math.round(revenue / all.length) : 0;
    const low     = INVENTORY_ITEMS.filter(i => i.stock > 0 && i.stock <= i.reorderLevel);
    const out     = INVENTORY_ITEMS.filter(i => i.stock <= 0);

    document.getElementById('dRevenue').textContent = '₱' + revenue.toLocaleString();
    document.getElementById('dOrders').textContent  = all.length;
    document.getElementById('dAvg').textContent     = '₱' + avg.toLocaleString();
    document.getElementById('dAlerts').textContent  = low.length + out.length;

    renderOrders([...all].reverse().slice(0, 10));
    renderAlerts(out, low);
}

function renderOrders(orders) {
    const tbody = document.getElementById('recentTbody');
    if (!orders.length) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--color-latte)">No orders today yet.</td></tr>`;
        return;
    }
    const mc = { Cash:'badge-success', GCash:'badge-info', Card:'badge-default', cash:'badge-success', gcash:'badge-info', card:'badge-default' };
    tbody.innerHTML = orders.map(o => `
        <tr>
            <td style="font-weight:600;color:var(--color-coffee)">${o.id}</td>
            <td style="color:var(--color-latte)">${o.time}</td>
            <td style="font-size:0.82rem">${o.items.slice(0,2).join(', ')}${o.items.length>2 ? ` +${o.items.length-2}` : ''}</td>
            <td style="font-weight:700;color:var(--color-caramel)">₱${o.total.toLocaleString()}</td>
            <td><span class="badge ${mc[o.payment]||'badge-default'}">${o.payment}</span></td>
        </tr>`).join('');
}

function renderAlerts(out, low) {
    const el = document.getElementById('alertList');
    if (!out.length && !low.length) {
        el.innerHTML = `<div class="empty-state" style="padding:1.5rem">
            <i class="fas fa-check-circle" style="color:var(--color-success)"></i>
            <p>All stock levels are healthy!</p></div>`;
        return;
    }
    el.innerHTML = [
        ...out.map(i => ({ i, type: 'out' })),
        ...low.map(i => ({ i, type: 'low' })),
    ].map(({ i, type }) => `
        <div class="alert-item ${type === 'out' ? 'out' : ''}">
            <i class="fas ${type === 'out' ? 'fa-times-circle' : 'fa-exclamation-triangle'}"></i>
            <div>
                <div class="al-name">${i.name}</div>
                <div class="al-detail">${type === 'out'
                    ? 'Out of stock — needs restocking'
                    : `${i.stock} ${i.unit} left (min ${i.reorderLevel})`}</div>
            </div>
        </div>`).join('');
}

function initClock() {
    const el = document.getElementById('live-clock');
    const tick = () => el.textContent = new Date().toLocaleString('en-PH',
        {weekday:'short',month:'short',day:'numeric',hour:'2-digit',minute:'2-digit',second:'2-digit'});
    tick();
    setInterval(tick, 1000);
}
