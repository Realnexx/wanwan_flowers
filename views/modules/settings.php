<?php
date_default_timezone_set('Asia/Manila');
$active_tab = $_GET['tab'] ?? 'general';
$title = 'System Settings';
$cashiers = $cashiers ?? []; // Fallback for undefined variable
ob_start();
?>

<div class="settings-page">
    <!-- Header Area -->
    <div class="settings-page-header mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h4 class="settings-page-title"><i class="ti ti-settings me-2" style="color:#5d87ff;"></i>System Configuration</h4>
                <p class="settings-page-subtitle">Manage your store profiles, financial rules, and system aesthetics</p>
            </div>
            <div class="settings-last-updated">
                <i class="ti ti-clock me-1"></i> Last updated: <?= date('F j Y g:i a') ?>
            </div>
        </div>
    </div>

    <!-- Main Settings Container -->
    <div class="row g-4">
        <!-- Sidebar Navigation -->
        <div class="col-xl-3 col-lg-4">
            <div class="settings-sidebar-card p-3 sticky-top" style="top: 80px; z-index: 10;">
                <div class="nav flex-column nav-pills settings-nav" role="tablist">
                    <button class="nav-link <?= $active_tab === 'general' ? 'active' : '' ?> d-flex align-items-center gap-3 py-3 px-4 mb-2"
                        data-bs-toggle="pill" data-bs-target="#tab-general" type="button">
                        <div class="nav-icon-wrap"><span class="material-symbols-outlined">storefront</span></div>
                        <div class="text-start">
                            <div class="nav-label">Store Profile</div>
                            <div class="nav-desc text-muted small">Name, Logo, Address</div>
                        </div>
                    </button>

                    <button class="nav-link <?= $active_tab==='paymongo'?'active':'' ?> d-flex align-items-center gap-3 py-3 px-4 mb-2" data-bs-toggle="pill" data-bs-target="#tab-paymongo" type="button"><div class="nav-icon-wrap"><span class="material-symbols-outlined">account_balance_wallet</span></div><div class="text-start"><div class="nav-label">PayMongo</div><div class="nav-desc text-muted small">Online payments & methods</div></div></button>

                    <button class="nav-link <?= $active_tab === 'financial' ? 'active' : '' ?> d-flex align-items-center gap-3 py-3 px-4 mb-2"
                        data-bs-toggle="pill" data-bs-target="#tab-financial" type="button">
                        <div class="nav-icon-wrap"><span class="material-symbols-outlined">payments</span></div>
                        <div class="text-start">
                            <div class="nav-label">Financial & Tax</div>
                            <div class="nav-desc text-muted small">Currency, Taxes, Fees</div>
                        </div>
                    </button>

                    <button class="nav-link <?= $active_tab === 'pos' ? 'active' : '' ?> d-flex align-items-center gap-3 py-3 px-4 mb-2"
                        data-bs-toggle="pill" data-bs-target="#tab-pos" type="button">
                        <div class="nav-icon-wrap"><span class="material-symbols-outlined">point_of_sale</span></div>
                        <div class="text-start">
                            <div class="nav-label">POS Operations</div>
                            <div class="nav-desc text-muted small">Receipts, Orders, Stock</div>
                        </div>
                    </button>

                    <button class="nav-link <?= $active_tab === 'appearance' ? 'active' : '' ?> d-flex align-items-center gap-3 py-3 px-4 mb-2"
                        data-bs-toggle="pill" data-bs-target="#tab-appearance" type="button">
                        <div class="nav-icon-wrap"><span class="material-symbols-outlined">palette</span></div>
                        <div class="text-start">
                            <div class="nav-label">System Appearance</div>
                            <div class="nav-desc text-muted small">Themes, Colors, Branding</div>
                        </div>
                    </button>

                    <button class="nav-link <?= $active_tab === 'security' ? 'active' : '' ?> d-flex align-items-center gap-3 py-3 px-4 mb-2"
                        data-bs-toggle="pill" data-bs-target="#tab-security" type="button">
                        <div class="nav-icon-wrap"><span class="material-symbols-outlined">security</span></div>
                        <div class="text-start">
                            <div class="nav-label">Security & Privacy</div>
                            <div class="nav-desc text-muted small">Access, Key, PINs</div>
                        </div>
                    </button>

                    <button class="nav-link <?= $active_tab==='backup'?'active':'' ?> d-flex align-items-center gap-3 py-3 px-4 mb-2" data-bs-toggle="pill" data-bs-target="#tab-backup" type="button"><div class="nav-icon-wrap"><span class="material-symbols-outlined">backup</span></div><div class="text-start"><div class="nav-label">Database backups</div><div class="nav-desc text-muted small">Schedule, recovery, verification</div></div></button>

                    <button class="nav-link <?= $active_tab === 'password' ? 'active' : '' ?> d-flex align-items-center gap-3 py-3 px-4"
                        data-bs-toggle="pill" data-bs-target="#tab-password" type="button">
                        <div class="nav-icon-wrap"><span class="material-symbols-outlined">lock_reset</span></div>
                        <div class="text-start">
                            <div class="nav-label">Account Password</div>
                            <div class="nav-desc text-muted small">Update Your Credentials</div>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="col-xl-9 col-lg-8">
            <div class="tab-content">
                <?php require __DIR__.'/../partials/paymongo_settings.php'; ?>
                <?php require __DIR__.'/../partials/backup_settings.php'; ?>

                <!-- GENERAL SETTINGS -->
                <div class="tab-pane fade <?= $active_tab === 'general' ? 'show active' : '' ?>" id="tab-general">
                    <form action="<?= url('?route=admin/settings') ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="setting_category" value="general">
