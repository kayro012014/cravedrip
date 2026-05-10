<?php
require 'pages/auth_check.php';
$userName = $_SESSION['user_name'];
$userRole = ucfirst($_SESSION['user_role']);
$initials = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $userName), 0, 2)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — CraveDrip Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <?= csrf_meta() ?>
</head>
<body>
<script>
    if (!sessionStorage.getItem('cravedrip_tab')) {
        window.location.replace('/cravedrip/admin/pages/logout.php');
    }
</script>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo">Crave<span>Drip</span></div>
        <div class="admin-badge">Admin Panel</div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Main</div>
        <a href="index.php" class="active"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="pages/pos.php"><i class="fas fa-cash-register"></i> Point of Sale</a>
        <a href="pages/inventory.php"><i class="fas fa-boxes"></i> Inventory</a>
        <div class="nav-section" style="margin-top:1rem">Shop</div>
        <a href="../index.html" target="_blank"><i class="fas fa-store"></i> View Website</a>
    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-user" onclick="openProfileModal()">
            <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
            <div>
                <div class="user-name"><?= htmlspecialchars($userName) ?></div>
                <div class="user-role"><?= htmlspecialchars($userRole) ?></div>
                <div class="user-edit-hint" style="color:rgba(255,255,255,0.6)">Click to edit profile</div>
            </div>
        </div>
        <a href="pages/logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
    </div>
</aside>

<!-- MAIN -->
<main class="main-content">
    <header class="top-header">
        <div>
            <div class="header-title">Dashboard</div>
            <div class="header-sub">Welcome back! Here's what's happening today.</div>
        </div>
        <div class="header-right">
            <span id="live-clock"></span>
        </div>
    </header>

    <div class="page-body">

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon caramel"><i class="fas fa-peso-sign"></i></div>
                <div><div class="stat-value" id="dRevenue">₱0</div><div class="stat-label">Today's Revenue</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-receipt"></i></div>
                <div><div class="stat-value" id="dOrders">0</div><div class="stat-label">Orders Today</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-chart-line"></i></div>
                <div><div class="stat-value" id="dAvg">₱0</div><div class="stat-label">Avg. Order Value</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-exclamation-triangle"></i></div>
                <div><div class="stat-value" id="dAlerts">0</div><div class="stat-label">Stock Alerts</div></div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="pages/pos.php" class="qa-card">
                <div class="qa-icon"><i class="fas fa-cash-register"></i></div>
                <div><h4>Open POS</h4><p>Process customer orders</p></div>
            </a>
            <a href="pages/inventory.php" class="qa-card">
                <div class="qa-icon"><i class="fas fa-boxes"></i></div>
                <div><h4>Manage Inventory</h4><p>View stock levels & restock</p></div>
            </a>
        </div>

        <!-- Two-column grid -->
        <div class="dash-grid">
            <!-- Recent Orders -->
            <div class="table-card">
                <div class="table-card-header">
                    <h3>Recent Orders</h3>
                    <a href="pages/pos.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> New Order
                    </a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Time</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody id="recentTbody"></tbody>
                </table>
            </div>

            <!-- Stock Alerts -->
            <div class="table-card">
                <div class="table-card-header">
                    <h3>Stock Alerts</h3>
                    <a href="pages/inventory.php" class="btn btn-sm btn-ghost">
                        <i class="fas fa-arrow-right"></i> Inventory
                    </a>
                </div>
                <div style="padding:1rem" id="alertList"></div>
            </div>
        </div>

    </div>
</main>

<!-- PROFILE MODAL -->
<div class="modal-overlay" id="profileModal">
    <div class="modal" style="max-width:440px">
        <div class="modal-header">
            <h2><i class="fas fa-user-circle" style="color:var(--color-caramel);margin-right:8px"></i>My Profile</h2>
            <button class="modal-close" onclick="closeProfileModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="profile-tabs">
                <button class="profile-tab active" onclick="switchProfileTab('info')"><i class="fas fa-id-card"></i> Profile</button>
                <button class="profile-tab"        onclick="switchProfileTab('password')"><i class="fas fa-lock"></i> Password</button>
            </div>

            <div id="profileInfoTab">
                <div class="form-group">
                    <label>Display Name</label>
                    <input type="text" id="profileName" placeholder="Your full name">
                </div>
                <div id="profileMsg"></div>
            </div>

            <div id="profilePwTab" style="display:none">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" id="pwCurrent" placeholder="Enter current password">
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" id="pwNew" placeholder="At least 6 characters">
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" id="pwConfirm" placeholder="Repeat new password">
                </div>
                <div id="pwMsg"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeProfileModal()">Cancel</button>
            <button class="btn btn-primary" id="profileSaveBtn" onclick="saveProfile()">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </div>
</div>

<script src="js/data.js"></script>
<script src="js/dashboard.js"></script>
<script>
const PROFILE_URL  = '/cravedrip/admin/pages/profile.php';
let   _profileTab  = 'info';

function openProfileModal() {
    document.getElementById('profileName').value = <?= json_encode($_SESSION['user_name']) ?>;
    ['pwCurrent','pwNew','pwConfirm'].forEach(id => document.getElementById(id).value = '');
    ['profileMsg','pwMsg'].forEach(id => document.getElementById(id).innerHTML = '');
    switchProfileTab('info');
    document.getElementById('profileModal').classList.add('open');
}
function closeProfileModal() {
    document.getElementById('profileModal').classList.remove('open');
}
function switchProfileTab(tab) {
    _profileTab = tab;
    document.getElementById('profileInfoTab').style.display = tab === 'info'     ? '' : 'none';
    document.getElementById('profilePwTab').style.display   = tab === 'password' ? '' : 'none';
    document.querySelectorAll('.profile-tab').forEach((btn, i) => {
        btn.classList.toggle('active', (i === 0 && tab === 'info') || (i === 1 && tab === 'password'));
    });
}
async function saveProfile() {
    const btn = document.getElementById('profileSaveBtn');
    btn.disabled = true;
    const fd = new FormData();
    if (_profileTab === 'info') {
        fd.append('action', 'update_name');
        fd.append('name', document.getElementById('profileName').value.trim());
    } else {
        fd.append('action',   'update_password');
        fd.append('current',  document.getElementById('pwCurrent').value);
        fd.append('new',      document.getElementById('pwNew').value);
        fd.append('confirm',  document.getElementById('pwConfirm').value);
    }
    const res  = await fetch(PROFILE_URL, { method: 'POST', headers: { 'X-CSRF-Token': getCsrf() }, body: fd });
    const data = await res.json();
    btn.disabled = false;
    const msgEl = document.getElementById(_profileTab === 'info' ? 'profileMsg' : 'pwMsg');
    if (data.ok) {
        if (_profileTab === 'info') {
            document.querySelectorAll('.user-name').forEach(el => el.textContent = data.name);
            const initials = data.name.split(' ').slice(0,2).map(w => w[0].toUpperCase()).join('');
            document.querySelectorAll('.user-avatar').forEach(el => el.textContent = initials);
        }
        closeProfileModal();
        showToast('success', _profileTab === 'info' ? 'Name updated!' : 'Password changed!');
    } else {
        msgEl.innerHTML = `<div class="form-msg error">${data.error}</div>`;
    }
}
</script>
</body>
</html>
