<?php
$is_admin = User::has_all_branch_access($_SESSION['user']['id']);
$title = "Inventory & Catalog Management";
ob_start();
?>
<link rel="stylesheet" href="<?= url('assets/css/inventory-controls.css') ?>?v=<?= filemtime(__DIR__.'/../../assets/css/inventory-controls.css') ?>">
<style>
    /* ================================================================
       AURORA POS — INVENTORY MANAGEMENT PAGE STYLES (NEW DESIGN)
       ================================================================ */

    /* Page Header */
    .inv-page-header {
        margin-top: 0.25rem;
        margin-bottom: 1.5rem;
    }

    .inv-badge-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #eef2ff;
        color: #5d87ff;
        border-radius: 50px;
        padding: 4px 12px;
        font-size: 0.75rem;
        font-weight: 700;
        margin-bottom: 8px;
        letter-spacing: 0.3px;
    }

    .inv-page-title {
        font-size: 1.6rem;
        font-weight: 800;
        color: #2a3547;
        letter-spacing: -0.5px;
        margin-bottom: 4px;
    }

    .inv-page-subtitle {
        font-size: 0.88rem;
        color: #7c8fac;
        margin-bottom: 0;
    }

    /* Aurora Stat Cards */
    .inv-kpi-card {
        border-radius: 18px;
        border: 1px solid #e8edf5;
        box-shadow: 0 2px 14px rgba(0, 0, 0, 0.04);
        background: #ffffff;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: 100%;
    }

    .inv-kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    }

    .inv-kpi-glow {
        height: 4px;
        width: 100%;
    }

    .inv-kpi-icon {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .inv-kpi-value {
        font-size: 1.75rem;
        font-weight: 800;
        letter-spacing: -0.5px;
        line-height: 1.1;
        color: #2a3547;
    }

    .inv-kpi-label {
        font-size: 0.72rem;
        font-weight: 700;
        color: #9eaac7;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    /* Add Product Button */
    .btn-inv-primary {
        background: linear-gradient(135deg, #5d87ff 0%, #4570ea 100%);
        color: #ffffff !important;
        border: none;
        border-radius: 50px;
        padding: 10px 24px;
        font-weight: 700;
        font-size: 0.88rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 14px rgba(93, 135, 255, 0.35);
        transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-inv-primary:hover {
        background: linear-gradient(135deg, #4d77ef 0%, #3560da 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(93, 135, 255, 0.45);
        color: #fff !important;
    }

    /* Export Button */
    .btn-inv-secondary {
        background: #ffffff;
        color: #2a3547 !important;
        border: 1px solid #dfe5ef;
        border-radius: 50px;
        padding: 10px 22px;
        font-weight: 700;
        font-size: 0.88rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        transition: all 0.22s ease;
    }

    .btn-inv-secondary:hover {
        border-color: #5d87ff;
        color: #5d87ff !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(93, 135, 255, 0.12);
    }

    /* Aurora Card (Table container) */
    .aurora-card {
        border-radius: 18px;
        border: 1px solid #e8edf5;
        box-shadow: 0 2px 14px rgba(0, 0, 0, 0.04);
        background: #ffffff;
        transition: box-shadow 0.2s ease;
        overflow: visible;
    }

    .aurora-card:hover {
        box-shadow: 0 6px 22px rgba(0, 0, 0, 0.06);
    }

    /* Toolbar */
    .inv-toolbar {
        background: #f8fafc;
        border-radius: 16px;
        padding: 12px 16px;
        border: 1px solid #eef2f6;
    }

    /* Filter buttons */
    .filter-btn-modern {
        height: 38px;
        font-size: 0.82rem;
        font-weight: 600;
        border-radius: 50px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #475569;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0 14px;
    }

    .filter-btn-modern:hover {
        border-color: #5d87ff;
        background: #eef2ff;
        color: #5d87ff;
        box-shadow: 0 4px 12px rgba(93,135,255,0.12);
    }

    /* Filter dropdowns */
    .filter-dropdown-menu {
        min-width: 185px !important;
        border-radius: 14px !important;
        padding: 6px !important;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12) !important;
        border: 1px solid rgba(0,0,0,0.06) !important;
        background: #ffffff !important;
        z-index: 999999 !important;
    }

    .filter-option {
        display: flex !important;
        align-items: center !important;
        padding: 10px 14px !important;
        font-size: 0.85rem !important;
        color: #475569 !important;
        border-radius: 10px !important;
        transition: all 0.15s ease !important;
        font-weight: 500 !important;
        border: none;
        width: 100%;
        background: transparent;
        text-align: left;
        user-select: none;
        -webkit-user-select: none;
        gap: 10px;
        min-height: 40px;
        line-height: 1.3;
    }
    .filter-dropdown-menu .filter-option .material-symbols-outlined {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 24px;
        width: 24px;
        height: 24px;
        margin-right: 0 !important;
        font-size: 18px !important;
        border-radius: 6px;
        background: #f1f5f9;
    }
    .filter-dropdown-menu .filter-option:focus-visible {outline:2px solid #5d87ff;outline-offset:-2px;}

    .filter-option:hover {
        background: #eef2ff !important;
        color: #5d87ff !important;
    }

    .filter-option.active {
        background: #eef2ff !important;
        color: #5d87ff !important;
        font-weight: 700 !important;
    }

    /* Clear Filters button */
    #clearFilters {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 50% !important;
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }

    #clearFilters:hover {
        transform: rotate(180deg);
        background: #5d87ff;
        border-color: #5d87ff;
    }

    #clearFilters:hover span {
        color: #fff !important;
    }

    /* Filter pill buttons (dropdown trigger) */
    .inv-filter-pill {
        border-radius: 50px;
        padding: 7px 16px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #475569;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        user-select: none;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        white-space: nowrap;
    }
    .inv-filter-pill:hover {
        color: #5d87ff;
        border-color: #c7d7ff;
        background: #f0f4ff;
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(93, 135, 255, 0.12);
    }
    .inv-filter-pill.active-filter {
        background: linear-gradient(135deg, #5d87ff 0%, #4570ea 100%);
        color: #ffffff;
        border-color: #5d87ff;
        box-shadow: 0 3px 10px rgba(93, 135, 255, 0.35);
    }
    /* Clean modern chevron for filter pills */
    .inv-filter-pill.dropdown-toggle::after {
        display: inline-block;
        width: 12px;
        height: 12px;
        margin-left: 6px;
        vertical-align: middle;
        content: "";
        border: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.2' d='m3 6 5 5 5-5'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-size: 11px 8px;
        background-position: center;
        opacity: 0.85;
        transition: transform 0.2s ease;
    }
    .inv-filter-pill.active-filter.dropdown-toggle::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.2' d='m3 6 5 5 5-5'/%3e%3c/svg%3e");
        opacity: 1;
    }
    .dropdown.show .inv-filter-pill.dropdown-toggle::after,
    .inv-filter-pill.dropdown-toggle[aria-expanded="true"]::after {
        transform: rotate(180deg);
    }

    /* DataTables */
    .dataTables_length select {
        padding: 0.4rem 1.5rem 0.4rem 0.75rem !important;
        border-radius: 50px !important;
        border: 1px solid #e2e8f0 !important;
        font-size: 0.8rem !important;
        background-color: #f8fafc !important;
        outline: none !important;
    }

    .dt-search-input {
        background-color: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 50px !important;
        padding: 8px 16px 8px 40px !important;
        font-size: 0.85rem !important;
        width: 280px !important;
        transition: all 0.3s ease !important;
        outline: none !important;
        color: #2a3547 !important;
    }

    .dt-search-input:focus {
        background-color: #fff !important;
        border-color: #5d87ff !important;
        box-shadow: 0 0 0 3px rgba(93, 135, 255, 0.12) !important;
        width: 320px !important;
    }

    .dt-search-container {
        position: relative;
    }

    .dt-search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
        font-size: 1.1rem;
    }

    /* Dropdown Items */
    .dropdown-menu {
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12) !important;
        border-radius: 16px !important;
        padding: 8px 6px !important;
        min-width: 210px !important;
        animation: dropdownFadeIn 0.2s ease-out;
        z-index: 10000 !important;
    }

    @keyframes dropdownFadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .dropdown-item {
        border-radius: 10px !important;
        padding: 10px 16px !important;
        font-size: 0.85rem !important;
        font-weight: 500 !important;
        color: #475569 !important;
        transition: all 0.2s ease !important;
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
    }

    .dropdown-item:hover {
        background-color: #eef2ff !important;
        color: #5d87ff !important;
        transform: translateX(4px);
    }

    .dropdown-item.text-danger:hover {
        background-color: #fff1f2 !important;
        color: #e11d48 !important;
    }

    /* ── Table: card-style rows ── */
    #productsTable {
        border-collapse: separate !important;
        border-spacing: 0 6px !important;
        width: 100% !important;
    }
    #productsTable thead tr {
        background: transparent !important;
    }
    #productsTable thead th {
        background: transparent !important;
        border: none !important;
        padding: 10px 14px !important;
        color: #8c98a9 !important;
        font-size: 0.68rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.8px !important;
        font-weight: 700 !important;
        white-space: nowrap;
        vertical-align: middle !important;
    }

    #productsTable tbody tr {
        background: #ffffff !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease !important;
    }
    #productsTable tbody tr:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 20px rgba(93, 135, 255, 0.08) !important;
    }
    #productsTable tbody td {
        border: none !important;
        border-top: 1px solid #f1f5f9 !important;
        border-bottom: 1px solid #f1f5f9 !important;
        padding: 14px 14px !important;
        vertical-align: middle !important;
        background: #ffffff !important;
        transition: background 0.18s ease, border-color 0.18s ease !important;
    }
    #productsTable tbody tr td:first-child {
        border-left: 3px solid transparent !important;
        border-top-left-radius: 12px !important;
        border-bottom-left-radius: 12px !important;
        border-top: 1px solid #f1f5f9 !important;
        border-bottom: 1px solid #f1f5f9 !important;
        padding-left: 18px !important;
    }
    #productsTable tbody tr td:last-child {
        border-right: 1px solid #f1f5f9 !important;
        border-top-right-radius: 12px !important;
        border-bottom-right-radius: 12px !important;
        padding-right: 18px !important;
    }
    #productsTable tbody tr:hover td {
        background: #fafbff !important;
        border-top-color: #dce7fa !important;
        border-bottom-color: #dce7fa !important;
    }
    #productsTable tbody tr:hover td:first-child {
        border-left: 3px solid #5d87ff !important;
    }
    #productsTable tbody tr:hover td:last-child {
        border-right-color: #dce7fa !important;
    }

    /* ── High z-index & overflow guarantees for action dropdown ── */
    #productsTable_wrapper,
    div.table-responsive,
    .aurora-card {
        overflow: visible !important;
    }

    #productsTable tbody tr.dropdown-open,
    #productsTable tbody tr:has(.show) {
        position: relative !important;
        z-index: 99999 !important;
    }

    #productsTable tbody td.dropdown-open,
    #productsTable tbody td:has(.show) {
        position: relative !important;
        z-index: 99999 !important;
    }

    #productsTable .dropdown.show {
        position: relative !important;
        z-index: 999999 !important;
    }

    #productsTable .dropdown-menu,
    .prod-action-menu {
        z-index: 9999999 !important;
    }

    /* ── Action button (Noticeable & High-End Cog) ── */
    .prod-action-btn {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        border: 1.5px solid #dbeafe !important;
        background: #eff4ff !important;
        color: #436fe8 !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 8px rgba(93, 135, 255, 0.16);
    }
    .prod-action-btn .action-cog-icon,
    .prod-action-btn i {
        font-size: 20px !important;
        line-height: 1;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        color: #436fe8;
    }
    .prod-action-btn:hover,
    .prod-action-btn.show,
    .prod-action-btn[aria-expanded="true"] {
        background: linear-gradient(135deg, #5d87ff 0%, #3b66de 100%) !important;
        border-color: #3b66de !important;
        color: #ffffff !important;
        box-shadow: 0 6px 20px rgba(93, 135, 255, 0.40) !important;
        transform: scale(1.1) translateY(-1px);
    }
    .prod-action-btn:hover .action-cog-icon,
    .prod-action-btn.show .action-cog-icon,
    .prod-action-btn:hover i,
    .prod-action-btn.show i {
        transform: rotate(60deg);
        color: #ffffff !important;
    }

    /* ── Action dropdown menu ── */
    .prod-action-menu {
        min-width: 220px !important;
        border: none !important;
        border-radius: 18px !important;
        box-shadow: 0 16px 48px rgba(0,0,0,0.14) !important;
        padding: 8px !important;
        background: #fff !important;
        animation: menuFadeIn 0.18s ease-out;
    }
    @keyframes menuFadeIn {
        from { opacity: 0; transform: translateY(8px) scale(0.97); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .prod-action-menu .menu-header {
        padding: 8px 12px 10px;
        border-bottom: 1px solid #f1f5f9;
        margin-bottom: 4px;
    }
    .prod-action-menu .menu-header .menu-title {
        font-size: 0.75rem;
        font-weight: 700;
        color: #2a3547;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 170px;
    }
    .prod-action-menu .menu-header .menu-sub {
        font-size: 0.65rem;
        color: #a0aec0;
        font-weight: 600;
    }
    .prod-menu-item {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        padding: 9px 10px !important;
        border-radius: 12px !important;
        font-size: 0.84rem !important;
        font-weight: 600 !important;
        color: #374151 !important;
        cursor: pointer;
        transition: all 0.15s ease !important;
        border: none !important;
        width: 100% !important;
        background: transparent !important;
        text-align: left !important;
    }
    .prod-menu-item:hover {
        background: #f5f7ff !important;
        color: #5d87ff !important;
        transform: translateX(3px);
    }
    .prod-menu-item.danger:hover {
        background: #fff5f5 !important;
        color: #e53e3e !important;
    }
    .prod-menu-item .menu-icon {
        width: 30px;
        height: 30px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1rem;
        transition: transform 0.2s ease;
    }
    .prod-menu-item:hover .menu-icon {
        transform: scale(1.12);
    }
    .prod-action-divider {
        height: 1px;
        background: #f1f5f9;
        margin: 4px 8px;
    }

    /* ── Category-Themed Badges ── */
    .prod-cat-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 0.78rem;
        font-weight: 700;
        white-space: nowrap;
        letter-spacing: 0.2px;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .prod-cat-badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
    }
    .prod-cat-badge .cat-icon {
        font-size: 15px;
        line-height: 1;
        display: inline-flex;
        align-items: center;
    }

    /* Category box (fallback) */
    .prod-cat-box {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 0.78rem;
        font-weight: 700;
        white-space: nowrap;
        border: 1px solid #e2e8f0;
    }

    /* Product image thumbnail */
    .prod-img-thumb {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        object-fit: cover;
        border: 1px solid #f0f4ff;
        background: #f8fafc;
        flex-shrink: 0;
    }

    /* Product type micro-badge */
    .prod-type-badge {
        display: inline-block;
        padding: 2px 7px;
        border-radius: 4px;
        font-size: 0.62rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        background: #f1f5f9;
        color: #64748b;
    }
    .prod-type-badge.ingredient { background: #f0fdf4; color: #16a34a; }
    .prod-type-badge.non-stock  { background: #eff6ff; color: #3b82f6; }


    /* Performance badges */
    .badge-performance {
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 800;
        font-size: 10px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .badge-best-seller {
        background: #fefce8;
        color: #854d0e;
        border: 1px solid #fef08a;
    }
    .badge-popular {
        background: #fff1f2;
        color: #9f1239;
        border: 1px solid #fecdd3;
    }
    .badge-performance-sm { padding: 2px 6px; font-size: 8px; }

    /* Pagination */
    .page-link {
        border: none;
        margin: 0 2px;
        border-radius: 8px !important;
        color: #64748b;
        font-weight: 600;
        padding: 7px 12px;
    }
    .page-item.active .page-link {
        background-color: #5d87ff !important;
        color: white;
        box-shadow: 0 4px 12px rgba(93, 135, 255, 0.25);
    }

    /* Font/utility */
    .fw-600 { font-weight: 600; }
    .fs-7 { font-size: 0.875rem; }
    .fs-8 { font-size: 0.75rem; }
    .fs-9 { font-size: 0.65rem; }
    .cursor-pointer { cursor: pointer !important; }

    /* Btn-icon */
    .btn-icon {
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .inv-page-title { font-size: 1.25rem; }
        .inv-kpi-value { font-size: 1.35rem; }
        .btn-inv-primary, .btn-inv-secondary {
            padding: 8px 14px;
            font-size: 0.8rem;
        }
        .filter-btn-modern {
            font-size: 0.75rem;
            height: 34px;
            min-width: unset !important;
        }
        .dt-search-input { width: 100% !important; }
        .inv-kpi-card { margin-bottom: 0; }
    }
</style>

<input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

<div class="px-0" id="inventory-directory">
    <!-- Page Header (Aurora Style) -->
    <div class="inv-page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
            <div class="inv-badge-pill">
                <iconify-icon icon="solar:box-bold-duotone" width="14"></iconify-icon>
                Inventory & Catalog
            </div>
            <h1 class="inv-page-title">Inventory Management</h1>
            <p class="inv-page-subtitle">Monitor stock levels, manage product catalog, and orchestrate inventory across branches.</p>
        </div>
        <div class="inv-header-actions d-flex gap-2 flex-wrap">
            <?php if(User::has_permission($_SESSION['user']['id'],'transfer_products') || User::has_permission($_SESSION['user']['id'],'view_stock_transfers') || User::has_permission($_SESSION['user']['id'],'approve_stock_transfers') || User::has_permission($_SESSION['user']['id'],'receive_stock_transfers')): ?><a class="btn btn-outline-primary" href="<?= url('?route=inventory/transfers') ?>"><span class="material-symbols-outlined inv-button-icon" aria-hidden="true">swap_horiz</span>Stock Transfers</a><?php endif; ?>
            <?php if(User::has_permission($_SESSION['user']['id'],'print_products')): ?><button type="button" class="btn-inv-secondary export-csv-btn">
                <iconify-icon icon="solar:export-bold-duotone" width="18"></iconify-icon>
                Export CSV
            </button><?php endif; ?>
            <?php if (User::has_permission($_SESSION['user']['id'], 'add_product')): ?>
                <button type="button" class="btn btn-outline-secondary me-2" id="create_ingredient_shortcut"><span class="material-symbols-outlined inv-button-icon" aria-hidden="true">add</span>Add ingredient</button>
                <button type="button" class="btn-inv-primary" data-bs-toggle="modal" data-bs-target="#createProductModal">
                    <iconify-icon icon="solar:add-square-bold-duotone" width="18"></iconify-icon>
                    New Product
                </button>
            <?php endif; ?>
        </div>
    </div>

<?php if (User::has_permission($_SESSION['user']['id'],'manage_inventory')): ?>
<div class="ic-toolbar mb-4"><div class="d-flex align-items-center gap-3"><span class="ic-toolbar-icon material-symbols-outlined" aria-hidden="true">inventory_2</span><div><div class="fw-bold">Stock operations</div><div class="small text-muted">Count, reconcile and plan your next restock.</div></div></div><div class="d-flex gap-2 flex-wrap"><button type="button" class="btn btn-light ic-launch" data-bs-toggle="modal" data-bs-target="#returnStockModal"><span class="material-symbols-outlined" aria-hidden="true">assignment_return</span>Return sale stock</button><button type="button" class="btn btn-primary ic-launch" data-bs-toggle="modal" data-bs-target="#inventoryControlModal"><span class="material-symbols-outlined" aria-hidden="true">fact_check</span>Inventory controls</button></div></div>
<?php endif; ?>
    <!-- KPI Stat Cards (Aurora Style) -->
    <div class="row g-3 mb-4" id="inventory-stats-container">
        <div class="col-sm-6 col-lg-3">
            <div class="inv-kpi-card">
                <div class="inv-kpi-glow" style="background: linear-gradient(90deg, #5d87ff, #a5b4fc);"></div>
                <div class="p-3 d-flex align-items-center gap-3">
                    <div class="inv-kpi-icon" style="background: #eef2ff;">
                        <iconify-icon icon="solar:box-bold-duotone" style="color: #5d87ff;" width="22"></iconify-icon>
                    </div>
                    <div>
                        <div class="inv-kpi-label">Total Catalog</div>
                        <div id="total-catalog-count" class="inv-kpi-value">—</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="inv-kpi-card">
                <div class="inv-kpi-glow" style="background: linear-gradient(90deg, #f59e0b, #fde68a);"></div>
                <div class="p-3 d-flex align-items-center gap-3">
                    <div class="inv-kpi-icon" style="background: #fefce8;">
                        <iconify-icon icon="solar:danger-bold-duotone" style="color: #d97706;" width="22"></iconify-icon>
                    </div>
                    <div>
                        <div class="inv-kpi-label">Low Stock Alerts</div>
                        <div id="low-stock-count" class="inv-kpi-value">—</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="inv-kpi-card">
                <div class="inv-kpi-glow" style="background: linear-gradient(90deg, #13dfa1, #a7f3d0);"></div>
                <div class="p-3 d-flex align-items-center gap-3">
                    <div class="inv-kpi-icon" style="background: #ecfdf5;">
                        <iconify-icon icon="solar:wallet-bold-duotone" style="color: #059669;" width="22"></iconify-icon>
                    </div>
                    <div>
                        <div class="inv-kpi-label">Cost Valuation</div>
                        <div id="inventory-valuation" class="inv-kpi-value" style="font-size: 1.3rem;">₱ 0.00</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="inv-kpi-card">
                <div class="inv-kpi-glow" style="background: linear-gradient(90deg, #0ea5e9, #bae6fd);"></div>
                <div class="p-3 d-flex align-items-center gap-3">
                    <div class="inv-kpi-icon" style="background: #e0f2fe;">
                        <iconify-icon icon="solar:card-bold-duotone" style="color: #0284c7;" width="22"></iconify-icon>
                    </div>
                    <div>
                        <div class="inv-kpi-label">Selling Valuation</div>
                        <div id="potential-revenue" class="inv-kpi-value" style="font-size: 1.3rem;">₱ 0.00</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card (Aurora Style) -->
    <div class="aurora-card inventory-directory-card" style="overflow: visible;">
        <!-- Single Integrated Toolbar matching reference design -->
        <div class="px-4 py-3 d-flex align-items-center justify-content-between flex-wrap gap-3 border-bottom">
            <!-- Left: Title & Filter Pills -->
            <div class="d-flex align-items-center flex-wrap gap-2">
                <!-- Title -->
                <div class="d-flex align-items-center gap-2 me-2">
                    <div style="width: 4px; height: 20px; background: linear-gradient(180deg, #5d87ff, #4570ea); border-radius: 2px;"></div>
                    <h5 class="mb-0" style="font-weight: 800; font-size: 1.05rem; color: #2a3547; letter-spacing: -0.2px;">Product Directory</h5>
                </div>

                <!-- Hidden filter values -->
                <input type="hidden" id="categoryFilter" value="all">
                <input type="hidden" id="branchFilter" value="0">
                <input type="hidden" id="stockFilter" value="all">

                <!-- Category Filter -->
                <div class="dropdown">
                    <button class="inv-filter-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-category" style="font-size:15px;"></i>
                        <span id="categoryLabel">All Categories</span>
                    </button>
                    <ul class="dropdown-menu filter-dropdown-menu py-2" id="categoryDropdownMenu">
                        <li><button class="filter-option dropdown-item rounded-2 active" data-value="all"
                                data-target="categoryFilter" data-label="All Categories">
                                <span class="material-symbols-outlined me-2" style="font-size:15px;">all_inclusive</span>All Categories
                            </button></li>
                    </ul>
                </div>

                <!-- Branch Filter -->
                <div class="dropdown">
                    <button class="inv-filter-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-building-store" style="font-size:15px;"></i>
                        <span id="branchLabel">All Branches</span>
                    </button>
                    <ul class="dropdown-menu filter-dropdown-menu py-2" id="branchDropdownMenu">
                        <li><button class="filter-option dropdown-item rounded-2 active" data-value="0"
                                data-target="branchFilter" data-label="All Branches">
                                <span class="material-symbols-outlined me-2" style="font-size:15px;">all_inclusive</span>All Branches
                            </button></li>
                    </ul>
                </div>

                <!-- Stock Filter -->
                <div class="dropdown">
                    <button class="inv-filter-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-stack" style="font-size:15px;"></i>
                        <span id="stockLabel">Stock Level</span>
                    </button>
                    <ul class="dropdown-menu filter-dropdown-menu py-2">
                        <li><button class="filter-option dropdown-item rounded-2 active" data-value="all"
                                data-target="stockFilter" data-label="All Levels">
                                <span class="material-symbols-outlined me-2" style="font-size:15px;">layers</span>All Levels
                            </button></li>
                        <li><button class="filter-option dropdown-item rounded-2" data-value="low"
                                data-target="stockFilter" data-label="Low Stock">
                                <span class="material-symbols-outlined me-2 text-warning" style="font-size:15px;">warning</span>Low Stock
                            </button></li>
                        <li><button class="filter-option dropdown-item rounded-2" data-value="out"
                                data-target="stockFilter" data-label="Out of Stock">
                                <span class="material-symbols-outlined me-2 text-danger" style="font-size:15px;">remove_shopping_cart</span>Out of Stock
                            </button></li>
                        <li><button class="filter-option dropdown-item rounded-2" data-value="overstock"
                                data-target="stockFilter" data-label="Overstock">
                                <span class="material-symbols-outlined me-2 text-success" style="font-size:15px;">inventory</span>Overstock
                            </button></li>
                    </ul>
                </div>

                <!-- Reset Filters -->
                <select id="sortFilter" class="inv-filter-pill" aria-label="Sort products">
                    <option value="newest">Newest first</option>
                    <option value="oldest">Oldest first</option>
                    <option value="name_asc">Name: A–Z</option>
                    <option value="name_desc">Name: Z–A</option>
                    <option value="price_asc">Price: Low to high</option>
                    <option value="price_desc">Price: High to low</option>
                    <option value="cost_asc">Cost: Low to high</option>
                    <option value="cost_desc">Cost: High to low</option>
                </select>
                <button type="button" id="clearFilters" title="Reset Filters">
                    <span class="material-symbols-outlined" style="font-size: 17px; color: #5d87ff;">restart_alt</span>
                </button>
            </div>

            <!-- Right: Search catalog... -->
            <div id="table-search-container" class="dt-custom-search"></div>
        </div>

        <div class="px-3 pt-2 pb-3">
            <div class="table-responsive">
                <table id="productsTable" class="table table-hover align-middle mb-0 w-100">
                    <thead>
                        <tr class="text-uppercase" style="letter-spacing: 0.8px;">
                            <th class="py-3 px-3 border-0" style="width: 25%;">Item Info</th>
                            <th class="py-3 px-3 border-0" style="width: 17%;">Category</th>
                            <th class="py-3 px-3 border-0" style="width: 12%;">Price</th>
                            <th class="py-3 px-3 border-0" style="width: 12%;">Cost</th>
                            <th class="py-3 px-3 border-0" style="width: 18%;">Stock Level</th>
                            <th class="py-3 px-2 border-0 text-center" style="width: 10%;">Performance</th>
                            <th class="py-3 px-3 border-0 text-end" style="width: 6%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- View Product Modal -->
    <?php include __DIR__ . '/../modals/viewProductModal.php'; ?>

<?php require __DIR__.'/../partials/inventory_controls.php'; ?>
<?php if (User::has_permission($_SESSION['user']['id'], 'manage_inventory')): ?>
<div class="modal fade inventory-workspace" id="returnStockModal" tabindex="-1" aria-labelledby="returnStockTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><div><span class="ic-eyebrow">SALE RECOVERY</span><h2 class="modal-title fs-5" id="returnStockTitle">Return stock from a recorded sale</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">

    <div id="return_stock_message" class="return-message mb-4" role="alert" tabindex="-1" hidden><span class="material-symbols-outlined" aria-hidden="true">info</span><div><strong>Stock return could not be completed</strong><p id="return_stock_message_text" class="mb-0 mt-1"></p></div></div>
    <div class="return-steps mb-4" aria-label="Stock return progress"><span id="return_step_select" class="is-current" aria-current="step"><b>1</b>Select sale</span><span class="return-step-line" aria-hidden="true"></span><span id="return_step_confirm"><b>2</b>Review & confirm</span></div>
    <div id="return_stock_entry">
    <p class="return-intro">Restore usable stock from a recorded sale.</p><p class="small text-muted mb-4">Ordinary refunds do not restore inventory. Review the sale and choose how its stock should be returned.</p>
    <div class="return-fields">
        <label class="small">Sale number<input type="text" inputmode="numeric" pattern="[1-9][0-9]*" id="return_stock_sale" class="form-control" list="return_stock_sale_suggestions" placeholder="Type or select a sale number" autocomplete="off" required aria-describedby="return_stock_sale_hint"></label>
        <datalist id="return_stock_sale_suggestions">
            <?php foreach (Sale::stock_return_suggestions() as $returnSale): ?>
            <option value="<?= (int)$returnSale['id'] ?>" label="<?= htmlspecialchars('#'.$returnSale['id'].' · '.$returnSale['created_at'].' · '.$returnSale['branch_name'].' · ₱'.number_format((float)$returnSale['final_amount'],2),ENT_QUOTES,'UTF-8') ?>"></option>
            <?php endforeach; ?>
        </datalist>
        <label class="small">Stock return policy<select id="return_stock_policy" class="form-select">
            <option value="void_before_preparation">Voided before preparation — ingredients were not used</option>
            <option value="return_finished">Usable finished goods returned — extras remain consumed</option>
        </select></label>
    </div>
    <p id="return_stock_sale_hint" class="small text-muted mt-2">Suggestions show the latest 100 accessible sales with recorded stock consumption and no previous stock return. You can also enter an older sale number.</p>
    <div class="return-safety mt-3"><span class="material-symbols-outlined" aria-hidden="true">info</span><p class="mb-0">Prepared or spoiled food must remain consumed. Record discarded stock under <strong>Waste / Spoilage</strong> in stock adjustments.</p></div>
    <div class="return-actions mt-4"><button type="button" id="return_sale_stock" class="btn return-review"><span class="material-symbols-outlined inv-button-icon" aria-hidden="true">assignment_return</span>Review stock return</button></div>
    </div>
    <div id="return_stock_confirmation" hidden>
        <div class="alert alert-warning d-flex gap-3 align-items-start" role="note"><span class="material-symbols-outlined" aria-hidden="true">verified_user</span><div>Confirm the selected stock is usable and this return policy is correct. <strong>Prepared food must not be returned as raw ingredients.</strong></div></div>
        <div class="return-summary mb-4"><div class="small text-muted">Sale number</div><div id="return_stock_confirm_sale" class="fw-bold mb-3"></div><div class="small text-muted">Stock return policy</div><div id="return_stock_confirm_policy" class="fw-bold"></div></div>
        <div class="return-actions"><button type="button" id="cancel_return_stock" class="btn return-back"><span class="material-symbols-outlined inv-button-icon" aria-hidden="true">arrow_back</span>Back to details</button><button type="button" id="confirm_return_stock" class="btn return-confirm"><span class="material-symbols-outlined inv-button-icon" aria-hidden="true">assignment_return</span>Confirm and return stock</button></div>
    </div>
</div><div class="modal-footer"><span class="small text-muted me-auto d-flex align-items-center gap-2"><span class="material-symbols-outlined" style="font-size:16px;" aria-hidden="true">lock</span>Each sale can be returned once.</span><button type="button" class="btn return-close" data-bs-dismiss="modal"><span class="material-symbols-outlined inv-button-icon" aria-hidden="true">close</span>Close</button></div></div></div></div>
<?php endif; ?>


    <!-- Manual Stock Adjustment Modal -->
    <?php include __DIR__ . '/../modals/manualAdjustmentModal.php'; ?>

    <!-- Stock History Modal -->
    <?php include __DIR__ . '/../modals/stockHistoryModal.php'; ?>

    <!-- Create Product Modal -->
    <?php include __DIR__ . '/../modals/createProductModal.php'; ?>

    <!-- Edit Product Modal -->
    <?php include __DIR__ . '/../modals/editProductModal.php'; ?>

    <!-- Delete Product Modal -->
    <?php include __DIR__ . '/../modals/deleteProductModal.php'; ?>

    <!-- Edit Batch Modal -->
    <?php include __DIR__ . '/../modals/editBatchModal.php'; ?>
    <?php if (User::has_permission($_SESSION['user']['id'],'reassign_products')) {
        $transferBranches = Branch::get_all_active();
        include __DIR__ . '/../modals/transferProductModal.php';
    } ?>



    <div class="modal fade" id="recipe-capacity-modal" tabindex="-1" aria-labelledby="recipe-capacity-title" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><div><h5 class="modal-title" id="recipe-capacity-title">Ingredients &amp; production capacity</h5><p class="small text-muted mb-0">Understand what is ready and what your ingredients can produce.</p></div><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body" id="recipe-capacity-content" aria-live="polite"></div></div></div></div>
    <script src="<?= url('assets/js/inventory-capacity.js') ?>?v=<?= filemtime(__DIR__.'/../../assets/js/inventory-capacity.js') ?>"></script>
    <script src="<?= url('assets/js/inventory-filter-state.js') ?>?v=<?= filemtime(__DIR__.'/../../assets/js/inventory-filter-state.js') ?>"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            console.log('🚀 Initializing Inventory Management System...');

            const inventoryStateKey='inventory-products:<?= (int)$_SESSION['user']['id'] ?>:'+location.pathname;
            const inventoryParams=new URLSearchParams(location.search);
            const inventoryDefaultBranch=<?= json_encode(User::has_all_branch_access($_SESSION['user']['id'])?'0':(string)($_SESSION['user']['branch_id']??0)) ?>;
            const inventoryAllowedBranches=<?= json_encode(array_merge(User::has_all_branch_access($_SESSION['user']['id'])?['0']:[],array_map('strval',array_column(Branch::for_current_user(),'id')))) ?>;
            let inventoryStorage;try{inventoryStorage=window.localStorage;}catch(e){inventoryStorage={getItem:()=>null,setItem:()=>{}};}
            const restoredInventory=InventoryFilterState.restore(inventoryStorage,inventoryStateKey,inventoryParams,{branch:inventoryDefaultBranch,category:'all',stock:'all',sort:'newest'},inventoryAllowedBranches);
            $('#branchFilter').val(restoredInventory.fields.branch);$('#categoryFilter').val(restoredInventory.fields.category);$('#stockFilter').val(restoredInventory.fields.stock);$('#sortFilter').val(restoredInventory.fields.sort);
            function syncInventoryFilterLabels(){for(const id of ['branchFilter','categoryFilter','stockFilter']){const value=String($('#'+id).val());const options=$('.filter-option[data-target="'+id+'"]');options.removeClass('active fw-600');const selected=options.filter(function(){return String($(this).data('value'))===value;});selected.addClass('active fw-600');$('#'+id.replace('Filter','Label')).text(selected.data('label')||({branchFilter:'Branch '+value,categoryFilter:'Category '+value,stockFilter:'All Levels'}[id]));options.first().closest('.dropdown').find('.inv-filter-pill').toggleClass('active-filter',value!=='all'&&value!=='0');}}
            syncInventoryFilterLabels();
            // Load Filter Options
            const loadFilters = () => {
                const csrf = $('[name="csrf_token"]').val();
                

                // Load Categories
                $.post('ajax/inventory_action.php', { action: 'get_categories', csrf_token: csrf }, function (res) {
                    console.log('📦 Categories Response:', res);
                    if (res.success && res.categories) {
                        const menu = $('#categoryDropdownMenu');
                        res.categories.forEach(cat => {
                            menu.append(`<li><button class="filter-option dropdown-item rounded-2" data-value="${cat.id}" data-target="categoryFilter" data-label="${cat.name}"><span class="material-symbols-outlined me-2" style="font-size:16px;">sell</span>${cat.name}</button></li>`);
                        });
                        syncInventoryFilterLabels();
                    }
                }, 'json').fail(err => console.error('❌ Category Load Error:', err));

                // Load Branches
                $.post('ajax/inventory_action.php', { action: 'get_branches', csrf_token: csrf }, function (res) {
                    console.log('🏢 Branches Response:', res);
                    if (res.success && res.branches) {
                        const menu = $('#branchDropdownMenu');
                        res.branches.forEach(branch => {
                            menu.append(`<li><button class="filter-option dropdown-item rounded-2" data-value="${branch.id}" data-target="branchFilter" data-label="${branch.name}"><span class="material-symbols-outlined me-2" style="font-size:16px;">place</span>${branch.name}</button></li>`);
                        });
                        syncInventoryFilterLabels();
                    }
                }, 'json').fail(err => console.error('❌ Branch Load Error:', err));
            };

            loadFilters();

            // View Product Handler
            // --- Modal-Based Batch Editing Handlers ---

            // 1. Open Edit Modal
            $(document).on('click', '.edit-batch-btn', function () {
                const btn = $(this);
                const tr = btn.closest('tr');
                const batchId = tr.data('id') || btn.data('id');
                const batchNum = tr.find('td:first').text().trim();
                const branch = tr.find('td:nth-child(2)').text().trim();
                const qty = parseFloat(tr.find('[data-field="quantity"]').text().replace(/,/g, '')) || parseFloat(btn.data('qty'));
                const cost = parseFloat(tr.find('[data-field="cost_price"]').text().replace(/[₱,]/g, '')) || parseFloat(btn.data('cost'));
                const selling = parseFloat(tr.find('[data-field="selling_price"]').text().replace(/[₱,]/g, '')) || parseFloat(btn.data('selling'));

                const modal = $('#editBatchModal');
                modal.find('#edit_batch_id').val(batchId);
                modal.find('#edit_batch_number_text').text(batchNum);
                modal.find('#edit_batch_branch_text').text(branch);
                modal.find('#edit_batch_quantity').val(qty);
                modal.find('#edit_batch_cost').val(cost);
                modal.find('#edit_batch_selling').val(selling || '');

                // Reset button state
                modal.find('button[type="submit"]').prop('disabled', false).text('Save Changes');

                modal.modal('show');
                $('#viewProductModal').modal('hide');
            });

            // 1.1 Return to View Modal when Edit Modal closes
            $('#editBatchModal').on('hidden.bs.modal', function () {
                const prodId = $('#view_prod_id').text().replace('#', '');
                if (prodId) {
                    // Re-trigger view product to refresh data and show modal
                    $('.view-product-btn[data-id="' + prodId + '"]').click();
                }
            });

            // 2. Handle Save via Form Submit
            $('#editBatchForm').on('submit', function (e) {
                e.preventDefault();
                const form = $(this);
                const btn = form.find('button[type="submit"]');
                const modal = $('#editBatchModal');

                const data = {
                    action: 'update_batch_data',
                    batch_id: $('#edit_batch_id').val(),
                    quantity: $('#edit_batch_quantity').val(),
                    cost_price: $('#edit_batch_cost').val(),
                    selling_price: $('#edit_batch_selling').val(),
                    csrf_token: $('[name="csrf_token"]').val()
                };

                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

                $.post('ajax/inventory_action.php', data, function (res) {
                    if (res.success) {
                        modal.modal('hide');

                        // Show Success Notification
                        Swal.fire({
                            icon: 'success',
                            title: 'Batch Updated',
                            text: 'The batch specifications have been applied successfully.',
                            timer: 2000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });

                        // Refresh parent table
                        if (typeof productsTable !== 'undefined') {
                            productsTable.ajax.reload(null, false);
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Update Failed',
                            text: res.message || 'Unknown error occurred.'
                        });
                        btn.prop('disabled', false).text('Save Changes');
                    }
                }, 'json').fail(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Could not connect to the server. Please try again.'
                    });
                    btn.prop('disabled', false).text('Save Changes');
                });
            });

            // Manual Stock Adjustment Handler
            $(document).on('click', '.adjust-stock-btn', function () {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const stock = parseFloat($(this).data('stock'));
                const maxThreshold = parseFloat($(this).data('max-threshold'));
                const branchId = $(this).data('branch-id');
                const modal = $('#stockAdjustmentModal');

                $('#stockAdjustmentForm')[0].reset();

                // Store validation data
                modal.data('current-stock', stock);
                modal.data('max-threshold', maxThreshold);

                // Update UI for specific product
                modal.find('#adj_prod_id').val(id);
                modal.find('#adj_prod_display_text').html(`<span class="badge bg-primary-subtle text-primary fw-bold">Target Product:</span> <span class="text-dark fw-bold">${name}</span>`);
                modal.find('#adj_product_search_container').hide();

                // Clear and Reset form
                $('#adjTypeSelect').val('stock_in').trigger('change');

                // Auto-populate branch if provided
                if (branchId) {
                    $('#adj_branch_id').val(branchId);
                    $('#adj_branch_id_hidden').val(branchId);
                }

                // Auto-populate cost and selling price from product data
                const costPrice = parseFloat($(this).data('cost-price')) || 0;
                const sellingPrice = parseFloat($(this).data('price')) || 0;
                if (costPrice > 0) modal.find('input[name="cost_price"]').val(costPrice);
                if (sellingPrice > 0) modal.find('input[name="selling_price"]').val(sellingPrice);

                // Fetch Next Batch Number
                $.ajax({
                    url: 'ajax/inventory_action.php',
                    method: 'POST',
                    data: {
                        action: 'get_next_batch',
                        product_id: id,
                        csrf_token: $('[name="csrf_token"]').val()
                    },
                    success: function (response) {
                        try {
                            const res = typeof response === 'string' ? JSON.parse(response) : response;
                            if (res.success) {
                                $('#autoBatchNumber').val(res.batch_number);
                            }
                        } catch (e) {
                            console.error('Error parsing batch response', e);
                        }
                        if (['stock_out','waste'].includes($('#adjTypeSelect').val())) {
                            loadActiveBatches();
                        }

                        modal.modal('show');
                    }
                });
            });

            // Product Selection from Search (if ever used)
            $(document).on('click', '.product-suggestion-item', function () {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const stock = parseFloat($(this).data('stock'));
                const maxThreshold = parseFloat($(this).data('max-threshold'));

                $('#productSearchInput').val(name);
                $('#adj_prod_id').val(id);
                $('#productSuggestions').addClass('d-none');

                // Store validation data
                const modal = $('#stockAdjustmentModal');
                modal.data('current-stock', stock);
                modal.data('max-threshold', maxThreshold);

                // Auto-populate cost and selling price from product data
                const costPrice = parseFloat($(this).data('cost-price')) || 0;
                const sellingPrice = parseFloat($(this).data('price')) || 0;
                if (costPrice > 0) modal.find('input[name="cost_price"]').val(costPrice);
                if (sellingPrice > 0) modal.find('input[name="selling_price"]').val(sellingPrice);

                // Fetch Next Batch Number
                $.ajax({
                    url: 'ajax/inventory_action.php',
                    method: 'POST',
                    data: {
                        action: 'get_next_batch',
                        product_id: id,
                        csrf_token: $('[name="csrf_token"]').val()
                    },
                    success: function (response) {
                        try {
                            const res = typeof response === 'string' ? JSON.parse(response) : response;
                            if (res.success) {
                                $('#autoBatchNumber').val(res.batch_number);
                            }
                        } catch (e) {
                            console.error('Error parsing batch response', e);
                        }
                        if (['stock_out','waste'].includes($('#adjTypeSelect').val())) {
                            loadActiveBatches();
                        }
                    }
                });
            });

            // Search logic
            $('#productSearchInput').on('focus input', function () {
                const query = $(this).val().toLowerCase();
                let matchCount = 0;
                $('.product-suggestion-item').each(function () {
                    const name = $(this).data('name').toLowerCase();
                    const id = $(this).data('id').toString();
                    const matches = name.includes(query) || id.includes(query);
                    $(this).toggle(matches);
                    if (matches) matchCount++;
                });
                if (matchCount > 0) $('#productSuggestions').removeClass('d-none');
                else $('#productSuggestions').addClass('d-none');
            });

            // Hide suggestions on outside click
            $(document).ready(function () {
                // Commit a pending custom attribute before saving.
                $('#createProductForm').on('submit', function (e) {
                    const pendingAttr = $('#new_attr_name_create').val().trim();
                    if (pendingAttr) {
                        addAttributeField(pendingAttr, '', 'create');
                        $('#new_attr_name_create').val('');
                    }
                });

                $(document).on('click', function (e) {
                    if (!$(e.target).closest('.position-relative').length) {
                        $('#productSuggestions').addClass('d-none');
                    }
                });
            });

            // Toggle Batch Info based on type
            $('#adjTypeSelect').on('change', function () {
                const type = $(this).val();
                if (['stock_out', 'waste'].includes(type)) {
                    $('#batchSelectionField').removeClass('d-none').slideDown();
                    $('#batchInfoFields').slideUp();
                    loadActiveBatches();
                } else {
                    $('#batchSelectionField').slideUp().addClass('d-none');
                    $('#batchInfoFields').removeClass('d-none').slideDown();
                }
            });

            function loadActiveBatches() {
                const product_id = $('#adj_prod_id').val();
                const branch_id = $('#adj_branch_id').val();
                const select = $('#adj_batch_id_select');

                if (!product_id) return;

                select.html('<option value="">-- Loading Batches... --</option>');

                $.post('ajax/inventory_action.php', {
                    action: 'get_active_batches',
                    csrf_token: $('[name="csrf_token"]').val(),
                    product_id: product_id,
                    branch_id: branch_id
                }, function (response) {
                    select.html('<option value="">-- Auto (FIFO) --</option>');
                    if (response.success && response.batches.length > 0) {
                        response.batches.forEach(b => {
                            select.append(`<option value="${b.id}">${b.batch_number} (Qty: ${b.quantity} | Cost: ₱${parseFloat(b.cost_price).toLocaleString()})</option>`);
                        });
                    } else if (response.success) {
                        select.append('<option disabled>No active batches found</option>');
                    }
                });
            }

            $('#adj_branch_id').on('change', function () {
                $('#adj_branch_id_hidden').val($(this).val());
                if (['stock_out','waste'].includes($('#adjTypeSelect').val())) {
                    loadActiveBatches();
                }
            });

            $('#stockAdjustmentForm').on('submit', function (e) {
                e.preventDefault();
                const modal = $('#stockAdjustmentModal');
                const adjType = $('#adjTypeSelect').val();
                const qty = parseFloat($(this).find('[name="quantity"]').val());
                const currentStock = modal.data('current-stock');
                const maxThreshold = modal.data('max-threshold');

                if (adjType === 'stock_in' && maxThreshold > 0 && (currentStock + qty) > maxThreshold) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Overstock Alert',
                        text: `This adjustment will bring the stock to ${currentStock + qty}, which exceeds the maximum threshold of ${maxThreshold}. Please adjust the product's max threshold first if necessary.`,
                        confirmButtonText: 'Understood',
                        customClass: { popup: 'rounded-4 border-0 shadow' }
                    });
                    return;
                }

                const formData = $(this).serialize();
                if ($(this).find('button[type="submit"]').prop('disabled')) return;
                const btn = $(this).find('button[type="submit"]');
                const originalText = btn.html();

                console.log('🚀 Submitting Stock Adjustment...', formData);
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> PROCESSING...');

                $.post('ajax/inventory_action.php', formData, function (response) {
                    console.log('✅ Server Response:', response);
                    btn.prop('disabled', false).html(originalText);

                    try {
                        const res = typeof response === 'string' ? JSON.parse(response) : response;
                        if (res.success) {
                            $('#stockAdjustmentModal').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: 'Adjusted!',
                                text: res.message,
                                timer: 2000,
                                showConfirmButton: false,
                                customClass: { popup: 'rounded-4 border-0 shadow' }
                            });
                            productsTable.ajax.reload(null, false);
                        } else {
                            $('#stockAdjustmentForm').trigger('adjustment:error', [res.message || 'Unable to adjust stock.', 'quantity']);
                        }
                    } catch (err) {
                        console.error('❌ Error parsing response:', err, response);
                        $('#stockAdjustmentForm').trigger('adjustment:error', ['Unable to read the server response. Please try again.']);
                    }
                }).fail(function (xhr, status, error) {
                    console.error('❌ AJAX Failure:', status, error, xhr.responseText);
                    btn.prop('disabled', false).html(originalText);
                    $('#stockAdjustmentForm').trigger('adjustment:error', ['Could not connect to the server. Please try again.']);
                });
            });

            // Stock History Handler
            let historyTable = null;
            let currentHistoryId = null;

            $(document).on('click', '.view-history-btn', function () {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const modal = $('#stockHistoryModal');

                currentHistoryId = id;
                modal.find('#historyProductName').text(name);
                modal.find('#historyProductSKU').text('SKU: #PROD-' + id.toString().padStart(4, '0'));

                if (historyTable) {
                    historyTable.ajax.reload();
                } else {
                    $('#historyTable').on('xhr.dt', function (e, settings, json, xhr) {
                        if (json && json.stats) {
                            $('#historyTotalIn').text((json.stats.total_in || 0).toLocaleString());
                            $('#historyTotalOut').text((json.stats.total_out || 0).toLocaleString());
                            const net = json.stats.net || 0;
                            $('#historyNet').text((net >= 0 ? '+' : '') + net.toLocaleString());
                            $('#historyCount').text((json.stats.count || 0).toLocaleString());
                        }
                    });

                    historyTable = $('#historyTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: 'ajax/inventory_action.php',
                            type: 'POST',
                            data: function (d) {
                                d.action = 'logs_datatable';
                                d.csrf_token = $('[name="csrf_token"]').val();
                                d.product_id = currentHistoryId;
                            }
                        },
                        columns: [
                            {
                                data: 'user_name',
                                render: function (data) {
                                    const name = data || 'System';
                                    const initial = name.trim().charAt(0).toUpperCase();
                                    return `<div class="d-flex align-items-center gap-2">
                                        <div style="width:24px;height:24px;border-radius:50%;background:#eef2ff;color:#5d87ff;font-weight:700;font-size:0.7rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">${initial}</div>
                                        <span class="fw-bold text-dark text-truncate" style="font-size:0.78rem;max-width:140px;">${name}</span>
                                    </div>`;
                                }
                            },
                            {
                                data: 'action',
                                render: function (data) {
                                    const is_in = ['stock_in', 'initial', 'Purchase'].includes(data);
                                    if (is_in) {
                                        return `<span class="badge rounded-pill" style="background:#dcfce7;color:#15803d;font-size:0.67rem;font-weight:700;padding:3px 8px;display:inline-flex;align-items:center;gap:2px;">
                                            <span class="material-symbols-outlined" style="font-size:11px;">arrow_downward</span> ${data.toUpperCase()}
                                        </span>`;
                                    } else {
                                        return `<span class="badge rounded-pill" style="background:#fee2e2;color:#b91c1c;font-size:0.67rem;font-weight:700;padding:3px 8px;display:inline-flex;align-items:center;gap:2px;">
                                            <span class="material-symbols-outlined" style="font-size:11px;">arrow_upward</span> ${data.toUpperCase()}
                                        </span>`;
                                    }
                                }
                            },
                            {
                                data: 'batch_number',
                                render: function (data) {
                                    return data ? `<span class="badge rounded-pill bg-light text-dark border fw-semibold" style="font-size:0.69rem;padding:3px 8px;display:inline-flex;align-items:center;gap:3px;">
                                        <span class="material-symbols-outlined" style="font-size:11px;color:#64748b;">layers</span> ${data}
                                    </span>` : '<span class="text-muted opacity-50">-</span>';
                                }
                            },
                            {
                                data: 'cost_price',
                                render: function (data) {
                                    return data ? `<span class="fw-bold text-dark" style="font-size:0.79rem;">₱${parseFloat(data).toLocaleString(undefined, { minimumFractionDigits: 2 })}</span>` : '<span class="text-muted opacity-50">-</span>';
                                }
                            },
                            {
                                data: 'quantity',
                                render: function (data, type, row) {
                                    const is_in = ['stock_in', 'initial', 'Purchase'].includes(row.action);
                                    return `<span class="fw-800 ${is_in ? 'text-success' : 'text-danger'}" style="font-size:0.84rem;">${is_in ? '+' : '-'}${data}</span>`;
                                }
                            },
                            {
                                data: 'remarks',
                                render: function (data) {
                                    if (!data) return '<span class="fst-italic opacity-50">None</span>';
                                    const safeText = String(data).replace(/"/g, '&quot;');
                                    return `<span class="text-muted history-remarks-truncate" style="font-size:0.75rem;" title="${safeText}" data-bs-toggle="tooltip" data-bs-placement="top">${data}</span>`;
                                }
                            },
                            {
                                data: 'created_at',
                                render: function (data) {
                                    const d = new Date(data);
                                    return `<div style="line-height:1.2;"><span class="text-dark fw-semibold" style="font-size:0.75rem;">${d.toLocaleDateString()}</span> <span class="text-muted" style="font-size:0.68rem;">${d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span></div>`;
                                }
                            }
                        ],
                        order: [[6, 'desc']],
                        pageLength: 5,
                        lengthMenu: [5, 10, 25],
                        drawCallback: function () {
                            $('#historyTable [data-bs-toggle="tooltip"]').each(function () {
                                new bootstrap.Tooltip(this);
                            });
                        },
                        language: {
                            search: "",
                            searchPlaceholder: "Search audit logs...",
                            paginate: {
                                previous: '<span class="material-symbols-outlined" style="font-size:15px;">chevron_left</span>',
                                next: '<span class="material-symbols-outlined" style="font-size:15px;">chevron_right</span>'
                            },
                            emptyTable: `<div class="text-center py-4"><span class="material-symbols-outlined text-muted mb-2" style="font-size:36px;color:#cbd5e1;">history_toggle_off</span><div class="fw-bold text-dark fs-7">No Audit Trails Found</div><div class="text-muted smaller">No inventory adjustments have been logged for this product.</div></div>`
                        },
                        dom: '<"d-flex justify-content-between align-items-center mb-2 px-1"lf>rt<"d-flex justify-content-between align-items-center mt-2 px-1"ip>'
                    });
                }

                modal.modal('show');
            });

            // Edit button within View modal
            $('#view_edit_btn').on('click', function () {
                const id = $(this).data('id');
                $('#viewProductModal').modal('hide');

                // Trigger the existing edit button for this product
                $(`.edit-product-btn[data-id="${id}"]`).first().trigger('click');
            });

            // Shared category theme resolver for table badges and view details modal
            function getCategoryTheme(categoryName) {
                if (!categoryName) {
                    return { bg: '#f8fafc', border: '#e2e8f0', color: '#64748b', iconColor: '#94a3b8', icon: 'category', name: 'Uncategorized' };
                }

                const cat = categoryName.trim().toLowerCase();
                let theme = null;

                if (cat.includes('halo') || cat.includes('special')) {
                    // Halo-Halo signature: soft pink-magenta
                    theme = { bg: '#fdf2f8', border: '#fbcfe8', color: '#c026d3', iconColor: '#d946ef', icon: 'icecream', name: categoryName };
                } else if (cat.includes('dessert') || cat.includes('sweet') || cat.includes('cake') || cat.includes('flan')) {
                    // Desserts: soft violet-fuchsia
                    theme = { bg: '#faf5ff', border: '#e9d5ff', color: '#9333ea', iconColor: '#a855f7', icon: 'cake', name: categoryName };
                } else if (cat.includes('coffee') || cat.includes('espresso') || cat.includes('latte') || cat.includes('cappuccino') || cat.includes('brew')) {
                    // Coffee: warm amber caramel
                    theme = { bg: '#fffbeb', border: '#fde68a', color: '#b45309', iconColor: '#d97706', icon: 'coffee', name: categoryName };
                } else if (cat.includes('drink') || cat.includes('tea') || cat.includes('bev') || cat.includes('juice') || cat.includes('soda') || cat.includes('shake')) {
                    // Drinks & Beverages: cool refreshing cyan/sky blue
                    theme = { bg: '#f0f9ff', border: '#bae6fd', color: '#0284c7', iconColor: '#0ea5e9', icon: 'local_cafe', name: categoryName };
                } else if (cat.includes('food') || cat.includes('meal') || cat.includes('dish') || cat.includes('rice') || cat.includes('lunch') || cat.includes('dinner')) {
                    // Food & Meals: fresh emerald green
                    theme = { bg: '#f0fdf4', border: '#bbf7d0', color: '#16a34a', iconColor: '#22c55e', icon: 'lunch_dining', name: categoryName };
                } else if (cat.includes('snack') || cat.includes('appetizer') || cat.includes('finger') || cat.includes('side') || cat.includes('fry') || cat.includes('fries')) {
                    // Snacks & Sides: warm golden amber
                    theme = { bg: '#fff7ed', border: '#fed7aa', color: '#ea580c', iconColor: '#f97316', icon: 'fastfood', name: categoryName };
                } else if (cat.includes('pastry') || cat.includes('bread') || cat.includes('bakery') || cat.includes('pie') || cat.includes('cookie')) {
                    // Bakery: terracotta orange
                    theme = { bg: '#fff7ed', border: '#ffedd5', color: '#c2410c', iconColor: '#ea580c', icon: 'bakery_dining', name: categoryName };
                } else if (cat.includes('add') || cat.includes('addon') || cat.includes('topping') || cat.includes('extra')) {
                    // Add-ons / Toppings: clean indigo-slate
                    theme = { bg: '#f1f5f9', border: '#e2e8f0', color: '#475569', iconColor: '#64748b', icon: 'sell', name: categoryName };
                } else if (cat.includes('ingredient') || cat.includes('raw')) {
                    // Ingredients: fresh mint teal
                    theme = { bg: '#f0fdfa', border: '#99f6e4', color: '#0d9488', iconColor: '#14b8a6', icon: 'kitchen', name: categoryName };
                } else if (cat.includes('combo') || cat.includes('bundle') || cat.includes('set') || cat.includes('package')) {
                    // Combo / Bundles: soft crimson rose
                    theme = { bg: '#fff1f2', border: '#fecdd3', color: '#e11d48', iconColor: '#f43f5e', icon: 'inventory_2', name: categoryName };
                } else {
                    // Deterministic hash palette for any custom user category
                    const palettes = [
                        { bg: '#eef2ff', border: '#c7d2fe', color: '#4f46e5', iconColor: '#6366f1', icon: 'bookmark', name: categoryName },
                        { bg: '#f5f3ff', border: '#ddd6fe', color: '#7c3aed', iconColor: '#8b5cf6', icon: 'category', name: categoryName },
                        { bg: '#ecfeff', border: '#a5f3fc', color: '#0891b2', iconColor: '#06b6d4', icon: 'local_offer', name: categoryName },
                        { bg: '#ecfdf5', border: '#a7f3d0', color: '#059669', iconColor: '#10b981', icon: 'eco', name: categoryName },
                        { bg: '#fff1f2', border: '#fecdd3', color: '#e11d48', iconColor: '#f43f5e', icon: 'stars', name: categoryName },
                        { bg: '#fffbeb', border: '#fde68a', color: '#d97706', iconColor: '#f59e0b', icon: 'label', name: categoryName }
                    ];
                    let hash = 0;
                    for (let i = 0; i < cat.length; i++) hash = (hash * 31 + cat.charCodeAt(i)) & 0xffffffff;
                    theme = palettes[Math.abs(hash) % palettes.length];
                }

                return theme;
            }

            // Initialize DataTables
            const productsTable = $('#productsTable').DataTable({
                ordering:false,
                stateSave:true,
                stateLoadCallback:function(){return restoredInventory.saved;},
                stateSaveCallback:function(settings,data){InventoryFilterState.save(inventoryStorage,inventoryStateKey,data,{branch:String($('#branchFilter').val()),category:String($('#categoryFilter').val()),stock:String($('#stockFilter').val()),sort:String($('#sortFilter').val())});const target=new URL(location.href);for(const [key,value] of Object.entries({branch_id:$('#branchFilter').val(),category_id:$('#categoryFilter').val(),stock_status:$('#stockFilter').val(),sort:$('#sortFilter').val(),search:data.search?.search||''})){if(value&&value!=='0'&&value!=='all')target.searchParams.set(key,value);else target.searchParams.delete(key);}history.replaceState(null,'',target);},
                search:{search:inventoryParams.get('search')||''},
                processing: true,
                serverSide: true,
                responsive: false,
                autoWidth: false,
                ajax: {
                    url: 'ajax/inventory_action.php',
                    type: 'POST',
                    data: function (d) {
                        d.action = 'products_datatable';
                        // Apply the dedicated sort selection independently of saved column sorts.
                        d.order = [InventoryFilterState.sortOrder($('#sortFilter').val())];
                        d.csrf_token = $('[name="csrf_token"]').val();
                        d.branch_id = $('#branchFilter').val();
                        d.category_id = $('#categoryFilter').val() !== 'all' ? $('#categoryFilter').val() : null;
                        d.stock_status = $('#stockFilter').val();
                    },
                    dataSrc: function (json) {
                        if (json.stats) {
                            $('#total-catalog-count').text(json.recordsTotal.toLocaleString());
                            $('#low-stock-count').text(json.stats.low_stock_count.toLocaleString());
                            $('#inventory-valuation').text('₱ ' + parseFloat(json.stats.total_valuation).toLocaleString(undefined, { minimumFractionDigits: 2 }));
                            $('#potential-revenue').text('₱ ' + parseFloat(json.stats.total_revenue).toLocaleString(undefined, { minimumFractionDigits: 2 }));

                            if (json.stats.low_stock_count > 0) {
                                $('#low-stock-count').addClass('text-danger').removeClass('text-dark');
                            } else {
                                $('#low-stock-count').removeClass('text-danger').addClass('text-dark');
                            }
                        }
                        return json.data;
                    }
                },
                createdRow:function(row){
                    const labels=['Product','Category','Selling price','Cost price','Stock level','Performance','Actions'];
                    Array.from(row.cells).forEach((cell,index)=>cell.setAttribute('data-label',labels[index]));
                },
                columns: [
                    {
                        data: 'name',
                        render: function (data, type, row) {
                            return `
                            <div class="d-flex align-items-center gap-3">
                                <img src="${row.image_path}" class="prod-img-thumb"
                                    onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(data)}&background=eef2ff&color=5d87ff&bold=true&size=44'">
                                <div>
                                    <div class="fw-700 text-dark mb-1" style="font-size:0.92rem;font-weight:700;line-height:1.25;color:#1e293b;">${data}</div>
                                    <div style="font-size:0.75rem;color:#8c98a9;font-weight:600;">SKU: #PROD-${row.id.toString().padStart(4, '0')}</div>
                                </div>
                            </div>`;
                        }
                    },
                    {
                        data: 'category_name',
                        render: function (data) {
                            const theme = getCategoryTheme(data);
                            return `<span class="prod-cat-badge" style="background:${theme.bg}; color:${theme.color}; border:1px solid ${theme.border};">
                                <span class="material-symbols-outlined cat-icon" style="color:${theme.iconColor};">${theme.icon}</span>
                                <span>${data || 'Uncategorized'}</span>
                            </span>`;
                        }
                    },
                    {
                        data: 'price',
                        render: function (data, type, row) {
                            if (row.product_type === 'variant' && (parseFloat(data) === 0 || row.variant_min_price > 0)) {
                                const minP = parseFloat(row.variant_min_price).toLocaleString(undefined, { minimumFractionDigits: 2 });
                                const maxP = parseFloat(row.variant_max_price).toLocaleString(undefined, { minimumFractionDigits: 2 });
                                return `<div class="fw-800 text-dark" style="font-size:1.02rem;font-weight:800;letter-spacing:-0.2px;">₱${minP} – ₱${maxP}</div>`;
                            }
                            return `<div class="fw-800 text-dark" style="font-size:1.05rem;font-weight:800;letter-spacing:-0.2px;">₱${parseFloat(data).toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>`;
                        }
                    },
                    {
                        data: 'cost_price',
                        render: function (data, type, row) {
                            if (row.product_type === 'variant' && parseFloat(data) === 0) {
                                return `<div class="text-muted fst-italic" style="font-size:0.85rem;">Per variant</div>`;
                            }
                            return `<div style="font-size:1.05rem;font-weight:600;color:#8c98a9;letter-spacing:-0.2px;">₱${parseFloat(data).toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>`;
                        }
                    },
                    {
                        data: 'stock',
                        render: function (data, type, row) {
                            if(row.stock_is_unlimited)return '<span class="prod-type-badge non-stock">No stock tracking</span><small class="d-block text-muted mt-1">Not limited by physical inventory</small>';
                            const totalStock = parseFloat(data);
                            const isLow = totalStock <= parseFloat(row.low_stock_threshold);
                            const isOut = totalStock <= 0;
                            const barColor = isOut ? '#ef4444' : (isLow ? '#f59e0b' : '#10b981');
                            const stockColor = isOut ? '#ef4444' : (isLow ? '#f59e0b' : '#1e293b');

                            let branchDetails = '';
                            if (row.branch_stocks && row.branch_stocks.length) {
                                row.branch_stocks.forEach(bs => {
                                    const perf = row.branch_performance_badges ? row.branch_performance_badges[bs.id] : null;
                                    if (bs.branch_stock > 0 || row.branch_stocks.length === 1) {
                                        const perfBadge = perf === 'Best Seller'
                                            ? `<span class="badge-performance badge-performance-sm badge-best-seller ms-1"><span class="material-symbols-outlined" style="font-size:8px;">workspace_premium</span> BEST</span>`
                                            : (perf === 'Popular' ? `<span class="badge-performance badge-performance-sm badge-popular ms-1"><span class="material-symbols-outlined" style="font-size:8px;">local_fire_department</span></span>` : '');
                                        branchDetails += `<div class="d-flex align-items-center justify-content-between mt-1" style="font-size:0.75rem;color:#8a97b0;">
                                            <span>${bs.name}:${perfBadge}</span>
                                            <span class="fw-700 text-dark ms-2">${Number(bs.branch_stock).toFixed(2)}</span>
                                        </div>`;
                                    }
                                });
                            }

                            const maxStock = parseFloat(row.max_stock_threshold) || 100;
                            const pct = Math.min(100, Math.round((totalStock / maxStock) * 100));

                            const typeBadge = row.product_type ? `<span class="prod-type-badge ${row.is_ingredient==1?'ingredient':''} me-1">${row.product_type}</span>` : '';
                            const nonStockBadge = row.track_stock == 0 ? `<span class="prod-type-badge non-stock">Non-Stock</span>` : '';
                            const ingredientBadge = row.is_ingredient == 1 && row.product_type !== 'ingredient' ? `<span class="prod-type-badge ingredient ms-1">Ingredient</span>` : '';

                            if(row.product_type==='recipe'||row.product_type==='combo'){
                                const mode=row.product_type==='combo'?'Package':row.recipe_mode==='made_to_order'?'Made to order':row.recipe_mode==='prepared_batch'?'Ready stock':'Needs review';
                                const modeHint=row.recipe_mode==='made_to_order'?'Ingredients are deducted on sale':row.recipe_mode==='prepared_batch'?'Ingredients are deducted when preparing stock':'Reconcile recipe stock mode before selling';
                                return `<div class="inv-stock-compact"><div class="inv-stock-top"><strong style="color:${stockColor}">${totalStock.toLocaleString(undefined,{maximumFractionDigits:4})}</strong><span>${row.stock_is_recipe_capacity?'can make':'ready'}</span><button type="button" class="recipe-capacity-btn inv-capacity-action" data-id="${row.id}" title="View ingredients and production capacity" aria-label="View ingredients and production capacity"><span class="material-symbols-outlined" aria-hidden="true">receipt_long</span>Capacity</button></div><div class="inv-stock-meta"><span class="inv-recipe-chip" title="${modeHint}">${mode}</span><span class="inv-stock-scope">${row.branch_stocks?.length===1?row.branch_stocks[0].name:row.branch_stocks?.length?row.branch_stocks.length+' branches':'Shared'}</span></div></div>`;
                            }
                            if(row.branch_stocks && row.branch_stocks.length>1 && branchDetails)branchDetails='<details class="inv-stock-branches"><summary>'+row.branch_stocks.length+' branches</summary>'+branchDetails+'</details>';
                            return `<div style="min-width:130px;max-width:160px;">
                                <div class="d-flex align-items-baseline gap-1 mb-1">
                                    <span style="font-size:1.15rem;font-weight:800;color:${stockColor};letter-spacing:-0.3px;">${totalStock.toLocaleString()}${row.stock_is_recipe_capacity ? '<small class="ms-1 fw-normal">can make</small>' : ''}</span>
                                    <span style="font-size:0.75rem;color:#94a3b8;font-weight:500;">${row.stock_is_recipe_capacity ? 'units from ingredients' : '/ '+maxStock.toLocaleString()}</span>
                                </div>
                                ${row.stock_is_recipe_capacity?'':`<div class="rounded-pill overflow-hidden mb-1" style="height:4px;background:#f1f5f9;"><div style="width:${pct}%;height:100%;background:${barColor};border-radius:50px;transition:width .4s;"></div></div>`}
                                <div class="mb-1">${typeBadge}${nonStockBadge}${ingredientBadge}</div>
                                ${branchDetails}
                                ${row.product_type==='recipe'?`<div class="mt-2 small text-muted">${row.recipe_mode==='made_to_order'?'Made to order · ingredients deducted on sale':row.recipe_mode==='prepared_batch'?'Ready stock · ingredients deducted on preparation':'Recipe requires reconciliation'}</div><button type="button" class="btn btn-sm btn-outline-primary mt-2 recipe-capacity-btn" data-id="${row.id}"><span class="material-symbols-outlined inv-button-icon" aria-hidden="true">receipt_long</span>Ingredients &amp; capacity</button>`:''}
                            </div>`;
                        }
                    },
                    {
                        data: 'global_performance_badge',
                        className: 'text-center',
                        render: function (data) {
                            if (data === 'Best Seller') return `<span class="badge-performance badge-best-seller"><span class="material-symbols-outlined" style="font-size: 13px;">workspace_premium</span> BEST</span>`;
                            if (data === 'Popular') return `<span class="badge-performance badge-popular"><span class="material-symbols-outlined" style="font-size: 13px;">local_fire_department</span> POPULAR</span>`;
                            return `<span class="text-muted opacity-25">-</span>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        className: 'text-end pe-3 align-middle',
                        render: function (data, type, row) {
                            const sku = row.sku ? `#PROD-${row.id.toString().padStart(4,'0')}` : `#PROD-${row.id.toString().padStart(4,'0')}`;
                            return `
                            <div class="dropdown">
                                <button class="prod-action-btn dropdown-toggle-custom"
                                    type="button" data-bs-toggle="dropdown" data-bs-offset="0,8"
                                    aria-expanded="false" title="Product Actions">
                                    <span class="material-symbols-outlined action-cog-icon">settings</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end prod-action-menu">
                                    <!-- Header -->
                                    <div class="menu-header d-flex align-items-center gap-2">
                                        <img src="${row.image_path}" style="width:32px;height:32px;border-radius:8px;object-fit:cover;border:1px solid #eef2ff;"
                                            onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(row.name)}&background=eef2ff&color=5d87ff&bold=true&size=32'">
                                        <div style="overflow:hidden;">
                                            <div class="menu-title">${row.name}</div>
                                            <div class="menu-sub">${sku}</div>
                                        </div>
                                    </div>

                                    <!-- View -->
                                    <li>
                                        <button class="prod-menu-item view-product-btn"
                                            data-unit-type="${row.unit_type || 'pc'}" data-type="${row.product_type || 'simple'}" data-id="${row.id}" data-category-name="${row.category_name || 'Uncategorized'}"
                                            data-name="${row.name}" data-description="${row.description || ''}"
                                            data-price="${row.price}" data-cost="${row.cost_price}"
                                            data-stock="${row.stock}" data-threshold="${row.low_stock_threshold}"
                                            data-max-threshold="${row.max_stock_threshold}"
                                            data-image="${row.image_path}"
                                            data-branch-name="${row.branch_name || 'Global (All Branches)'}"
                                            data-sku="${row.sku || ''}" data-barcode="${row.barcode || ''}">
                                            <span class="menu-icon" style="background:#eef2ff;color:#5d87ff;">
                                                <span class="material-symbols-outlined" style="font-size:16px;">visibility</span>
                                            </span>
                                            View Details
                                        </button>
                                    </li>

                                    ${row.can_edit ? `
                                    <!-- Edit -->
                                    <li>
                                        <button class="prod-menu-item edit-product-btn"
                                            data-id="${row.id}" data-category="${row.category_id}"
                                            data-name="${row.name}" data-description="${row.description || ''}"
                                            data-price="${row.price}" data-cost="${row.cost_price}"
                                            data-stock="${row.stock}" data-threshold="${row.low_stock_threshold}"
                                            data-max-threshold="${row.max_stock_threshold}"
                                            data-branch="${row.branch_id || ''}"
                                            data-type="${row.product_type || 'simple'}"
                                            data-track-stock="${row.track_stock}"
                                            data-sellable="${row.is_sellable}" data-website="${row.show_on_website}"
                                            data-ingredient="${row.is_ingredient}"
                                            data-inventory-type="${row.inventory_type || 'stocked'}"
                                            data-unit-type="${row.unit_type || 'pc'}"
                                            data-sku="${row.sku || ''}" data-barcode="${row.barcode || ''}"
                                            data-status="${row.status || 'active'}"
                                            data-image="${row.image_path || 'assets/img/default.png'}"
                                            data-discountable="${row.is_discountable}">
                                            <span class="menu-icon" style="background:#f0fdf4;color:#16a34a;">
                                                <span class="material-symbols-outlined" style="font-size:16px;">edit</span>
                                            </span>
                                            Edit Product
                                        </button>
                                    </li>` : ''}

                                    ${row.can_manage_stock ? `
                                    <!-- Stock Adjustment -->
                                    <li>
                                        <button class="prod-menu-item adjust-stock-btn"
                                            data-id="${row.id}" data-name="${row.name}"
                                            data-stock="${row.stock}" data-max-threshold="${row.max_stock_threshold}"
                                            data-branch-id="${row.branch_id || ''}"
                                            data-cost-price="${row.cost_price || 0}" data-price="${row.price || 0}">
                                            <span class="menu-icon" style="background:#fffbeb;color:#d97706;">
                                                <span class="material-symbols-outlined" style="font-size:16px;">inventory</span>
                                            </span>
                                            Manual Adjustment
                                        </button>
                                    </li>` : ''}

                                    ${row.can_request_transfer ? `<li><a class="prod-menu-item" href="?route=inventory/transfers&product_id=${row.id}&source=${row.branch_id || ''}"><span class="menu-icon" style="background:#eef2ff;color:#5d87ff;"><span class="material-symbols-outlined" style="font-size:16px;" aria-hidden="true">swap_horiz</span></span>Request stock transfer</a></li>` : ''}
                                    ${row.can_transfer ? `<li><button type="button" class="prod-menu-item transfer-product-btn" data-id="${row.id}" data-source-branch="${row.branch_id || 'global'}" data-ingredient="${row.product_type === 'ingredient' || row.is_ingredient == 1 ? '1' : '0'}"><span class="menu-icon" style="background:#eef2ff;color:#5d87ff;"><span class="material-symbols-outlined" style="font-size:16px;" aria-hidden="true">move_up</span></span><span>Transfer product, ingredients &amp; stock</span></button></li>` : ''}

                                    <!-- Stock History -->
                                    ${row.can_view_history ? `<li>
                                        <button class="prod-menu-item view-history-btn"
                                            data-id="${row.id}" data-name="${row.name}">
                                            <span class="menu-icon" style="background:#f0f9ff;color:#0284c7;">
                                                <span class="material-symbols-outlined" style="font-size:16px;">history</span>
                                            </span>
                                            Stock History
                                        </button>
                                    </li>` : ''}

                                    ${row.can_delete ? `
                                    <div class="prod-action-divider"></div>
                                    <!-- Delete -->
                                    <li>
                                        <button class="prod-menu-item danger delete-product-btn"
                                            data-id="${row.id}" data-name="${row.name}">
                                            <span class="menu-icon" style="background:#fff5f5;color:#e53e3e;">
                                                <span class="material-symbols-outlined" style="font-size:16px;">delete</span>
                                            </span>
                                            Remove Product
                                        </button>
                                    </li>` : ''}
                                </ul>
                            </div>`;
                        }
                    }
                ],
                dom: '<"d-none"l>rt<"d-flex align-items-center justify-content-between p-4 border-top"<"small text-muted"i><"pagination-sm"p>>',
                language: {
                    lengthMenu: "Show _MENU_",
                    search: "",
                    paginate: { previous: '<span class="material-symbols-outlined">chevron_left</span>', next: '<span class="material-symbols-outlined">chevron_right</span>' },
                    emptyTable: `
                        <div class="text-center py-5">
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <span class="material-symbols-outlined fs-1 text-muted opacity-50">inventory_2</span>
                            </div>
                            <h5 class="fw-bold text-dark">No items in sight</h5>
                            <p class="text-muted small mx-auto" style="max-width: 300px;">We couldn't find any products matching your current filters or search query.</p>
                            <button id="reset-filters-btn" class="btn btn-outline-primary btn-sm rounded-pill px-4 mt-2"><span class="material-symbols-outlined inv-button-icon" aria-hidden="true">restart_alt</span>Reset All</button>
                        </div>`,
                    zeroRecords: `
                        <div class="text-center py-5">
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <span class="material-symbols-outlined fs-1 text-muted opacity-50">search_off</span>
                            </div>
                            <h5 class="fw-bold text-dark">No matching records</h5>
                            <p class="text-muted small mx-auto" style="max-width: 300px;">We couldn't find any products matching your current search or filters. Try adjusting your criteria.</p>
                            <button id="reset-filters-btn" class="btn btn-outline-primary btn-sm rounded-pill px-4 mt-2"><span class="material-symbols-outlined inv-button-icon" aria-hidden="true">filter_alt_off</span>Clear Search</button>
                        </div>`
                },
                drawCallback: function () {
                    document.querySelectorAll('#productsTable .dropdown-toggle-custom').forEach(el => {
                        if (!el._bsDropdown) {
                            try {
                                el._bsDropdown = new bootstrap.Dropdown(el);
                            } catch (e) {}
                        }
                    });
                },
                initComplete: function () {
                    const searchHtml = `
                        <div class="dt-search-container">
                            <span class="material-symbols-outlined dt-search-icon">search</span>
                            <input type="text" class="form-control dt-search-input" placeholder="Search catalog...">
                        </div>`;
                    $('.dt-custom-search').html(searchHtml);
                    $('.dt-search-input').val(this.api().search());

                    $('.dt-search-input').on('keyup', function () {
                        productsTable.search(this.value).page('first');productsTable.state.save();productsTable.draw();
                    });
                }
            });

            // Custom filter dropdown selection
            $('#sortFilter').on('change',function(){productsTable.page('first');productsTable.state.save();productsTable.draw();});
            $(document).on('click', '.filter-option', function () {
                const $btn = $(this);
                const targetId = $btn.data('target');
                const value = $btn.data('value');
                const label = $btn.data('label');

                // Update hidden input
                $('#' + targetId).val(value);

                // Update button label
                const labelId = targetId.replace('Filter', 'Label');
                $('#' + labelId).text(label);

                // Update active state in dropdown
                $btn.closest('ul').find('.filter-option').removeClass('active fw-600');
                $btn.addClass('active fw-600');

                // Toggle active-filter class on the pill button trigger
                const $pillBtn = $btn.closest('.dropdown').find('.inv-filter-pill');
                const isDefault = (value === 'all' || value === '0');
                $pillBtn.toggleClass('active-filter', !isDefault);

                // Persist before the request so refresh during loading keeps the selected scope.
                productsTable.page('first');productsTable.state.save();productsTable.draw();
            });

            // Export CSV with current filters
            $(document).on('click', '.export-csv-btn', function () {
                const branchId = $('#branchFilter').val();
                const categoryId = $('#categoryFilter').val();
                const stockStatus = $('#stockFilter').val();
                const search = $('.dt-search-input').val() || '';
                const params = new URLSearchParams({
                    route: 'inventory/products',
                    export: 'csv'
                });
                if (branchId && branchId !== '0') params.set('branch_id', branchId);
                if (categoryId && categoryId !== 'all') params.set('category_id', categoryId);
                if (stockStatus && stockStatus !== 'all') params.set('stock_status', stockStatus);
                if (search) params.set('search', search);
                window.location.href = '?' + params.toString();
            });

            $(document).on('click', '.view-product-btn', function () {
                const data = $(this).data();
                const modal = $('#viewProductModal');

                // Basic Info
                const row = productsTable.row($(this).closest('tr')).data();
                const stockUnit = String(row?.unit_type || data.unitType || 'pc');
                const formatQuantity = value => {
                    const number = Number(value);
                    return (Number.isFinite(number) ? number : 0).toLocaleString(undefined, {
                        minimumFractionDigits: 2, maximumFractionDigits: 2
                    }) + ' ' + stockUnit;
                };

                modal.find('#view_prod_id').text('#' + data.id.toString().padStart(4, '0'));
                modal.find('#view_prod_name').text(data.name);
                modal.find('#view_prod_description').text(data.description || 'No description provided.');
                // Enhanced Category Theming for View Modal
                const catTheme = getCategoryTheme(data.categoryName);
                modal.find('#view_prod_category')
                    .text(data.categoryName || 'Uncategorized')
                    .attr('title', data.categoryName || 'Uncategorized')
                    .css('color', catTheme.color);
                modal.find('#view_prod_category_icon')
                    .text(catTheme.icon)
                    .css('color', catTheme.iconColor);
                modal.find('#view_prod_category_icon_wrap').css({
                    'background': '#ffffff',
                    'color': catTheme.iconColor,
                    'box-shadow': '0 2px 6px rgba(0,0,0,0.04)'
                });
                modal.find('#view_prod_category_card').css({
                    'background': catTheme.bg,
                    'border-color': catTheme.border
                });

                modal.find('#view_prod_branch_text')
                    .text(data.branchName || 'Global (All Branches)')
                    .attr('title', data.branchName || 'Global (All Branches)');
                const typeLabel = data.type === 'combo' ? 'Package' : data.type === 'recipe' ? (row?.recipe_mode === 'made_to_order' ? 'Made to order' : 'Recipe') : data.type === 'variant' ? 'Variant' : data.type === 'ingredient' ? 'Ingredient' : 'Simple';
                modal.find('#view_prod_badge_type').text(typeLabel);

                // SKU & Barcode
                const skuVal = row?.sku || data.sku || '';
                modal.find('#view_prod_sku').text(skuVal || '-');

                const barcodeVal = row?.barcode || data.barcode || '';
                if (barcodeVal) {
                    modal.find('#view_prod_barcode').show();
                    modal.find('#view_prod_barcode_download').show();
                    modal.find('#view_prod_barcode_text').text(barcodeVal);

                    function drawBarcode() {
                        try {
                            JsBarcode('#view_prod_barcode', barcodeVal, {
                                format: 'CODE128',
                                width: 2,
                                height: 44,
                                displayValue: false,
                                background: '#ffffff',
                                lineColor: '#0f172a',
                                margin: 4,
                                valid: function (valid) {
                                    if (!valid) {
                                        document.getElementById('view_prod_barcode_text').textContent = barcodeVal;
                                    }
                                }
                            });
                        } catch (e) {
                            console.error('JsBarcode render error:', e);
                            document.getElementById('view_prod_barcode_text').textContent = barcodeVal;
                        }
                    }

                    if (typeof JsBarcode === 'undefined') {
                        $.getScript('https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js', function () {
                            drawBarcode();
                        });
                    } else {
                        drawBarcode();
                    }
                } else {
                    modal.find('#view_prod_barcode').hide();
                    modal.find('#view_prod_barcode_download').hide();
                    modal.find('#view_prod_barcode_text').html('<span class="badge bg-light text-muted fw-600 px-2 py-1" style="font-size:0.65rem;">NO BARCODE</span>');
                }

                // Price & stock display — use variant data if variant product
                let stock = parseFloat(data.stock);
                if (row && row.product_type === 'variant') {
                    const minP = parseFloat(row.variant_min_price || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const maxP = parseFloat(row.variant_max_price || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    modal.find('#view_prod_price').text('₱' + minP + ' – ₱' + maxP);
                    modal.find('#view_prod_cost').text('Per variant');
                    stock = parseInt(row.variant_total_stock || 0);
                } else {
                    modal.find('#view_prod_price').text('₱' + parseFloat(data.price).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    modal.find('#view_prod_cost').text('₱' + parseFloat(data.cost).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                }

                modal.find('#view_prod_stock').text(formatQuantity(stock));
                modal.find('#view_prod_threshold').text(formatQuantity(data.threshold));
                modal.find('#view_prod_max').text(formatQuantity(data.maxThreshold));
                modal.find('#view_prod_image').attr('src', data.image || 'assets/img/default.png');

                // Stock Status & Progress
                const max = parseFloat(data.maxThreshold) || 100;
                const thresh = parseFloat(data.threshold);
                const percent = Math.min(100, Math.round((stock / max) * 100));

                modal.find('#view_stock_percent').text(percent + '%');
                modal.find('#view_stock_progress').css('width', percent + '%').removeClass('bg-success bg-warning bg-danger');

                if (stock <= 0) {
                    modal.find('#view_stock_status').text('OUT OF STOCK').removeClass('bg-success bg-warning').addClass('bg-danger');
                    modal.find('#view_stock_progress').addClass('bg-danger');
                } else if (stock <= thresh) {
                    modal.find('#view_stock_status').text('LOW STOCK').removeClass('bg-success bg-danger').addClass('bg-warning');
                    modal.find('#view_stock_progress').addClass('bg-warning text-dark');
                } else {
                    modal.find('#view_stock_status').text('IN STOCK').removeClass('bg-warning bg-danger').addClass('bg-success');
                    modal.find('#view_stock_progress').addClass('bg-success');
                }

                window.loadProductInsightBreakdown(data, row);
                // Recipe Section
                if (row && row.product_type === 'recipe') {
                    // Load modifiers
                    $('#view_modifiers_section').removeClass('d-none');
                    $('#view_modifiers_list').html('<tr><td colspan="3" class="text-center py-2"><div class="spinner-border spinner-border-sm text-warning"></div></td></tr>');
                    $.post('?route=ajax/inventory_action', { action: 'get_product_modifiers', product_id: data.id, csrf_token: $('[name="csrf_token"]').val() }, function (res) {
                        $('#view_modifiers_list').empty();
                        if (res.success && res.modifiers.length > 0) {
                            res.modifiers.forEach(mod => {
                                const extraPrice = parseFloat(mod.additional_price) || 0;
                                const hasIngredient = mod.ingredient_product_id ? 'Deducts stock' : 'Charge only';
                                $('#view_modifiers_list').append(`
                                    <tr>
                                        <td class="ps-3 py-2 border-0">${mod.modifier_name}</td>
                                        <td class="text-end py-2 border-0">${extraPrice > 0 ? '₱' + extraPrice.toFixed(2) : '<span class="text-muted">Free</span>'}</td>
                                        <td class="pe-3 text-end py-2 border-0 text-muted">${hasIngredient}</td>
                                    </tr>
                                `);
                            });
                        } else {
                            $('#view_modifiers_list').html('<tr><td colspan="3" class="text-center py-2 text-muted">No modifiers defined.</td></tr>');
                        }
                    }, 'json');
                } else {

                    $('#view_modifiers_section').addClass('d-none');
                }

                // Batch List
                const syncBadge = $('#sync_badge');
                syncBadge.addClass('d-none');
                $('#view_batch_list').html('<tr><td colspan="7" class="text-center py-3 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading batch data...</td></tr>');
                $.post('?route=ajax/inventory_action', { action: 'get_batch_breakdown', product_id: data.id, csrf_token: $('[name="csrf_token"]').val() }, function (res) {
                    let html = '';
                    let totalQty = 0;
                    let totalCost = 0;
                    let totalSelling = 0;
                    const baseCost = parseFloat(res.base_cost) || 0;
                    const basePrice = parseFloat(res.base_price) || 0;
                    if (res.success && res.batches.length > 0) {
                        res.batches.forEach(batch => {
                            const bQty = parseFloat(batch.quantity);
                            const bCost = batch.cost_price ? parseFloat(batch.cost_price) : baseCost;
                            const bSelling = batch.selling_price ? parseFloat(batch.selling_price) : basePrice;
                            const bCostTotal = bQty * bCost;
                            const bSellingTotal = bQty * bSelling;
                            totalQty += bQty;
                            totalCost += bCostTotal;
                            totalSelling += bSellingTotal;
                            html += `<tr class="batch-row align-middle" data-id="${batch.id}">
                                <td class="ps-3 fw-bold text-dark"><span class="text-primary">#</span>${batch.batch_number}</td>
                                <td><span class="badge bg-soft-primary px-2 py-1 rounded-pill smaller fw-600">${batch.branch_name || 'Global'}</span></td>
                                <td class="text-center fw-bold" data-field="quantity">${$('<span>').text(formatQuantity(bQty)).html()}</td>
                                <td class="text-end text-muted" data-field="cost_price">₱${bCost.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                <td class="text-end text-muted" data-field="selling_price">₱${bSelling.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                <td class="text-end fw-bold text-dark" data-field="total_cost">₱${bCostTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                <td class="text-end fw-bold" style="color: #b60e3d;" data-field="total_selling">₱${bSellingTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                <td class="text-center pe-3">
                                    ${<?= json_encode(User::has_permission($_SESSION['user']['id'], 'edit_stock_batches')) ?> ? `<button class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center edit-batch-btn" style="width: 28px; height: 28px;" title="Edit Batch">
                                        <span class="material-symbols-outlined fs-6 text-primary">edit_square</span>
                                    </button>` : ''}
                                </td>
                            </tr>`;
                        });
                    } else {
                        html = '<tr><td colspan="8" class="text-center py-4 text-muted">No active batches found for this product.</td></tr>';
                    }
                    $('#view_batch_list').html(html);
                    $('#view_batch_total_qty').text(formatQuantity(totalQty));
                    $('#view_batch_total_cost').text('₱' + totalCost.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    $('#view_batch_total_selling').text('₱' + totalSelling.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                    if (res.was_synced) {
                        syncBadge.removeClass('d-none').text('Stock Corrected (Sync)');
                        modal.find('#view_prod_stock').text(formatQuantity(totalQty));
                        const thresh = parseFloat(data.threshold);
                        const max = parseFloat(data.maxThreshold) || 100;
                        const pct = Math.min(100, Math.round((totalQty / max) * 100));
                        modal.find('#view_stock_percent').text(pct + '%');
                        modal.find('#view_stock_progress').css('width', pct + '%');
                        if (typeof productsTable !== 'undefined') productsTable.ajax.reload(null, false);
                    }
                }, 'json');

                $('#view_edit_btn').toggle(<?= json_encode(User::has_permission($_SESSION['user']['id'], 'edit_product')) ?>);
                $('#view_edit_btn').off('click').on('click', function () {
                    modal.modal('hide');
                    $(`.edit-product-btn[data-id="${data.id}"]`).click();
                });

                modal.modal('show');
            });

            // Copy SKU & Barcode helper in View Details Modal
            $(document).on('click', '.copy-sku-btn, .copy-barcode-btn', function (e) {
                e.stopPropagation();
                const btn = $(this);
                const targetId = btn.data('target');
                const textToCopy = $(targetId).text().trim();
                if (textToCopy && textToCopy !== '-' && textToCopy !== 'NO BARCODE') {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(textToCopy).then(function () {
                            const icon = btn.find('.material-symbols-outlined');
                            icon.text('check').css('color', '#10b981');
                            setTimeout(function () {
                                icon.text('content_copy').css('color', '');
                            }, 1500);
                        });
                    }
                }
            });

            // Download Barcode as PNG
            $(document).on('click', '.download-barcode-btn, .barcode-preview-box', function (e) {
                e.stopPropagation();
                const barcodeVal = $('#view_prod_barcode_text').text().trim();
                if (!barcodeVal || barcodeVal === '-' || barcodeVal === 'NO BARCODE') return;

                const canvas = document.getElementById('view_prod_barcode');
                if (!canvas || !canvas.width || $(canvas).is(':hidden')) return;

                const btn = $('.download-barcode-btn');
                const prodName = $('#view_prod_name').text().trim() || 'product';
                const safeName = prodName.toLowerCase().replace(/[^a-z0-9]/g, '_');
                const filename = `barcode_${safeName}_${barcodeVal}.png`;

                try {
                    // Render high-res export canvas with clean white background and text
                    const exportCanvas = document.createElement('canvas');
                    const ctx = exportCanvas.getContext('2d');
                    const paddingX = 20;
                    const paddingY = 16;
                    const textHeight = 26;

                    exportCanvas.width = canvas.width + (paddingX * 2);
                    exportCanvas.height = canvas.height + (paddingY * 2) + textHeight;

                    // Clean white background
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, exportCanvas.width, exportCanvas.height);

                    // Draw barcode bars
                    ctx.drawImage(canvas, paddingX, paddingY);

                    // Draw barcode number centered at bottom
                    ctx.fillStyle = '#0f172a';
                    ctx.font = 'bold 15px "JetBrains Mono", "SF Mono", "Courier New", monospace';
                    ctx.textAlign = 'center';
                    ctx.fillText(barcodeVal, exportCanvas.width / 2, exportCanvas.height - paddingY + 2);

                    // Trigger browser download
                    const link = document.createElement('a');
                    link.download = filename;
                    link.href = exportCanvas.toDataURL('image/png');
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    // Visual feedback on the download button
                    const icon = btn.find('.material-symbols-outlined');
                    icon.text('check').css('color', '#10b981');
                    setTimeout(function () {
                        icon.text('download').css('color', '');
                    }, 1500);
                } catch (err) {
                    console.error('Barcode download error:', err);
                }
            });

            $(document).on('click', '#reset-filters-btn, #clearFilters', function () {
                console.log('🔄 Resetting all filters...');
                productsTable.state.clear();
                $('#branchFilter').val(inventoryDefaultBranch);
                $('#categoryFilter').val('all');
                $('#stockFilter').val('all');
                $('#sortFilter').val('newest');
                $('#branchLabel').text('All Branches');
                $('#categoryLabel').text('All Categories');
                $('#stockLabel').text('Stock Level');
                $('.filter-option').removeClass('active fw-600');
                $('.filter-option[data-value="0"], .filter-option[data-value="all"]').addClass('active fw-600');
                $('.inv-filter-pill').removeClass('active-filter');
                $('.dt-search-input').val('');
                syncInventoryFilterLabels();productsTable.search('').page('first');productsTable.state.save();productsTable.draw();
            });


            // Image Preview for Modals
            $(document).on('change', '#create_image_input', function () {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        $('#create_img_preview_new').attr('src', e.target.result).removeClass('d-none');
                        $('#upload_placeholder_new').addClass('d-none');
                    }
                    reader.readAsDataURL(file);
                }
            });

            $(document).on('change', '#edit_image_input', function () {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        $('#edit_img_preview').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(file);
                }
            });

            // Auto-generate SKU/barcode on edit form submit when empty, and commit pending custom attribute
            $(document).on('submit', '#editProductModal form', function () {
                const skuField = $(this).find('#edit_sku');
                const barcodeField = $(this).find('#edit_barcode');

                if (!skuField.val().trim()) {
                    const prefix = 'SKX-';
                    const year = new Date().getFullYear().toString().slice(-2);
                    const rand = Math.floor(Math.random() * 90000) + 10000;
                    skuField.val(prefix + year + rand);
                }

                if (!barcodeField.val().trim()) {
                    const rand = String(Math.floor(Math.random() * 90000000000) + 10000000000);
                    barcodeField.val('1' + rand.slice(0, 11));
                }

                const pendingAttr = $('#new_attr_name_edit').val().trim();
                if (pendingAttr) {
                    addAttributeField(pendingAttr, '', 'edit');
                    $('#new_attr_name_edit').val('');
                }
            });

            $(document).on('click', '.edit-product-btn', function () {
                const data = $(this).data();
                const row = productsTable.row($(this).closest('tr')).data();
                const modal = $('#editProductModal');

                modal.find('#edit_product_id').val(data.id);
                modal.find('#edit_prod_name').val(data.name);
                modal.find('#edit_prod_cat').val(data.category);
                modal.find('#edit_status').val(data.status);
                modal.find('#edit_sku').val(row?.sku || data.sku || '');
                modal.find('#edit_barcode').val(row?.barcode || data.barcode || '');
                modal.find('#edit_prod_desc').val(data.description);
                modal.find('#edit_prod_price').val(data.price);
                modal.find('#edit_prod_cost').val(data.cost);
                modal.find('#edit_prod_stock').val(Number(data.stock || 0).toFixed(2));
                modal.find('#edit_prod_thresh').val(Number(data.threshold || 0).toFixed(2));
                modal.find('#edit_prod_max_thresh').val(Number(data.maxThreshold || 0).toFixed(2));
                modal.attr('data-recipe-branch',data.branch==null?'':String(data.branch));
                if (modal.find('#edit_prod_branch').length) modal.find('#edit_prod_branch').val(data.branch);

                modal.find('#edit_track_stock').prop('checked', data.trackStock == 1);
                modal.find('#edit_is_sellable').prop('checked', data.sellable == 1);
                modal.find('#edit_show_on_website').prop('checked', data.website == 1);
                modal.find('#edit_website_badge').val(row?.website_badge || '');
                modal.find('#edit_website_occasion').val(row?.website_occasion || '');
                modal.find('#edit_website_stems').val(row?.website_stems || '');
                modal.find('#edit_is_discountable').prop('checked', data.discountable == 1);
                modal.find('#edit_is_ingredient').prop('checked', data.ingredient == 1);
                modal.find('#edit_inventory_type').val(data.inventoryType || 'stocked');
                const editUnit = String(row?.unit_type || data.unitType || 'pc');
                const editUnitSelect = modal.find('#edit_unit_type');
                if (!editUnitSelect.find('option').toArray().some(option => option.value === editUnit)) {
                    editUnitSelect.append(new Option(editUnit, editUnit));
                }
                editUnitSelect.find('option').prop('disabled', false);
                editUnitSelect.val(editUnit);
                editUnitSelect.data('saved-unit', editUnit);
                modal.find('#edit_product_type').val(data.type || 'simple');
                modal.find('#edit_recipe_mode').val(row?.recipe_mode || 'legacy');
                modal.find('#recipe_reconciled').prop('checked',false);

                modal.find('#edit_display_name').text(data.name);
                modal.find('#edit_img_preview').attr('src', data.image || 'assets/img/default.png');

                // Tab-based type toggling
                handleProductTypeChange(data.type || 'simple', 'edit');

                // Load variants for the edit modal
                loadProductVariants(data.id, 'edit');

                // Load attributes for edit modal
                loadProductAttributes(data.id, 'edit', row?.json_attributes || data.jsonAttributes);

                // Reset to general tab
                const firstTab = modal.find('#editProductTabs .nav-link:first');
                if (!firstTab.hasClass('active')) firstTab.tab('show');

                modal.modal('show');
            });

            // === Product Type Toggle ===
            function handleProductTypeChange(type, context) {
                const prefix = context === 'edit' ? 'edit_' : '';
                const recipeTabItem = context === 'edit' ? $('#edit_recipe_tab_item') : $('#recipe_tab_item');

                // Control Price and Sellable state
                const priceField = context === 'edit' ? $('#edit_prod_price') : $('#create_prod_price');
                const sellableSw = context === 'edit' ? $('#edit_is_sellable') : $('#sellable_sw');
                const ingredientSw = context === 'edit' ? $('#edit_is_ingredient') : $('#ingredient_sw');

                const stockField = context === 'edit' ? $('#edit_prod_stock') : $('[name="stock"]');
                const costField = context === 'edit' ? $('#edit_prod_cost') : $('#create_prod_cost');

                if (type === 'ingredient') {
                    priceField.closest('.col-6').hide();
                    priceField.val(0).prop('required', false);
                    sellableSw.prop('checked', false);
                    ingredientSw.prop('checked', true);
                    costField.closest('.col-6').show();
                    stockField.closest('.col-md-6').show();
                } else if (type === 'variant') {
                    priceField.closest('.col-6').hide();
                    priceField.val(0).prop('required', false);
                    costField.closest('.col-6').hide();
                    costField.val(0);
                    stockField.closest('.col-md-6').hide();
                    stockField.val(0);
                    sellableSw.prop('checked', true);
                    ingredientSw.prop('checked', false);
                } else {
                    priceField.closest('.col-6').show();
                    priceField.prop('required', true);
                    costField.closest('.col-6').show();
                    stockField.closest('.col-md-6').show();
                    if (type === 'recipe') sellableSw.prop('checked', true);
                }

                if (type === 'recipe') {
                    recipeTabItem.removeClass('d-none');
                    if (context === 'edit') {
                        const productId = $('#edit_product_id').val();
                        if (productId) loadProductRecipe(productId);
                    } else if (context === 'create') {
                        if ($('#ingredients_list_create .ingredient-row').length === 0) {
                            addIngredientRow(null, '#ingredients_list_create');
                        }
                    }
                } else {
                    recipeTabItem.addClass('d-none');
                }
            }

            $('#edit_product_type').on('change', function () {
                handleProductTypeChange($(this).val(), 'edit');
            });

            $('#create_product_type').on('change', function () {
                handleProductTypeChange($(this).val(), 'create');
            });

            // === RECIPE LOGIC ===
            let ingredientsData = <?= json_encode($ingredients) ?>;

            function loadProductRecipe(productId) {
                $('#ingredients_list').html('<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>');
                $('#no_ingredients_msg').addClass('d-none');
                console.log('📦 Loading recipe for product:', productId);
                $.post('?route=ajax/inventory_action', { action: 'get_product_recipe', product_id: productId, csrf_token: $('[name="csrf_token"]').val() }, function (response) {
                    console.log('📦 Recipe response:', response);
                    $('#ingredients_list').empty();
                    if (response.success && response.recipe.length > 0) {
                        response.recipe.forEach(item => addIngredientRow(item, '#ingredients_list'));
                        checkRecipeAvailability(productId);
                    } else {
                        $('#no_ingredients_msg').removeClass('d-none');
                    }
                }, 'json');
                loadProductModifiers(productId, 'edit');
            }

            // Refresh availability when switching to recipe tab
            $(document).on('shown.bs.tab', 'button[data-bs-target="#edit-recipe-tab"]', function () {
                const productId = $('#edit_product_id').val();
                if (productId) checkRecipeAvailability(productId);
            });

            function formatQty(val) {
                const num = parseFloat(val);
                if (isNaN(num)) return '';
                return num % 1 === 0 ? num.toString() : num.toFixed(4);
            }

            function ingredientUnitOptions(base, current = base) {
                const aliases = {pcs:'pc', piece:'pc', pieces:'pc', gram:'g', grams:'g', kilogram:'kg', kilograms:'kg', liter:'l', litre:'l', liters:'l', milliliter:'ml'};
                const canonical = value => { const unit = String(value || 'pc').trim().toLowerCase(); return aliases[unit] || unit; };
                base = canonical(base);
                current = canonical(current);
                const units = ['g','kg'].includes(base) ? ['g','kg'] : ['ml','l'].includes(base) ? ['ml','l'] : [base];
                const labels = {pc:'pc', g:'g', kg:'kg', ml:'ml', l:'L'};
                const options = $('<select>');
                // Preserve an older incompatible value so saving cannot silently reinterpret its quantity.
                if (!units.includes(current)) options.append($('<option>').val(current).text(current + ' (check unit)').prop('selected',true));
                units.forEach(unit => options.append($('<option>').val(unit).text(labels[unit] || unit).prop('selected',unit === current)));
                return options.html();
            }

            function scopedIngredients(listSelector) {
                const editing = listSelector === '#ingredients_list' || listSelector === '#modifiers_list';
                const branch = editing ? $('#editProductModal').attr('data-recipe-branch') : $('#createProductModal [name="branch_id"]').val();
                const selectedBranches=$('#createProductModal [name="branch_ids[]"]:checked').map(function(){return this.value;}).get();
                if(!editing && $('#createProductModal .product-branch-picker').length)return ingredientsData.filter(ingredient=>ingredient.branch_id===null || (selectedBranches.length===1&&String(ingredient.branch_id)===selectedBranches[0]));
                const fallback = <?= json_encode($_SESSION['user']['branch_id']??null) ?>;
                const scope = branch === undefined ? fallback : branch;
                return ingredientsData.filter(ingredient => ingredient.branch_id === null || (scope !== null && scope !== '' && String(ingredient.branch_id) === String(scope)));
            }

            function addIngredientRow(data = null, listSelector = '#ingredients_list') {
                const rowId = 'row_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
                let options = '<option value="" disabled selected>Select Ingredient</option>';
                scopedIngredients(listSelector).forEach(ing => {
                    const selected = data && data.ingredient_product_id == ing.id ? 'selected' : '';
                    const unit = ing.unit_type || 'pc';
                    options += `<option value="${ing.id}" data-unit="${unit}" data-cost="${ing.cost_price || 0}" ${selected}>${ing.name}</option>`;
                });
                const index = Date.now().toString() + Math.floor(Math.random()*100000).toString();
                const initialUnit = data ? (data.unit || (ingredientsData.find(i => i.id == data.ingredient_product_id)?.unit_type || 'pc')) : 'pc';
                const html = `
                    <div class="ingredient-row d-flex gap-2 align-items-center bg-white p-2 rounded-3 border shadow-sm mb-2" id="${rowId}">
                        <div class="flex-grow-1">
                            <select name="recipe[${index}][ingredient_id]" class="form-select form-select-sm border-0 bg-light-gray ing-select">${options}</select>
                        </div>
                        <div style="width: 100px;">
                            <input type="number" name="recipe[${index}][quantity]" class="form-control form-control-sm border-0 bg-light-gray text-center ing-qty" placeholder="Qty" step="0.0001" min="0.0001" value="${data ? formatQty(data.quantity) : ''}">
                        </div>
                        <div style="width: 100px;">
                            <select name="recipe[${index}][unit]" aria-label="Ingredient unit" class="form-select form-select-sm border-0 bg-light-gray text-center ing-unit">${ingredientUnitOptions(ingredientsData.find(i => i.id == data?.ingredient_product_id)?.unit_type || initialUnit, initialUnit)}</select>
                        </div>
                        <button type="button" class="btn btn-link text-danger p-0 remove-ingredient" data-row="${rowId}">
                            <span class="material-symbols-outlined">delete_sweep</span>
                        </button>
                    </div>`;
                $(listSelector).append(html);
                $(document).trigger('recipe:changed');
            }

            $('#edit_prod_branch,.product-branch-picker input').on('change',function(){
                const editing=this.id==='edit_prod_branch';
                if(editing)$('#editProductModal').attr('data-recipe-branch',this.value);
                const scope=editing?'#editProductModal':'#createProductModal';
                if(!editing){const branches=$('#createProductModal [name="branch_ids[]"]:checked').map(function(){return this.value;}).get();$('#createProductModal [name="branch_id"]').val(branches.length===1?branches[0]:'');}
                $(scope+' .ing-select,'+scope+' .mod-ing-select').each(function(){
                    const select=$(this),previous=select.val(),modifier=select.hasClass('mod-ing-select');
                    select.empty().append($('<option>').val('').text(modifier?'None (Charge only)':'Select Ingredient'));
                    scopedIngredients(editing?'#ingredients_list':'#ingredients_list_create').forEach(ingredient=>select.append($('<option>').val(ingredient.id).text(ingredient.name).attr('data-unit',ingredient.unit_type||'pc').attr('data-cost',ingredient.cost_price||0)));
                    select.val(select.find('option').toArray().some(option=>option.value===String(previous))?previous:'');
                    select.prev('.ingredient-search').val('');
                });
                $(document).trigger('recipe:changed');
            });
            $('#add_ingredient_row').on('click', () => addIngredientRow(null, '#ingredients_list'));
            $('#add_ingredient_row_create').on('click', () => addIngredientRow(null, '#ingredients_list_create'));
            $(document).on('recipe:import', function (event, rows) {
                $('#ingredients_list_create').empty();
                rows.forEach(row => addIngredientRow(row, '#ingredients_list_create'));
                $(document).trigger('recipe:changed');
            });

            $(document).on('change', '.ing-select', function () {
                const unit = $(this).find('option:selected').data('unit') || 'pc';
                $(this).closest('.ingredient-row').find('.ing-unit').html(ingredientUnitOptions(unit));
            });

            $(document).on('change', '.mod-ing-select', function () {
                const unit = ingredientsData.find(i => i.id == $(this).val())?.unit_type || 'pc';
                $(this).closest('.modifier-row').find('.mod-unit').html(ingredientUnitOptions(unit));
            });

            $(document).on('click', '.remove-ingredient', function () {
                const rowId = $(this).data('row');
                $(`#${rowId}`).remove();
            });

            $('#save_recipe_btn').on('click', function () {
                const btn = $(this);
                const productId = $('#edit_product_id').val();
                const ingredients = [];
                $('#ingredients_list .ingredient-row').each(function () {
                    const id = $(this).find('.ing-select').val();
                    const qty = $(this).find('.ing-qty').val();
                    const unit = $(this).find('.ing-unit').val();
                    if (id && qty) ingredients.push({ ingredient_id: id, quantity: qty, unit: unit });
                });

                console.log('📦 Saving recipe for product:', productId, 'ingredients:', ingredients);
                const modifiers=[];
                $('#modifiers_list_edit .modifier-row').each(function () {
                    const name=$(this).find('.mod-name').val().trim();
                    if(name) modifiers.push({modifier_name:name,additional_price:$(this).find('.mod-price').val() || '0',
                        ingredient_product_id:$(this).find('.mod-ing-select').val(),ingredient_quantity:$(this).find('.mod-qty').val() || '1',ingredient_unit:$(this).find('.mod-unit').val() || ''});
                });
                btn.prop('disabled', true).html('<div class="spinner-border spinner-border-sm"></div>');
                $.post('?route=ajax/inventory_action', { action: 'save_product_recipe', product_id: productId, ingredients: ingredients, modifiers_json: JSON.stringify(modifiers), csrf_token: $('[name="csrf_token"]').val() }, function (res) {
                    console.log('📦 Save recipe response:', res);
                    btn.prop('disabled', false).html('<span class="material-symbols-outlined fs-6">save</span> Save Recipe');
                    if (res.success) {
                        // After saving recipe, save modifiers
                        // Recipe and extras were saved in one transaction.
                        showToast('Recipe saved successfully!', 'success');
                    } else {
                        console.error('❌ Recipe save failed:', res);
                        showToast(res.message || 'Failed to save recipe.', 'danger');
                    }
                }, 'json');
            });

            // === MODIFIERS LOGIC ===
            function addModifierRow(data = null, context = 'edit') {
                const tbody = context === 'edit' ? '#modifiers_list_edit' : '#modifiers_list_create';
                const existingPlaceholder = $(tbody).find('td[colspan]');
                if (existingPlaceholder.length) existingPlaceholder.closest('tr').remove();

                const index = Date.now().toString() + Math.floor(Math.random()*100000).toString();
                let ingOptions = '<option value="">None (Charge only)</option>';
                scopedIngredients(context==='edit'?'#modifiers_list':'#modifiers_list_create').forEach(ing => {
                    ingOptions += `<option value="${ing.id}" ${data && data.ingredient_product_id == ing.id ? 'selected' : ''}>${ing.name}</option>`;
                });

                const html = `
                    <tr class="modifier-row">
                        <td class="ps-3">
                            <input type="text" name="modifiers[${index}][modifier_name]" class="form-control form-control-sm border-0 bg-light-gray mod-name" placeholder="e.g., Extra Leche Flan" value="${data ? data.modifier_name : ''}">
                        </td>
                        <td>
                            <select name="modifiers[${index}][ingredient_product_id]" class="form-select form-select-sm border-0 bg-light-gray mod-ing-select">
                                ${ingOptions}
                            </select>
                            <div class="d-flex gap-1 mt-1">
                                <input type="number" aria-label="Ingredient quantity per add-on" name="modifiers[${index}][ingredient_quantity]" class="form-control form-control-sm mod-qty" value="${data?.ingredient_quantity || 1}" min="0.0001" step="0.0001">
                                <select aria-label="Ingredient unit" name="modifiers[${index}][ingredient_unit]" class="form-select form-select-sm mod-unit">${ingredientUnitOptions(ingredientsData.find(i => i.id == data?.ingredient_product_id)?.unit_type || 'pc', data?.ingredient_unit || ingredientsData.find(i => i.id == data?.ingredient_product_id)?.unit_type || 'pc')}</select>
                            </div>
                        </td>
                        <td>
                            <input type="number" name="modifiers[${index}][additional_price]" class="form-control form-control-sm border-0 bg-light-gray mod-price text-center" step="0.01" placeholder="0.00" value="${data ? data.additional_price : ''}">
                        </td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-modifier-row">
                                <span class="material-symbols-outlined fs-5">delete_sweep</span>
                            </button>
                        </td>
                    </tr>`;
                $(tbody).append(html);
            }

            function loadProductModifiers(productId, context) {
                const tbody = context === 'edit' ? '#modifiers_list_edit' : '#modifiers_list_create';
                $(tbody).html('<tr><td colspan="4" class="text-center py-3"><div class="spinner-border spinner-border-sm text-warning"></div></td></tr>');
                $.post('?route=ajax/inventory_action', { action: 'get_product_modifiers', product_id: productId, csrf_token: $('[name="csrf_token"]').val() }, function (res) {
                    $(tbody).empty();
                    if (res.success && res.modifiers.length > 0) {
                        res.modifiers.forEach(m => addModifierRow(m, context));
                    } else {
                        $(tbody).html('<tr><td colspan="4" class="text-center py-3 text-muted fst-italic fs-8">No modifiers yet.</td></tr>');
                    }
                }, 'json');
            }

            function saveProductModifiers(productId) {
                const modifiers = [];
                $('#modifiers_list_edit .modifier-row').each(function () {
                    const name = $(this).find('.mod-name').val().trim();
                    const ingId = $(this).find('.mod-ing-select').val();
                    const price = $(this).find('.mod-price').val() || 0;
                    if (name) {
                        modifiers.push({ modifier_name: name, additional_price: price, ingredient_product_id: ingId, ingredient_quantity: $(this).find('.mod-qty').val() || '1', ingredient_unit: $(this).find('.mod-unit').val() || '' });
                    }
                });
                $.post('?route=ajax/inventory_action', { action: 'save_product_modifiers', product_id: productId, modifiers: modifiers, csrf_token: $('[name="csrf_token"]').val() }, function (res) {
                    console.log('Modifiers sync status:', res);
                }, 'json');
            }

            $(document).on('click', '#add_modifier_row_edit', () => addModifierRow(null, 'edit'));
            $(document).on('click', '#add_modifier_row_create', () => addModifierRow(null, 'create'));
            $(document).on('click', '.remove-modifier-row', function () {
                const tbody = $(this).closest('tbody');
                $(this).closest('tr.modifier-row').remove();
                if (tbody.find('.modifier-row').length === 0) {
                    tbody.html('<tr><td colspan="3" class="text-center py-3 text-muted fst-italic fs-8">No modifiers yet.</td></tr>');
                }
            });

            // === RECIPE AVAILABILITY LOGIC ===
            function checkRecipeAvailability(productId) {
                const alertBox = $('#recipe_availability_alert');
                const details = $('#shortage_details');

                alertBox.addClass('d-none');
                details.empty();

                $.post('ajax/inventory_action.php', { action: 'check_recipe_availability', product_id: productId, csrf_token: $('[name="csrf_token"]').val() }, function (res) {
                    if (res.success && !res.ok) {
                        alertBox.removeClass('d-none');
                        let shortageHtml = '<ul class="mb-0 ps-3 mt-1">';
                        res.shortage.forEach(s => {
                            shortageHtml += `<li><strong>${s.ingredient}</strong>: Need ${s.required}${s.unit}, only have ${s.available}${s.unit}</li>`;
                        });
                        shortageHtml += '</ul>';
                        details.html(shortageHtml);
                    }
                }, 'json');
            }

            // === VARIANTS LOGIC ===
            function loadProductVariants(productId, context) {
                const tbody = context === 'edit' ? '#variants_list_body_edit' : '#variants_list_body_create';
                const loading = context === 'edit' ? $('#variants_loading') : null;
                if (loading) loading.removeClass('d-none');
                $(tbody).html('<tr><td colspan="6" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>');
                $.post('ajax/inventory_action.php', { 
                    action: 'get_product_variants', 
                    product_id: productId,
                    csrf_token: $('[name="csrf_token"]').val() 
                }, function (res) {
                    if (loading) loading.addClass('d-none');
                    $(tbody).empty();
                    if (res.success && res.variants && res.variants.length > 0) {
                        res.variants.forEach(v => addVariantRow(v, context));
                    } else {
                        $(tbody).html('<tr><td colspan="6" class="text-center py-4 text-muted fs-8">No variants found. Add one above.</td></tr>');
                    }
                }, 'json').fail(function () {
                    if (loading) loading.addClass('d-none');
                    $(tbody).html('<tr><td colspan="6" class="text-center py-4 text-danger fs-8">Failed to load variants.</td></tr>');
                });
            }

            function addVariantRow(data = null, context = 'edit') {
                const tbody = context === 'edit' ? '#variants_list_body_edit' : '#variants_list_body_create';
                const existingPlaceholder = $(tbody).find('td[colspan]');
                if (existingPlaceholder.length) existingPlaceholder.closest('tr').remove();

                const index = $(tbody).find('.variant-row').length;
                const html = `
                    <tr class="variant-row">
                        <td class="ps-3">
                            <input type="hidden" name="variants[${index}][id]" class="v-id" value="${data ? (data.id || '') : ''}">
                            <input type="text" name="variants[${index}][variant_name]" class="form-control form-control-sm border-0 bg-light-gray v-name" placeholder="e.g., Large" value="${data ? (data.variant_name || data.name || '') : ''}">
                        </td>
                        <td><input type="text" name="variants[${index}][sku]" class="form-control form-control-sm border-0 bg-light-gray v-sku" placeholder="SKU" value="${data ? (data.sku || '') : ''}"></td>
                        <td><input type="number" name="variants[${index}][price]" class="form-control form-control-sm border-0 bg-light-gray v-price" step="0.01" placeholder="0.00" value="${data ? (data.price !== undefined && data.price !== null ? data.price : '') : ''}"></td>
                        <td><input type="number" name="variants[${index}][cost_price]" class="form-control form-control-sm border-0 bg-light-gray v-cost" step="0.01" placeholder="0.00" value="${data ? (data.cost_price !== undefined && data.cost_price !== null ? data.cost_price : '') : ''}"></td>
                        <td><input type="number" name="variants[${index}][stock]" class="form-control form-control-sm border-0 bg-light-gray v-stock" placeholder="0" value="${data ? (data.stock !== undefined && data.stock !== null ? data.stock : '') : ''}"></td>
                        <td class="text-end pe-3"><button type="button" class="btn btn-sm btn-link text-danger p-0 remove-variant-row"><span class="material-symbols-outlined fs-5">delete</span></button></td>
                    </tr>`;
                $(tbody).append(html);
            }

            $(document).on('click', '#add_variant_btn_edit', () => addVariantRow(null, 'edit'));
            $(document).on('click', '#add_variant_btn_create', () => addVariantRow(null, 'create'));

            $(document).on('click', '.remove-variant-row', function () {
                const tbody = $(this).closest('tbody');
                $(this).closest('tr.variant-row').remove();
                if (tbody.find('.variant-row').length === 0) {
                    tbody.html('<tr><td colspan="6" class="text-center py-4 text-muted fs-8">No variants found. Add one above.</td></tr>');
                }
            });

            $('#save_variants_btn').on('click', function () {
                const btn = $(this);
                const productId = $('#edit_product_id').val();
                if (!productId) {
                    showToast('Please select a valid product first.', 'warning');
                    return;
                }

                const variants = [];
                $('#variants_list_body_edit .variant-row').each(function () {
                    const name = $(this).find('.v-name').val().trim();
                    if (!name) return;
                    variants.push({
                        id: $(this).find('.v-id').val(),
                        name: name,
                        variant_name: name,
                        sku: $(this).find('.v-sku').val().trim(),
                        price: $(this).find('.v-price').val() !== '' ? parseFloat($(this).find('.v-price').val()) : 0,
                        cost_price: $(this).find('.v-cost').val() !== '' ? parseFloat($(this).find('.v-cost').val()) : 0,
                        stock: $(this).find('.v-stock').val() !== '' ? parseInt($(this).find('.v-stock').val()) : 0,
                        barcode: ''
                    });
                });

                btn.prop('disabled', true).html('<div class="spinner-border spinner-border-sm"></div> Saving...');
                $.post('ajax/inventory_action.php', { 
                    action: 'save_product_variants', 
                    product_id: productId, 
                    variants: variants,
                    csrf_token: $('[name="csrf_token"]').val()
                }, function (res) {
                    btn.prop('disabled', false).html('<span class="material-symbols-outlined fs-6">save</span> Save Variants');
                    if (res.success) {
                        showToast(res.message || 'Variants saved successfully!', 'success');
                        loadProductVariants(productId, 'edit');
                    } else {
                        showToast(res.message || 'Failed to save variants.', 'danger');
                    }
                }, 'json').fail(function (xhr) {
                    btn.prop('disabled', false).html('<span class="material-symbols-outlined fs-6">save</span> Save Variants');
                    let errMsg = 'Failed to save variants.';
                    try {
                        const parsed = JSON.parse(xhr.responseText);
                        if (parsed.message) errMsg = parsed.message;
                    } catch(e) {}
                    showToast(errMsg, 'danger');
                });
            });

            // === DYNAMIC ATTRIBUTES LOGIC ===
            function loadProductAttributes(productId, context, existingJson) {
                const container = context === 'edit' ? '#attributes_container_edit' : '#attributes_container_create';
                const noMsg = context === 'edit' ? $('#no_attributes_msg_edit') : null;
                const loading = context === 'edit' ? $('#attributes_loading_edit') : null;

                $(container).empty();
                if (noMsg) noMsg.addClass('d-none');

                let attrs = null;
                if (existingJson) {
                    try {
                        attrs = typeof existingJson === 'string' ? JSON.parse(existingJson) : existingJson;
                    } catch (e) {
                        attrs = null;
                    }
                }

                if (attrs && typeof attrs === 'object' && Object.keys(attrs).length > 0) {
                    Object.entries(attrs).forEach(([key, val]) => addAttributeField(key, val, context));
                    return;
                }

                // If edit context with product ID, fetch freshest attributes via AJAX
                if (context === 'edit' && productId) {
                    if (loading) loading.removeClass('d-none');
                    $.post('ajax/inventory_action.php', {
                        action: 'get_product_attribute_values',
                        product_id: productId,
                        csrf_token: $('[name="csrf_token"]').val()
                    }, function (res) {
                        if (loading) loading.addClass('d-none');
                        $(container).empty();
                        if (res.success && res.values && typeof res.values === 'object' && Object.keys(res.values).length > 0) {
                            Object.entries(res.values).forEach(([key, val]) => addAttributeField(key, val, context));
                        } else {
                            if (noMsg) noMsg.removeClass('d-none');
                        }
                    }, 'json').fail(function () {
                        if (loading) loading.addClass('d-none');
                        if (noMsg) noMsg.removeClass('d-none');
                    });
                } else {
                    if (noMsg) noMsg.removeClass('d-none');
                }
            }

            function addAttributeField(key = '', value = '', context = 'edit') {
                const container = context === 'edit' ? '#attributes_container_edit' : '#attributes_container_create';
                const noMsg = context === 'edit' ? $('#no_attributes_msg_edit') : null;
                if (noMsg) noMsg.addClass('d-none');

                const safeKey = $('<div>').text(key).html();
                const safeVal = $('<div>').text(value).html();

                const fieldHtml = `
                    <div class="col-md-6 attribute-field-group">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text border-0 bg-white fw-bold fs-9" style="min-width: 100px; text-transform: uppercase; letter-spacing: 0.5px;">${safeKey}</span>
                            <input type="text" name="attributes[${safeKey}]" class="form-control border-0 bg-light-gray shadow-none attr-val-input" value="${safeVal}" placeholder="${safeKey} value">
                            <button type="button" class="btn btn-sm btn-link text-danger remove-attr-field"><span class="material-symbols-outlined fs-5">close</span></button>
                        </div>
                    </div>`;
                $(container).append(fieldHtml);
            }

            $(document).on('click', '.remove-attr-field', function () {
                const container = $(this).closest('.row');
                $(this).closest('.attribute-field-group').remove();
                if (container.find('.attribute-field-group').length === 0) {
                    if (container.attr('id') === 'attributes_container_edit') {
                        $('#no_attributes_msg_edit').removeClass('d-none');
                    }
                }
            });

            $(document).on('click', '#add_custom_attr_btn_edit', function () {
                const name = $('#new_attr_name_edit').val().trim();
                if (!name) return;
                addAttributeField(name, '', 'edit');
                $('#new_attr_name_edit').val('');
            });

            $(document).on('click', '#add_custom_attr_btn_create', function () {
                const name = $('#new_attr_name_create').val().trim();
                if (!name) return;
                addAttributeField(name, '', 'create');
                $('#new_attr_name_create').val('');
            });

            $(document).on('keydown', '#new_attr_name_edit', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    $('#add_custom_attr_btn_edit').trigger('click');
                }
            });

            $(document).on('keydown', '#new_attr_name_create', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    $('#add_custom_attr_btn_create').trigger('click');
                }
            });

            $('#save_attributes_btn').on('click', function () {
                const btn = $(this);
                const productId = $('#edit_product_id').val();
                if (!productId) {
                    showToast('Please select a valid product first.', 'warning');
                    return;
                }

                // If user typed a field name in the input but forgot to click "Add Field", commit it
                const pendingName = $('#new_attr_name_edit').val().trim();
                if (pendingName) {
                    addAttributeField(pendingName, '', 'edit');
                    $('#new_attr_name_edit').val('');
                }

                const attributes = {};
                $('#attributes_container_edit .attribute-field-group').each(function () {
                    const key = $(this).find('.input-group-text').text().trim();
                    const val = $(this).find('.attr-val-input').val().trim();
                    if (key) {
                        attributes[key] = val;
                    }
                });

                btn.prop('disabled', true).html('<div class="spinner-border spinner-border-sm"></div> Saving...');
                $.post('ajax/inventory_action.php', {
                    action: 'save_product_attributes',
                    product_id: productId,
                    attributes: attributes,
                    csrf_token: $('[name="csrf_token"]').val()
                }, function (res) {
                    btn.prop('disabled', false).html('<span class="material-symbols-outlined" style="font-size: 14px;">save</span> Save Attributes');
                    if (res.success) {
                        showToast(res.message || 'Custom attributes saved successfully!', 'success');
                        // Update the row data in productsTable so reopening doesn't have stale cache
                        const rowElem = $(`.edit-product-btn[data-id="${productId}"]`).closest('tr');
                        if (rowElem.length) {
                            const rowData = productsTable.row(rowElem).data();
                            if (rowData) {
                                rowData.json_attributes = JSON.stringify(attributes);
                            }
                        }
                    } else {
                        showToast(res.message || 'Failed to save custom attributes.', 'danger');
                    }
                }, 'json').fail(function (xhr) {
                    btn.prop('disabled', false).html('<span class="material-symbols-outlined" style="font-size: 14px;">save</span> Save Attributes');
                    let errMsg = 'Failed to save custom attributes.';
                    try {
                        const parsed = JSON.parse(xhr.responseText);
                        if (parsed.message) errMsg = parsed.message;
                    } catch(e) {}
                    showToast(errMsg, 'danger');
                });
            });

            // === DELETE PRODUCT ===
            $(document).on('click', '.delete-product-btn', function () {
                const data = $(this).data();
                const modal = $('#deleteProductModal');
                modal.find('[name="product_id"]').val(data.id);
                modal.find('#deleteProductName').text(data.name);
                modal.modal('show');
            });

            // Handle dropdown clipping inside table stacking context: elevate both row and cell to highest z-index
            $(document).on('show.bs.dropdown', '#productsTable .dropdown', function () {
                const $dropdown = $(this);
                const $cell = $dropdown.closest('td');
                const $row = $dropdown.closest('tr');
                $dropdown.css({ 'position': 'relative', 'z-index': '999999' });
                $cell.addClass('dropdown-open').css({ 'position': 'relative', 'z-index': '99999' });
                $row.addClass('dropdown-open').css({ 'position': 'relative', 'z-index': '99999' });
            }).on('hide.bs.dropdown', '#productsTable .dropdown', function () {
                const $dropdown = $(this);
                const $cell = $dropdown.closest('td');
                const $row = $dropdown.closest('tr');
                $dropdown.css({ 'position': '', 'z-index': '' });
                $cell.removeClass('dropdown-open').css({ 'position': '', 'z-index': '' });
                $row.removeClass('dropdown-open').css({ 'position': '', 'z-index': '' });
            });

            // --- Unit Filtering Logic ---
            const unitMappings = {
                'weighted': ['kg', 'g', 'l', 'ml', 'gal', 'oz', 'lbs', 'yd', 'm', 'cm', 'in'],
                'recipe': ['cp', 'gls', 'srv', 'set', 'pc', 'pcs', 'bot', 'jr', 'try', 'pl', 'tb', 'cn', 'can'],
                'service': ['srv', 'set', 'pc', 'pcs', 'hr', 'day', 'cn', 'can'],
                'simple': ['pc', 'pcs', 'set', 'box', 'pck', 'bndl', 'dz', 'cse', 'bot', 'jr', 'cn', 'can', 'tb', 'pl', 'try', 'pr', 'rl', 'sck', 'dz'],
                'variant': ['pc', 'pcs', 'set', 'box', 'pck', 'bndl', 'dz', 'cse', 'bot', 'jr', 'cn', 'can', 'tb', 'pl', 'try', 'pr', 'rl', 'sck', 'dz'],
                'combo': ['pc', 'pcs', 'set', 'box', 'pck', 'bndl', 'dz', 'cse', 'set', 'bndl', 'cn', 'can'],
                'digital': ['pc', 'set', 'srv'],
                'ingredient': [] // Show all for ingredients
            };

            function updateUnitOptions(typeSelectId, unitSelectId) {
                const type = $(`#${typeSelectId}`).val();
                const $unitSelect = $(`#${unitSelectId}`);
                const allowedUnits = unitMappings[type] || [];

                $unitSelect.find('option').each(function () {
                    const unitValue = $(this).val();
                    // Always show the placeholder (empty value)
                    if (unitValue === "" || allowedUnits.length === 0 || allowedUnits.includes(unitValue)
                        || (unitSelectId === 'edit_unit_type' && unitValue === $unitSelect.data('saved-unit'))) {
                        $(this).show().prop('disabled', false);
                    } else {
                        $(this).hide().prop('disabled', true);
                    }
                });

                // If currently selected unit is now disabled, pick the first enabled one
                if ($unitSelect.find('option:selected').prop('disabled')) {
                    $unitSelect.val($unitSelect.find('option:not(:disabled)').first().val());
                }
            }

            // Listeners for Create Modal
            $('#create_product_type').on('change', function () {
                updateUnitOptions('create_product_type', 'create_unit_type');
            });

            // Listeners for Edit Modal
            $('#edit_product_type').on('change', function () {
                updateUnitOptions('edit_product_type', 'edit_unit_type');
            });

            // Trigger on modal show
            $('#createProductModal').on('shown.bs.modal', function () {
                updateUnitOptions('create_product_type', 'create_unit_type');
            });

            $('#editProductModal').on('shown.bs.modal', function () {
                updateUnitOptions('edit_product_type', 'edit_unit_type');
            });

            // Auto-generate Batch Number for New Product & reset to first tab
            $('#createProductModal').on('show.bs.modal', function () {
                const firstTab = $(this).find('#productTabs .nav-link:first');
                if (!firstTab.hasClass('active')) firstTab.tab('show');
                const csrfToken = $('input[name="csrf_token"]').val();
                $.ajax({
                    url: 'ajax/inventory_action.php',
                    method: 'POST',
                    data: {
                        action: 'get_next_product_id',
                        csrf_token: csrfToken
                    },
                    success: function (response) {
                        if (response.success) {
                            const nextId = response.next_id.toString().padStart(3, '0');
                            const batchNum = `BATCH-${nextId}-001`;
                            $('#initial_batch_number').val(batchNum);
                            console.log('✨ Auto-generated Batch Number:', batchNum);
                        } else {
                            // Fallback if AJAX fails
                            const now = new Date();
                            const dateStr = now.getFullYear().toString() +
                                (now.getMonth() + 1).toString().padStart(2, '0') +
                                now.getDate().toString().padStart(2, '0');
                            const batchNum = `BATCH-NEW-${dateStr}`;
                            $('#initial_batch_number').val(batchNum);
                        }
                    },
                    error: function () {
                        const now = new Date();
                        const dateStr = now.getFullYear().toString() +
                            (now.getMonth() + 1).toString().padStart(2, '0') +
                            now.getDate().toString().padStart(2, '0');
                        $('#initial_batch_number').val(`BATCH-NEW-${dateStr}`);
                    }
                });
            });
        });
    </script>
    <?php
    ?>
    <script src="<?= url('assets/js/inventory-controls.js') ?>?v=<?= filemtime(__DIR__.'/../../assets/js/inventory-controls.js') ?>"></script>
    <script src="<?= url('assets/js/recipe-admin.js') ?>?v=<?= filemtime(__DIR__ . '/../../assets/js/recipe-admin.js') ?>"></script>
    <link rel="stylesheet" href="<?= url('assets/css/product-bundles.css') ?>?v=<?= filemtime(__DIR__.'/../../assets/css/product-bundles.css') ?>">
    <script defer src="<?= url('assets/js/product-bundles.js') ?>?v=<?= filemtime(__DIR__.'/../../assets/js/product-bundles.js') ?>"></script>
    <script src="<?= url('assets/js/product-branches.js') ?>?v=<?= filemtime(__DIR__ . '/../../assets/js/product-branches.js') ?>"></script>
    <script src="<?= url('assets/js/product-insights.js') ?>?v=<?= filemtime(__DIR__.'/../../assets/js/product-insights.js') ?>"></script>
    <link rel="stylesheet" href="<?= url('assets/css/inventory-responsive.css') ?>?v=<?= filemtime(__DIR__.'/../../assets/css/inventory-responsive.css') ?>">
    <script src="<?= url('assets/js/inventory-modal-cards.js') ?>?v=<?= filemtime(__DIR__.'/../../assets/js/inventory-modal-cards.js') ?>"></script>
    <script src="<?= url('assets/js/recipe-stock-adjustment.js') ?>?v=<?= filemtime(__DIR__ . '/../../assets/js/recipe-stock-adjustment.js') ?>"></script>
    <?php
    $content = ob_get_clean();
    require __DIR__ . '/../layouts/admin.php';
    ?>
