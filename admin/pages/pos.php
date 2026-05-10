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
    <title>POS — CraveDrip Admin</title>
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
        <a href="pos.php" class="active"><i class="fas fa-cash-register"></i> Point of Sale</a>
        <a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a>
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
            <div class="header-title">Point of Sale</div>
            <div class="header-sub">Select items and process payment</div>
        </div>
        <div class="header-right">
            <span id="live-clock"></span>
            <button class="btn btn-ghost btn-sm" onclick="clearCart()">
                <i class="fas fa-trash-alt"></i> Clear Order
            </button>
        </div>
    </header>

    <div class="page-body">
        <div class="pos-layout">

            <!-- LEFT: Menu Panel -->
            <div class="pos-panel">
                <div class="pos-topbar">
                    <div class="search-wrap" style="flex:1">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search menu…"
                               oninput="setSearch(this.value)">
                    </div>
                </div>

                <div class="pos-cats">
                    <button class="cat-pill active" data-cat="all"    onclick="setCat('all')">All</button>
                    <button class="cat-pill"         data-cat="coffee" onclick="setCat('coffee')"><i class="fas fa-coffee"></i> Coffee</button>
                    <button class="cat-pill"         data-cat="cold"   onclick="setCat('cold')"><i class="fas fa-ice-cream"></i> Cold Drinks</button>
                    <button class="cat-pill"         data-cat="tea"    onclick="setCat('tea')"><i class="fas fa-leaf"></i> Tea</button>
                    <button class="cat-pill"         data-cat="pastry" onclick="setCat('pastry')"><i class="fas fa-bread-slice"></i> Pastries</button>
                </div>

                <div class="pos-grid" id="posGrid"></div>
            </div>

            <!-- RIGHT: Cart Panel -->
            <div class="pos-panel">
                <div class="cart-header">
                    <h3><i class="fas fa-shopping-basket" style="color:var(--color-caramel);margin-right:6px"></i>Current Order</h3>
                    <span class="cart-num" id="cartOrderNum">#1001</span>
                </div>

                <div class="cart-items" id="cartItems"></div>

                <div class="cart-totals">
                    <div class="totals-row">
                        <span>Subtotal</span>
                        <span id="subtotalAmt">₱0</span>
                    </div>
                    <div class="totals-row">
                        <label for="discountInput">Discount (₱)</label>
                        <input type="number" id="discountInput" min="0" placeholder="0"
                               oninput="calcTotals()">
                    </div>
                    <div class="totals-row" style="font-size:0.75rem;color:var(--color-latte)">
                        <span>VAT included</span>
                        <span id="vatAmt">₱0.00</span>
                    </div>
                    <div class="totals-row grand">
                        <span>TOTAL</span>
                        <span id="totalAmt">₱0</span>
                    </div>
                </div>

                <div class="cart-actions">
                    <button class="btn btn-success btn-lg btn-block" id="processBtn"
                            onclick="openPayment()" disabled>
                        <i class="fas fa-credit-card"></i> Process Payment
                    </button>
                </div>
            </div>

        </div>
    </div>
</main>

<!-- PAYMENT MODAL -->
<div class="modal-overlay" id="payModal">
    <div class="modal">
        <div class="modal-header">
            <h2>Process Payment</h2>
            <button class="modal-close" onclick="closeModal('payModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="pay-tabs">
                <button class="pay-tab" data-m="cash" onclick="selectMethod('cash')">
                    <i class="fas fa-money-bill-wave"></i> Cash
                </button>
                <button class="pay-tab" data-m="gcash" onclick="selectMethod('gcash')">
                    <i class="fas fa-mobile-alt"></i> GCash
                </button>
                <button class="pay-tab" data-m="card" onclick="selectMethod('card')">
                    <i class="fas fa-credit-card"></i> Card
                </button>
            </div>

            <div class="pay-summary">
                <div class="pay-row"><span>Subtotal</span><span id="pSubtotal">₱0</span></div>
                <div class="pay-row"><span>Discount</span><span id="pDiscount">—</span></div>
                <div class="pay-row total"><span>Total</span><span id="pTotal">₱0</span></div>
            </div>

            <div id="cashSection">
                <div class="form-group">
                    <label>Cash Received (₱)</label>
                    <input type="number" id="cashInput" min="0" placeholder="Enter amount"
                           oninput="calcChange()">
                </div>
                <div class="change-box" id="changeBox" style="display:none">
                    <div class="cl">Change</div>
                    <div class="ca" id="changeAmt">₱0</div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('payModal')">Cancel</button>
            <button class="btn btn-success" onclick="confirmPayment()">
                <i class="fas fa-check"></i> Confirm Payment
            </button>
        </div>
    </div>
</div>

<!-- RECEIPT MODAL -->
<div class="modal-overlay" id="receiptModal">
    <div class="modal">
        <div class="modal-header">
            <h2><i class="fas fa-check-circle" style="color:var(--color-success);margin-right:8px"></i>Payment Successful</h2>
            <button class="modal-close" onclick="newOrder()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="receiptBody"></div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="printReceipt()">
                <i class="fas fa-print"></i> Print
            </button>
            <button class="btn btn-primary" onclick="newOrder()">
                <i class="fas fa-plus"></i> New Order
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
<script src="../js/pos.js"></script>
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