<div class="card p-3 mb-3"><h5>Online ordering</h5><p class="text-muted">Allow customers to place pickup and flower delivery orders. Existing orders can still be tracked and processed when closed.</p><label for="flower-delivery-fee" class="form-label mt-3">Flower delivery fee (PHP)</label><input class="form-control mb-3" id="flower-delivery-fee" name="settings[general][flower_delivery_fee]" type="number" step="0.01" min="0" max="999999" value="<?= htmlspecialchars($all_settings['general']['flower_delivery_fee'] ?? '0.00', ENT_QUOTES, 'UTF-8') ?>"><p class="small text-muted">Flat delivery fee shown before placing a flower order. Zero means no delivery charge.</p><label for="online-ordering">Accept online orders</label><select class="form-select" id="online-ordering" name="settings[general][online_ordering_enabled]"><option value="1" <?= ($all_settings['general']['online_ordering_enabled'] ?? '1')==='1'?'selected':'' ?>>Enabled</option><option value="0" <?= ($all_settings['general']['online_ordering_enabled'] ?? '1')==='0'?'selected':'' ?>>Disabled</option></select></div>
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <div class="settings-section-card p-4">
                            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                                <div class="section-icon"><span class="material-symbols-outlined">info</span></div>
                                <h5 class="mb-0 fw-bold">Store Information</h5>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-12 text-center mb-3">
                                    <div class="position-relative d-inline-block">
                                        <img src="<?= url($all_settings['general']['store_logo'] ?? 'assets/img/logo.png') ?>"
                                            class="store-logo-preview rounded-4 shadow-sm" id="logoPreview">
                                        <label for="store_logo" class="logo-edit-btn">
                                            <i class="fa-solid fa-camera"></i>
                                        </label>
                                        <input type="file" name="store_logo" id="store_logo" class="d-none" onchange="previewLogo(this)">
                                    </div>
                                    <p class="text-muted small mt-2">Upload your store logo (PNG, JPG max 2MB)</p>
                                </div>

                                <div class="col-md-6">
                                    <div class="premium-input-group">
                                        <label class="small fw-semibold text-muted mb-2">Store Name</label>
                                        <input type="text" name="settings[general][store_name]" class="form-control premium-field"
                                            value="<?= $all_settings['general']['store_name'] ?? 'Stacey Halu Halu' ?>"
                                            required onkeyup="document.getElementById('receiptStoreName').innerText = this.value.toUpperCase()">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="premium-input-group">
                                        <label class="small fw-semibold text-muted mb-2">Business Email</label>
                                        <input type="email" name="settings[general][store_email]" class="form-control premium-field"
                                            value="<?= $all_settings['general']['store_email'] ?? 'hello@haluhalu.com' ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="premium-input-group">
                                        <label class="small fw-semibold text-muted mb-2">Contact Phone</label>
                                        <input type="text" name="settings[general][store_phone]" class="form-control premium-field"
                                            value="<?= $all_settings['general']['store_phone'] ?? '+63 912 345 6789' ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="premium-input-group">
                                        <label class="small fw-semibold text-muted mb-2">Alternate Contact</label>
                                        <input type="text" name="settings[general][store_phone_alt]" class="form-control premium-field"
                                            value="<?= $all_settings['general']['store_phone_alt'] ?? '' ?>">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="premium-input-group">
                                        <label class="small fw-semibold text-muted mb-2">Store Address</label>
                                        <textarea name="settings[general][store_address]" class="form-control premium-field" rows="3"><?= $all_settings['general']['store_address'] ?? '123 Sweet Tooth Blvd, Sugar City' ?></textarea>
                                    </div>
                                </div>

                                <div class="col-12 mt-4 pt-3 border-top">
                                    <div class="d-flex align-items-center gap-3 mb-4 pb-1">
                                        <div class="section-icon bg-zinc-100 text-zinc-900"><span class="material-symbols-outlined">identity_platform</span></div>
                                        <h5 class="mb-0 fw-bold">Executive Branding & Messaging</h5>
                                    </div>
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="premium-input-group">
                                                <label class="small fw-semibold text-muted mb-2">Executive Full Name</label>
                                                <input type="text" name="settings[general][founder_name]" class="form-control premium-field"
                                                    value="<?= $all_settings['general']['founder_name'] ?? 'Stacey Halu' ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="premium-input-group">
                                                <label class="small fw-semibold text-muted mb-2">Executive Title / Designation</label>
                                                <input type="text" name="settings[general][founder_title]" class="form-control premium-field"
                                                    value="<?= $all_settings['general']['founder_title'] ?? 'Founder & CEO' ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="premium-input-group">
                                                <label class="small fw-semibold text-muted mb-2">Executive Focus Quote</label>
                                                <textarea name="settings[general][founder_quote]" class="form-control premium-field" rows="2" placeholder="Enter a tactical or inspirational quote for the team..."><?= $all_settings['general']['founder_quote'] ?? '"The Makati Central branch has seen a 25% increase in efficiency since implementing the new automated prep system. Great job team!"' ?></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="premium-input-group">
                                                <label class="small fw-semibold text-muted mb-2">Current Expansion Milestone</label>
                                                <input type="text" name="settings[general][expansion_milestone]" class="form-control premium-field"
                                                    value="<?= $all_settings['general']['expansion_milestone'] ?? 'Strategic Regional Growth Phase III' ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-5 pt-3 border-top">
                                <button type="submit" class="btn btn-premium-main px-5 py-2 fw-bold">
                                    <i class="fa-solid fa-save me-2"></i> SAVE CHANGES
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- FINANCIAL SETTINGS -->
                <div class="tab-pane fade <?= $active_tab === 'financial' ? 'show active' : '' ?>" id="tab-financial">
                    <form action="<?= url('?route=admin/settings') ?>" method="POST">
                        <input type="hidden" name="setting_category" value="financial"><input type="hidden" name="settings[financial][tax_mode]" value="exclusive">
