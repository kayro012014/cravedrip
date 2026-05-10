/* CraveDrip — Shared API client + UI utilities */

const API_BASE = '/cravedrip/api';

function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function apiGet(path) {
    const res = await fetch(`${API_BASE}/${path}`);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
}

async function apiPost(path, data) {
    const isFormData = data instanceof FormData;
    const headers    = { 'X-CSRF-Token': getCsrf() };
    if (!isFormData) headers['Content-Type'] = 'application/json';

    const res = await fetch(`${API_BASE}/${path}`, {
        method: 'POST',
        headers,
        body: isFormData ? data : JSON.stringify(data),
    });
    return res.json();
}

function showToast(type, msg) {
    const old = document.getElementById('cravedrip-toast');
    if (old) old.remove();

    const t    = document.createElement('div');
    t.id       = 'cravedrip-toast';
    const bg   = type === 'success' ? '#27ae60' : '#e74c3c';
    const icon = type === 'success' ? 'check'   : 'times';

    Object.assign(t.style, {
        position:      'fixed',
        bottom:        '2.5rem',
        left:          '50%',
        transform:     'translateX(-50%) translateY(16px)',
        background:    '#1a0f08',
        color:         '#f5efe6',
        padding:       '0.8rem 1.4rem',
        borderRadius:  '100px',
        fontSize:      '0.85rem',
        fontWeight:    '600',
        fontFamily:    "'DM Sans', sans-serif",
        zIndex:        '999999',
        display:       'flex',
        alignItems:    'center',
        gap:           '0.6rem',
        boxShadow:     '0 8px 32px rgba(0,0,0,0.4)',
        opacity:       '0',
        transition:    'transform 0.3s ease, opacity 0.3s ease',
        pointerEvents: 'none',
        whiteSpace:    'nowrap',
    });

    t.innerHTML = `
        <span style="width:20px;height:20px;border-radius:50%;background:${bg};color:#fff;
                     display:flex;align-items:center;justify-content:center;
                     font-size:0.65rem;flex-shrink:0">
            <i class="fas fa-${icon}"></i>
        </span>
        <span>${msg}</span>`;

    document.body.appendChild(t);
    requestAnimationFrame(() => requestAnimationFrame(() => {
        t.style.opacity   = '1';
        t.style.transform = 'translateX(-50%) translateY(0)';
    }));
    setTimeout(() => {
        t.style.opacity   = '0';
        t.style.transform = 'translateX(-50%) translateY(16px)';
        setTimeout(() => t.remove(), 300);
    }, 3000);
}

function initClock() {
    const el = document.getElementById('live-clock');
    if (!el) return;
    const tick = () => el.textContent = new Date().toLocaleString('en-PH', {
        weekday: 'short', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
    });
    tick();
    setInterval(tick, 1000);
}
