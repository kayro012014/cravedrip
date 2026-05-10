<?php
require 'auth_check.php';
$userName = $_SESSION['user_name'];
$userRole = ucfirst($_SESSION['user_role']);
$initials = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $userName), 0, 2)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory — CraveDrip Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
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
        <a href="../index.php"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="pos.php"><i class="fas fa-cash-register"></i> Point of Sale</a>
        <a href="inventory.php" class="active"><i class="fas fa-boxes"></i> Inventory</a>
        <div class="nav-section" style="margin-top:1rem">Shop</div>
        <a href="../../index.html" target="_blank"><i class="fas fa-store"></i> View Website</a>
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
        <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
    </div>
</aside>

<!-- MAIN -->
<main class="main-content">
    <header class="top-header">
        <div>
            <div class="header-title">Inventory</div>
            <div class="header-sub">Track stock levels and manage supplies</div>
        </div>
        <div class="header-right">
            <span id="live-clock"></span>
            <button class="btn btn-primary btn-sm" onclick="openAdd()">
                <i class="fas fa-plus"></i> Add Item
            </button>
        </div>
    </header>

    <div class="page-body">

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon caramel"><i class="fas fa-layer-group"></i></div>
                <div><div class="stat-value" id="sTotalSKU">0</div><div class="stat-label">Total SKUs</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-exclamation-triangle"></i></div>
                <div><div class="stat-value" id="sLow">0</div><div class="stat-label">Low Stock</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
                <div><div class="stat-value" id="sOut">0</div><div class="stat-label">Out of Stock</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-peso-sign"></i></div>
                <div><div class="stat-value" id="sValue" style="font-size:1.1rem">₱0</div><div class="stat-label">Inventory Value</div></div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="inv-toolbar">
            <div class="search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search items…" oninput="setSearch(this.value)">
            </div>
            <div class="cat-pills">
                <button class="cat-pill active" data-cat="all"         onclick="setCat('all')">All</button>
                <button class="cat-pill"         data-cat="ingredients" onclick="setCat('ingredients')"><i class="fas fa-seedling"></i> Ingredients</button>
                <button class="cat-pill"         data-cat="supplies"    onclick="setCat('supplies')"><i class="fas fa-box"></i> Supplies</button>
                <button class="cat-pill"         data-cat="baked"       onclick="setCat('baked')"><i class="fas fa-bread-slice"></i> Baked Goods</button>
            </div>
        </div>

        <!-- Table -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th data-sort="name"         onclick="sortBy('name')">Item</th>
                        <th data-sort="stock"         onclick="sortBy('stock')">Stock</th>
                        <th data-sort="reorderLevel"  onclick="sortBy('reorderLevel')">Reorder At</th>
                        <th data-sort="costPrice"     onclick="sortBy('costPrice')">Cost Price</th>
                        <th data-sort="sellPrice"     onclick="sortBy('sellPrice')">Sell Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="invTbody"></tbody>
            </table>
        </div>

    </div>
</main>

<!-- ADD / EDIT ITEM MODAL -->
<div class="modal-overlay" id="itemModal">
    <div class="modal">
        <div class="modal-header">
            <h2 id="modalTitle">Add New Item</h2>
            <button class="modal-close" onclick="closeModal('itemModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form id="itemForm" onsubmit="event.preventDefault(); saveItem()">
                <div class="form-group">
                    <label>Item Name *</label>
                    <input type="text" id="fName" placeholder="e.g. Arabica Beans" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Category *</label>
                        <select id="fCat" required>
                            <option value="">Select…</option>
                            <option value="ingredients">Ingredients</option>
                            <option value="supplies">Supplies</option>
                            <option value="baked">Baked Goods</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Unit *</label>
                        <select id="fUnit" required>
                            <option value="">Select…</option>
                            <option value="kg">kg</option>
                            <option value="g">g</option>
                            <option value="L">L</option>
                            <option value="ml">ml</option>
                            <option value="pcs">pcs</option>
                            <option value="pack">pack</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Current Stock *</label>
                        <input type="number" id="fStock" min="0" step="0.01" placeholder="0" required>
                    </div>
                    <div class="form-group">
                        <label>Reorder Level *</label>
                        <input type="number" id="fReorder" min="0" step="0.01" placeholder="0" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Cost Price (₱) *</label>
                        <input type="number" id="fCostPrice" min="0" step="0.01" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label>Selling Price (₱)</label>
                        <input type="number" id="fSellPrice" min="0" step="0.01" placeholder="Optional">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('itemModal')">Cancel</button>
            <button class="btn btn-primary" onclick="saveItem()">
                <i class="fas fa-save"></i> Save Item
            </button>
        </div>
    </div>
</div>

<!-- STOCK ADJUSTMENT MODAL -->
<div class="modal-overlay" id="adjModal">
    <div class="modal" style="max-width:400px">
        <div class="modal-header">
            <h2>Adjust Stock</h2>
            <button class="modal-close" onclick="closeModal('adjModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div style="margin-bottom:1rem">
                <div style="font-weight:700;color:var(--color-coffee);font-size:1rem" id="adjName"></div>
                <div style="font-size:0.8rem;color:var(--color-latte);margin-top:3px" id="adjCurrent"></div>
            </div>

            <div class="adj-btns">
                <button class="adj-btn add active" data-type="add" onclick="setAdjType('add')">
                    <i class="fas fa-plus-circle"></i> Add Stock
                </button>
                <button class="adj-btn deduct" data-type="deduct" onclick="setAdjType('deduct')">
                    <i class="fas fa-minus-circle"></i> Deduct Stock
                </button>
            </div>

            <div class="form-group">
                <label>Quantity</label>
                <input type="number" id="adjQty" min="0.01" step="0.01" placeholder="Enter quantity">
            </div>
            <div class="form-group">
                <label>Reason <span style="color:var(--color-latte);font-weight:400">(optional)</span></label>
                <input type="text" id="adjReason" placeholder="e.g. New delivery, spoilage…">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('adjModal')">Cancel</button>
            <button class="btn btn-primary" onclick="confirmAdj()">
                <i class="fas fa-check"></i> Confirm
            </button>
        </div>
    </div>
</div>

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

<script src="../js/data.js"></script>
<script src="../js/inventory.js"></script>
<script>
const PROFILE_URL = '/cravedrip/admin/pages/profile.php';
let   _profileTab = 'info';

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
        fd.append('action',  'update_password');
        fd.append('current', document.getElementById('pwCurrent').value);
        fd.append('new',     document.getElementById('pwNew').value);
        fd.append('confirm', document.getElementById('pwConfirm').value);
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