<div class="card p-3 mb-3"><h5>Checkout rules</h5><p class="text-muted small">Applies to new POS sales and online orders. Tax is calculated after discount. Service charge applies to the discounted listed price; tax does not apply to the service charge. Enable only after reviewing your business rules. Currency is PHP for payment compatibility.</p>
<?php foreach(['tax_enabled'=>'Apply configured tax','service_charge_enabled'=>'Apply service charge','discount_enabled'=>'Allow eligible product discounts'] as $key=>$label): ?><label class="mb-2"><input type="checkbox" name="settings[financial][<?= $key ?>]" value="1" <?= ($all_settings['financial'][$key]??($key==='discount_enabled'?'1':'0'))==='1'?'checked':'' ?>> <?= $label ?></label><?php endforeach; ?>
<label for="discount-cap">Maximum discount on eligible items (%)</label><input id="discount-cap" class="form-control" type="number" min="0" max="100" step="0.01" name="settings[financial][discount_max_percent]" value="<?= htmlspecialchars($all_settings['financial']['discount_max_percent']??'100',ENT_QUOTES) ?>" required><small class="text-muted">Products marked non-discountable are excluded. The cashier enters an amount; this percentage caps it.</small></div>
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <div class="settings-section-card p-4">
                            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                                <div class="section-icon bg-success-subtle text-success"><span class="material-symbols-outlined">monetization_on</span></div>
                                <h5 class="mb-0 fw-bold">Currency & Taxation</h5>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-12">
                                    <div class="premium-input-group">
                                        <label class="small fw-semibold text-muted mb-2">Primary Currency</label>
                                        <input class="form-control" value="PHP — Philippine Peso" readonly aria-label="Currency"><small class="text-muted">Sales and PayMongo payments use Philippine pesos.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="premium-input-group">
                                        <label class="small fw-semibold text-muted mb-2">VAT / Tax Rate (%)</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" name="settings[financial][tax_rate]" class="form-control premium-field"
                                                value="<?= $all_settings['financial']['tax_rate'] ?? '12' ?>">
                                            <span class="input-group-text bg-light border-0 px-3">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="premium-input-group">
                                        <label class="small fw-semibold text-muted mb-2">Service Charge (%)</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" name="settings[financial][service_charge]" class="form-control premium-field"
                                                value="<?= $all_settings['financial']['service_charge'] ?? '0' ?>">
                                            <span class="input-group-text bg-light border-0 px-3">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mt-4">
                                    <div class="p-3 rounded-4 bg-light bg-opacity-50 border">
                                        <div class="form-check form-switch d-flex align-items-center justify-content-between">
                                            <div>
                                                <label class="form-check-label fw-bold small" for="taxInclusive">Prices include Tax</label>
                                                <p class="text-muted small mb-0">Check if your product prices already include the tax amount.</p>
                                            </div>
                                            <input class="form-switch-input" type="checkbox" name="settings[financial][tax_mode]" id="taxInclusive"
                                                value="inclusive" <?= ($all_settings['financial']['tax_mode'] ?? 'exclusive') === 'inclusive' ? 'checked' : '' ?>>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-5 pt-3 border-top">
                                <button type="submit" class="btn btn-premium-main px-5 py-2 fw-bold">
                                    <i class="fa-solid fa-save me-2"></i> SAVE CHANGES
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- POS SETTINGS -->
                <div class="tab-pane fade <?= $active_tab === 'pos' ? 'show active' : '' ?>" id="tab-pos">
                    <form action="<?= url('?route=admin/settings') ?>" method="POST">
                        <input type="hidden" name="setting_category" value="pos"><div class="card p-3 mb-3"><h5>Manual payment methods</h5><p class="text-muted small">Enable methods staff can record at the POS and online-order counter. These record payments received separately; PayMongo remains online only.</p><?php require_once __DIR__.'/../../models/CheckoutPolicy.php';foreach(['Cash','GCash','Card','Maya','Bank Transfer','Credit Card','Debit Card'] as $method): ?><label class="me-3 mb-2"><input type="checkbox" name="settings[pos][manual_payment_methods][]" value="<?= htmlspecialchars($method,ENT_QUOTES) ?>" <?= in_array($method,CheckoutPolicy::methods(),true)?'checked':'' ?>> <?= htmlspecialchars($method) ?></label><?php endforeach; ?></div>
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <div class="settings-section-card p-4">
                            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                                <div class="section-icon bg-warning-subtle text-warning"><span class="material-symbols-outlined">receipt</span></div>
                                <h5 class="mb-0 fw-bold">Operational Rules</h5>
                            </div>

                            <div class="row g-4">
                                <div class="col-12 mt-0">
                                    <h6 class="fw-bold small text-muted text-uppercase mb-3">Receipt Customization</h6>
                                </div>
                                <div class="col-md-6 text-center">
                                    <div class="receipt-preview-small mb-3">
                                        <div class="receipt-simualtor text-start p-3 bg-white border rounded shadow-sm">
                                            <div class="text-center small border-bottom mb-2 pb-2">
                                                <div class="fw-bold" id="receiptStoreName"><?= strtoupper($all_settings['general']['store_name'] ?? 'STACEY HALU HALU') ?></div>
                                                <div style="font-size: 8px;" id="headerPreview"><?= $all_settings['pos']['receipt_header'] ?? 'Visit us: www.haluhalu.com' ?></div>
                                                <div style="font-size: 7px; color: #999; margin-top: 2px;" id="receiptNumPreview">
                                                    <?= ($all_settings['pos']['receipt_prefix'] ?? 'SHH-') . ($all_settings['pos']['receipt_start_number'] ?? '0001') ?>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between" style="font-size: 9px;"><span>Item 1</span><span>₱149.00</span></div>
                                            <div class="d-flex justify-content-between fw-bold mb-2 pt-2 border-top" style="font-size: 10px;"><span>TOTAL</span><span>₱149.00</span></div>
                                            <div class="text-center border-top mt-2 pt-2 fw-semibold" style="font-size: 8px;" id="footerPreview"><?= $all_settings['pos']['receipt_footer'] ?? 'Thank you! Come again.' ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="premium-input-group mb-3">
                                        <label class="small fw-semibold text-muted mb-2">Receipt Header Text</label>
                                        <textarea name="settings[pos][receipt_header]" class="form-control premium-field small" rows="2"
                                            onkeyup="document.getElementById('headerPreview').innerText = this.value"><?= $all_settings['pos']['receipt_header'] ?? 'Visit us: www.haluhalu.com' ?></textarea>
                                    </div>
                                    <div class="premium-input-group mb-3">
                                        <label class="small fw-semibold text-muted mb-2">Receipt Footer Text</label>
                                        <textarea name="settings[pos][receipt_footer]" class="form-control premium-field small" rows="2"
                                            onkeyup="document.getElementById('footerPreview').innerText = this.value"><?= $all_settings['pos']['receipt_footer'] ?? 'Thank you! Come again.' ?></textarea>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div class="premium-input-group">
                                                <label class="small fw-semibold text-muted mb-2">Number Prefix</label>
                                                <input type="text" name="settings[pos][receipt_prefix]" class="form-control premium-field small"
                                                    placeholder="e.g. SHH-" value="<?= $all_settings['pos']['receipt_prefix'] ?? 'SHH-' ?>"
                                                    onkeyup="document.getElementById('receiptNumPreview').innerText = this.value + document.getElementById('receiptStartNum').value">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="premium-input-group">
                                                <label class="small fw-semibold text-muted mb-2">Start Number</label>
                                                <input type="number" name="settings[pos][receipt_start_number]" id="receiptStartNum" class="form-control premium-field small"
                                                    value="<?= $all_settings['pos']['receipt_start_number'] ?? '0001' ?>"
                                                    onkeyup="document.getElementById('receiptNumPreview').innerText = document.getElementsByName('settings[pos][receipt_prefix]')[0].value + this.value">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <hr>
                                </div>

                                <div class="col-md-6">
                                    <div class="premium-input-group">
                                        <label class="small fw-semibold text-muted mb-2">Stock Alert Threshold</label>
                                        <input type="number" name="settings[pos][stock_threshold]" class="form-control premium-field"
                                            value="<?= $all_settings['pos']['stock_threshold'] ?? '10' ?>">
                                        <small class="text-muted d-block mt-1">Get notified when stock falls below this number.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="premium-input-group">
                                        <label class="small fw-semibold text-muted mb-2">Default Order Type</label>
                                        <select name="settings[pos][default_order_type]" class="form-select premium-field">
                                            <option value="Dine-in" <?= ($all_settings['pos']['default_order_type'] ?? 'Dine-in') === 'Dine-in' ? 'selected' : '' ?>>Dine-in</option>
                                            <option value="Take-out" <?= ($all_settings['pos']['default_order_type'] ?? 'Take-out') === 'Take-out' ? 'selected' : '' ?>>Take-out</option>
                                            <option value="Delivery" <?= ($all_settings['pos']['default_order_type'] ?? 'Delivery') === 'Delivery' ? 'selected' : '' ?>>Delivery</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-5 pt-3 border-top">
                                <button type="submit" class="btn btn-premium-main px-5 py-2 fw-bold">
                                    <i class="fa-solid fa-save me-2"></i> SAVE CHANGES
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- SECURITY SETTINGS -->
                <div class="tab-pane fade <?= $active_tab === 'security' ? 'show active' : '' ?>" id="tab-security">
                    <form action="<?= url('?route=admin/settings') ?>" method="POST">
                        <input type="hidden" name="setting_category" value="security">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <div class="settings-section-card p-4">
                            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                                <div class="section-icon bg-danger-subtle text-danger"><span class="material-symbols-outlined">verified_user</span></div>
                                <h5 class="mb-0 fw-bold">System Security Controls</h5>
                            </div>

                            <div class="row g-4 text-start">
                                <div class="col-md-6">
                                    <div class="premium-input-group">
                                        <label class="small fw-semibold text-muted mb-2">Login Attempts Threshold</label>
                                        <input type="number" name="settings[security][login_attempts]" class="form-control premium-field"
                                            value="<?= $all_settings['security']['login_attempts'] ?? '5' ?>">
                                        <small class="text-muted">Max failed logins before account lock.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="premium-input-group">
                                        <label class="small fw-semibold text-muted mb-2">Session Timeout (Minutes)</label>
                                        <input type="number" name="settings[security][session_timeout]" class="form-control premium-field"
                                            value="<?= $all_settings['security']['session_timeout'] ?? '60' ?>">
                                        <small class="text-muted">Auto-logout after inactivity.</small>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <hr class="my-2">
                                </div>

                                <div class="col-md-12">
                                    <div class="p-3 rounded-4 bg-light bg-opacity-50 border mb-3">
                                        <div class="form-check form-switch d-flex align-items-center justify-content-between">
                                            <div>
                                                <label class="form-check-label fw-bold small" for="enforceStrongPass">Enforce Strong Passwords</label>
                                                <p class="text-muted small mb-0">Requires symbols, numbers, and uppercase for new accounts.</p>
                                            </div>
                                            <input class="form-switch-input" type="checkbox" name="settings[security][strong_passwords]" id="enforceStrongPass"
                                                value="1" <?= ($all_settings['security']['strong_passwords'] ?? '0') == '1' ? 'checked' : '' ?>>
                                        </div>
                                    </div>

                                    <div class="p-3 rounded-4 bg-light bg-opacity-50 border">
                                        <div class="form-check form-switch d-flex align-items-center justify-content-between">
                                            <div>
                                                <label class="form-check-label fw-bold small" for="twoFactorPrompt">Require authenticator verification</label>
                                                <p class="text-muted small mb-0">Administrative accounts must verify an authenticator code. First sign-in enrolls the device and supplies recovery codes.</p>
                                            </div>
                                            <input class="form-switch-input" type="checkbox" name="settings[security][2fa_enabled]" id="twoFactorPrompt"
                                                value="1" <?= ($all_settings['security']['2fa_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <hr class="my-2">
                                </div>

                                <div class="col-12"><label class="form-label" for="security-confirm">Current account password</label><input type="password" id="security-confirm" name="current_password" class="form-control" autocomplete="current-password" required><p class="text-muted small">Required to change access policies or PINs. Leave PIN fields blank to keep the current PIN.</p></div>

                                <div class="col-12">
                                    <hr class="my-2">
                                </div>

                                <div class="col-md-12">
                                    <div class="p-3 rounded-4 border" style="background: rgba(255, 193, 7, 0.05); border-color: rgba(255, 193, 7, 0.2) !important;">
                                        <div class="d-flex align-items-center gap-2 mb-3">
                                            <span class="material-symbols-outlined text-warning" style="font-size: 20px;">dialpad</span>
                                            <h6 class="mb-0 fw-bold small">POS Terminal PIN Access</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-7">
                                                <div class="form-check form-switch d-flex align-items-center justify-content-between">
                                                    <div>
                                                        <label class="form-check-label fw-bold small" for="requirePosPin">Require PIN for Cashiers</label>
                                                        <p class="text-muted small mb-0">Ask for 4-digit PIN before launching POS.</p>
                                                    </div>
                                                    <input class="form-switch-input" type="checkbox" name="settings[security][require_pos_pin]" id="requirePosPin"
                                                        value="1" <?= ($all_settings['security']['require_pos_pin'] ?? '1') == '1' ? 'checked' : '' ?>>
                                                </div>
                                                <div class="mt-3"><label for="stockTransferApproval" class="form-label fw-bold small">Stock transfer approval</label><select id="stockTransferApproval" name="settings[security][stock_transfer_require_approval]" class="form-select"><option value="1" <?= ($all_settings['security']['stock_transfer_require_approval'] ?? '1')==='1' ? 'selected' : '' ?>>Require approval before dispatch</option><option value="0" <?= ($all_settings['security']['stock_transfer_require_approval'] ?? '1')==='0' ? 'selected' : '' ?>>Dispatch without approval</option></select><p class="text-muted small">Applies to new requests. Receiving confirmation is always required.</p></div>
                                                <p class="text-danger small mt-2 fw-bold mb-0">
                                                    <i class="fa-solid fa-circle-info me-1"></i> Cashiers should have their unique PINs set in "User Management".
                                                </p>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="premium-input-group">
                                                    <label class="small fw-semibold text-muted mb-1">Master Override PIN</label>
                                                    <input type="password" maxlength="4" name="settings[security][master_pos_pin]" class="form-control premium-field text-center fw-bold"
                                                        style="letter-spacing: 5px; font-size: 18px;"
                                                        placeholder="New 4-digit PIN" value="">
                                                    <small class="text-muted">Override available to settings administrators only.</small>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <hr class="my-2">
                                            </div>

                                            <div class="col-md-12">
                                                <div class="p-3 rounded-4 border bg-white shadow-sm mb-3">
                                                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="material-symbols-outlined text-primary" style="font-size: 20px;">badge</span>
                                                            <h6 class="mb-0 fw-bold small">Manage Cashier PINs Per Branch</h6>
                                                        </div>
                                                        <span class="badge bg-primary-subtle text-primary rounded-pill"><?= count($cashiers) ?> Staff</span>
                                                    </div>
                                                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 13px;">
                                                            <thead class="sticky-top bg-white" style="z-index: 1;">
                                                                <tr>
                                                                    <th class="text-muted border-0">Branch</th>
                                                                    <th class="text-muted border-0">Name</th>
                                                                    <th class="text-muted border-0 text-center" style="width: 120px;">POS PIN</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($cashiers as $cashier): ?>
                                                                    <tr>
                                                                        <td class="border-0"><span class="badge bg-light text-dark fw-normal"><?= htmlspecialchars($cashier['branch_name'] ?? 'Unassigned') ?></span></td>
                                                                        <td class="border-0 font-body-md fw-600 text-dark"><?= htmlspecialchars($cashier['name']) ?></td>
                                                                         <td class="border-0">
                                                                            <input type="password" maxlength="4" name="user_pins[<?= $cashier['id'] ?>]"
                                                                                class="form-control form-control-sm text-center fw-bold premium-field"
                                                                                style="letter-spacing: 3px;"
                                                                                placeholder="New PIN" value=""
                                                                                onfocus="this.type='text'" onblur="this.type='password'">
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <p class="text-muted extra-small mt-2 mb-0">
                                                        <i class="fa-solid fa-circle-info me-1"></i> Changes to PINs will be saved when you click "Update Security" below.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="text-end mt-5 pt-3 border-top">
                                            <button type="submit" class="btn btn-premium-main px-5 py-2 fw-bold">
                                                <i class="fa-solid fa-shield-halved me-2"></i> UPDATE SECURITY
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- APPEARANCE SETTINGS -->
                <div class="tab-pane fade <?= $active_tab === 'appearance' ? 'show active' : '' ?>" id="tab-appearance">
                    <form action="<?= url('?route=admin/settings') ?>" method="POST">
                        <input type="hidden" name="setting_category" value="appearance">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <div class="settings-section-card p-4">
                            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                                <div class="section-icon bg-info-subtle text-info"><span class="material-symbols-outlined">auto_fix_high</span></div>
                                <h5 class="mb-0 fw-bold">UI Theme & Branding</h5>
                            </div>

                            <div class="row g-4 text-start">
                                <div class="col-md-6">
                                    <div class="premium-input-group">
                                        <label class="small fw-semibold text-muted mb-2">System Primary Color</label>
                                        <div class="d-flex gap-3">
                                            <input type="color" name="settings[appearance][primary_color]" class="form-control form-control-color border-0 p-0 rounded-circle overflow-hidden shadow-sm"
                                                style="width: 48px; height: 48px;" value="<?= $all_settings['appearance']['primary_color'] ?? '#0891b2' ?>">
                                            <div class="flex-grow-1">
                                                <input type="text" class="form-control premium-field" value="<?= $all_settings['appearance']['primary_color'] ?? '#0891b2' ?>" readonly>
                                                <small class="text-muted">Used for buttons, links, and accents.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="premium-input-group">
                                        <label class="small fw-semibold text-muted mb-2">Accent Secondary Color</label>
                                        <div class="d-flex gap-3">
                                            <input type="color" name="settings[appearance][secondary_color]" class="form-control form-control-color border-0 p-0 rounded-circle overflow-hidden shadow-sm"
                                                style="width: 48px; height: 48px;" value="<?= $all_settings['appearance']['secondary_color'] ?? '#7209b7' ?>">
                                            <div class="flex-grow-1">
                                                <input type="text" class="form-control premium-field" value="<?= $all_settings['appearance']['secondary_color'] ?? '#7209b7' ?>" readonly>
                                                <small class="text-muted">Gradient start colors and highlights.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-5 pt-3 border-top">
                                <button type="submit" class="btn btn-premium-main px-5 py-2 fw-bold">
                                    <i class="fa-solid fa-save me-2"></i> SAVE CHANGES
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- PASSWORD SETTINGS -->
                <div class="tab-pane fade <?= $active_tab === 'password' ? 'show active' : '' ?>" id="tab-password">
                    <form action="<?= url('?route=admin/settings') ?>" method="POST">
                        <input type="hidden" name="setting_category" value="password">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <div class="settings-section-card p-4">
                            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                                <div class="section-icon bg-primary-subtle text-primary"><span class="material-symbols-outlined">lock_reset</span></div>
                                <h5 class="mb-0 fw-bold">Update Account Password</h5>
                            </div>

                            <div class="row g-4 text-start justify-content-center">
                                <div class="col-md-8">
                                    <div class="premium-input-group mb-4">
                                        <label class="small fw-semibold text-muted mb-2">Current Authentication Password</label>
                                        <div class="input-group">
                                            <input type="password" name="current_password" class="form-control premium-field" required>
                                            <button class="btn btn-link text-muted border-0 px-3" type="button" onclick="togglePassword(this)" style="text-decoration: none;">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="premium-input-group mb-4">
                                        <label class="small fw-semibold text-muted mb-2">New Secure Password</label>
                                        <div class="input-group">
                                            <input type="password" name="new_password" class="form-control premium-field" required>
                                            <button class="btn btn-link text-muted border-0 px-3" type="button" onclick="togglePassword(this)" style="text-decoration: none;">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="premium-input-group mb-4">
                                        <label class="small fw-semibold text-muted mb-2">Confirm New Password</label>
                                        <div class="input-group">
                                            <input type="password" name="confirm_password" class="form-control premium-field" required>
                                            <button class="btn btn-link text-muted border-0 px-3" type="button" onclick="togglePassword(this)" style="text-decoration: none;">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="alert alert-info border-0 rounded-4 p-3 small mb-0">
                                        <div class="d-flex gap-2">
                                            <i class="fa-solid fa-circle-info mt-1"></i>
                                            <div>
                                                For your security, we recommend a password that is at least 8 characters long and includes a mix of uppercase letters, numbers, and symbols.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-5 pt-3 border-top">
                                <button type="submit" class="btn btn-premium-main px-5 py-2 fw-bold">
                                    <i class="fa-solid fa-key me-2"></i> UPDATE PASSWORD
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
    .fw-800 {
        font-weight: 800;
    }

    /* Settings Nav */
    .settings-nav .nav-link {
        background: transparent;
        border: 2px solid transparent;
        border-radius: 16px;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        color: #444;
        position: relative;
        overflow: hidden;
    }

    .settings-nav .nav-link:hover {
        background: rgba(8, 145, 178, 0.05);
        border-color: rgba(8, 145, 178, 0.1);
    }

    .settings-nav .nav-link.active {
        background: #ffffff !important;
        border: 2px solid #0891b2 !important;
        color: #0891b2 !important;
        box-shadow: 0 10px 30px rgba(8, 145, 178, 0.08);
    }

    .nav-icon-wrap {
        width: 44px;
        height: 44px;
        background: #f7f7f8;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all 0.3s;
    }

    .settings-nav .nav-link.active .nav-icon-wrap {
        background: #0891b2;
        color: #fff;
    }

    .nav-label {
        font-weight: 700;
        font-family: 'Outfit', sans-serif;
        font-size: 15px;
    }

    .nav-desc {
        font-size: 11px;
        margin-top: -2px;
    }

    /* Page Header */
    .settings-page-header {
        margin-top: 0.25rem;
    }
    .settings-page-title {
        font-size: 1.45rem;
        font-weight: 800;
        color: #2a3547;
        letter-spacing: -0.4px;
        margin-bottom: 4px;
        font-family: 'Outfit', sans-serif;
    }
    .settings-page-subtitle {
        font-size: 0.85rem;
        color: #7c8fac;
        margin-bottom: 0;
    }
    .settings-last-updated {
        font-size: 0.78rem;
        color: #7c8fac;
        background: #f0f4ff;
        border-radius: 50px;
        padding: 6px 16px;
        font-weight: 500;
    }

    /* Solid Section Cards */
    .settings-sidebar-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid #e8edf5;
        box-shadow: 0 2px 12px rgba(93,135,255,0.06);
    }
    .settings-section-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid #e8edf5;
        box-shadow: 0 2px 12px rgba(93,135,255,0.06);
    }

    .section-icon {
        width: 42px;
        height: 42px;
        background: rgba(26, 115, 232, 0.1);
        color: #1a73e8;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Premium Input Fields */
    .premium-field {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        font-size: 14.5px;
        background: #fdfdfd;
        transition: all 0.2s;
        font-family: 'Inter', sans-serif;
    }

    .premium-field:focus {
        border-color: #0891b2;
        box-shadow: 0 0 0 4px rgba(8, 145, 178, 0.1);
        background: #fff;
        outline: none;
    }

    /* Logo Preview */
    .store-logo-preview {
        width: 140px;
        height: 140px;
        object-fit: contain;
        background: #fff;
        border: 4px solid #fff;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    }

    .logo-edit-btn {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 36px;
        height: 36px;
        background: #0891b2;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(8, 145, 178, 0.3);
        transition: all 0.2s;
    }

    .logo-edit-btn:hover {
        transform: scale(1.1);
        background: #7209b7;
    }

    /* Buttons */
    .btn-premium-main {
        background: #0891b2;
        color: #fff;
        border: none;
        border-radius: 12px;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(8, 145, 178, 0.3);
    }

    .btn-premium-main:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(8, 145, 178, 0.4);
        color: #fff;
    }

    /* Receipt Preview */
    .receipt-simualtor {
        font-family: 'Courier New', Courier, monospace;
        color: #333;
        line-height: 1.4;
    }

    /* Switches */
    .form-switch-input {
        width: 48px;
        height: 24px;
        appearance: none;
        background: #cbd5e1;
        border-radius: 12px;
        position: relative;
        cursor: pointer;
        transition: all 0.3s;
    }

    .form-switch-input:checked {
        background: #0891b2;
    }

    .form-switch-input::before {
        content: '';
        position: absolute;
        width: 18px;
        height: 18px;
        background: #fff;
        border-radius: 50%;
        top: 3px;
        left: 3px;
        transition: all 0.3s;
    }

    .form-switch-input:checked::before {
        left: 27px;
    }

    .btn-link:hover i {
        color: #7209b7 !important;
    }
</style>

<script>
    function previewLogo(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('logoPreview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function updateCurrencyFields(select) {
        const val = select.value.split('|');
        document.getElementById('hidden_curr_symbol').value = val[0];
        document.getElementById('hidden_curr_name').value = val[1];
    }

    function togglePassword(btn) {
        const input = btn.closest('.input-group').querySelector('input');
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
?>
