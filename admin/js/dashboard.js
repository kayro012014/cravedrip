/* CraveDrip Dashboard — pulls live data from API */

document.addEventListener('DOMContentLoaded', () => {
    initClock();
    loadDashboard();
});

async function loadDashboard() {
    try {
        const data = await apiGet('dashboard.php');
        if (!data.ok) throw new Error(data.error);

        document.getElementById('dRevenue').textContent = '₱' + data.revenue.toLocaleString();
        document.getElementById('dOrders').textContent  = data.orders;
        document.getElementById('dAvg').textContent     = '₱' + Math.round(data.avg).toLocaleString();
        document.getElementById('dAlerts').textContent  = data.alerts;

        renderOrders(data.recent);
        renderAlerts(data.out, data.low);
    } catch (e) {
        console.error('Dashboard load failed:', e);
    }
}

function renderOrders(orders) {
    const tbody = document.getElementById('recentTbody');
    if (!orders || !orders.length) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--color-latte)">No orders today yet.</td></tr>`;
        return;
    }
    const mc = {
        Cash:'badge-success', GCash:'badge-info', Card:'badge-default',
        cash:'badge-success', gcash:'badge-info', card:'badge-default',
    };
    tbody.innerHTML = orders.map(o => `
        <tr>
            <td style="font-weight:600;color:var(--color-coffee)">${o.order_number}</td>
            <td style="color:var(--color-latte)">${o.time}</td>
            <td style="font-size:0.82rem">
                ${(o.items || []).slice(0, 2).join(', ')}
                ${(o.items || []).length > 2 ? ` +${o.items.length - 2}` : ''}
            </td>
            <td style="font-weight:700;color:var(--color-caramel)">₱${o.total.toLocaleString()}</td>
            <td><span class="badge ${mc[o.payment] || 'badge-default'}">${o.payment}</span></td>
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
