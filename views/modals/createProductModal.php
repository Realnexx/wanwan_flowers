<link rel="stylesheet" href="<?= url('assets/css/inventory-workspace.css') ?>?v=<?= filemtime(__DIR__.'/../../assets/css/inventory-workspace.css') ?>">
<?php
$is_admin = !empty($_SESSION['user']['role_name']) && in_array(strtolower($_SESSION['user']['role_name']), ['admin', 'super admin', 'manager', 'owner']);
?>
<style>
    /* ── Aurora Create Product Modal Styles ── */
    #createProductModal .modal-content {
        border-radius: 22px !important;
        border: 1px solid #e8edf5 !important;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.14), 0 4px 16px rgba(93, 135, 255, 0.06) !important;
        overflow: hidden;
        background: #ffffff;
    }

    #createProductModal .create-modal-accent-bar {
        height: 4px;
        width: 100%;
        background: linear-gradient(90deg, #5d87ff, #49beff);
    }

    #createProductModal .modal-header {
        display: flex !important;
        align-items: flex-start !important;
        justify-content: space-between !important;
        width: 100% !important;
    }

    #createProductModal .view-modal-btn-close {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #f1f5f9;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        transition: all 0.2s ease;
        margin-left: auto !important;
        flex-shrink: 0;
        cursor: pointer;
    }
    #createProductModal .view-modal-btn-close:hover {
        background: #e2e8f0;
        color: #1e293b;
        transform: scale(1.08);
    }

    /* Aurora Segmented Tabs */
    #productTabs {
        background: #f1f5f9;
        border-radius: 12px;
        padding: 5px;
        gap: 4px;
        border: none;
    }
    #productTabs .nav-link {
        border-radius: 9px !important;
        color: #64748b;
        font-weight: 700;
        font-size: 0.76rem;
        padding: 8px 14px;
        border: none !important;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    #productTabs .nav-link:hover {
        color: #1e293b;
        background: rgba(255, 255, 255, 0.6);
    }
    #productTabs .nav-link.active {
        background: #ffffff !important;
        color: #5d87ff !important;
        box-shadow: 0 2px 8px rgba(93, 135, 255, 0.12) !important;
        font-weight: 800;
    }

    /* Aurora Inputs */
    #createProductModal .form-control {
        border: 1.5px solid #edf2f7 !important;
        border-radius: 10px !important;
        background-color: #ffffff !important;
        font-size: 0.83rem;
        color: #1e293b;
        padding: 8px 12px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    #createProductModal .form-control:focus {
        border-color: #5d87ff !important;
        box-shadow: 0 0 0 3px rgba(93, 135, 255, 0.12) !important;
        outline: none;
    }

    /* Aurora Select Dropdown with Visible Chevron */
    #createProductModal .form-select {
        border: 1.5px solid #edf2f7 !important;
        border-radius: 10px !important;
        background-color: #ffffff !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.2' d='m3 6 5 5 5-5'/%3e%3c/svg%3e") !important;
        background-repeat: no-repeat !important;
        background-position: right 0.85rem center !important;
        background-size: 13px 9px !important;
        padding: 8px 2.2rem 8px 12px !important;
        font-size: 0.83rem;
        color: #1e293b;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background-image 0.2s ease;
    }
    #createProductModal .form-select:hover {
        border-color: #cbd5e1 !important;
    }
    #createProductModal .form-select:focus {
        border-color: #5d87ff !important;
        box-shadow: 0 0 0 3px rgba(93, 135, 255, 0.12) !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%235d87ff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.2' d='m3 6 5 5 5-5'/%3e%3c/svg%3e") !important;
        outline: none;
    }

    /* Spec Cards & Toggle Pills */
    .create-spec-card {
        border: 1px solid #edf2f7;
        border-radius: 14px;
        background: #ffffff;
        padding: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    }
    .create-toggle-pill {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 50px;
        padding: 6px 14px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .create-toggle-pill:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }
</style>

<div class="modal fade" id="createProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0">
            <!-- Top Gradient Accent Bar -->
            <div class="create-modal-accent-bar"></div>

            <form id="createProductForm" data-inline-validation novalidate action="?route=inventory/products" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="recipe_create_key" value="<?= bin2hex(random_bytes(16)) ?>">
                <input type="hidden" name="action" value="create">

                <!-- Header -->
                <div class="modal-header border-0 px-4 pt-4 pb-2 d-flex align-items-start justify-content-between w-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center justify-content-center"
                            style="width: 46px; height: 46px; border-radius: 14px; background: #eef2ff; color: #5d87ff;">
                            <span class="material-symbols-outlined" style="font-size: 24px;">add_circle</span>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h5 class="modal-title fw-800 text-dark mb-0" style="font-size: 1.15rem; letter-spacing: -0.3px;">Create New Product</h5>
                                <span class="badge rounded-pill" style="background: #eef2ff; color: #5d87ff; font-size: 0.68rem; font-weight: 700; padding: 4px 9px;">Catalog Creator</span>
                            </div>
                            <p class="text-muted mb-0" style="font-size: 0.78rem;">Add a new product to your inventory catalog</p>
                        </div>
                    </div>
                    <button type="button" class="view-modal-btn-close shadow-none ms-auto" data-bs-dismiss="modal" aria-label="Close" title="Close">
                        <span class="material-symbols-outlined" style="font-size: 18px;">close</span>
                    </button>
                </div>

                <div class="modal-body px-4 pt-2 pb-0">
                    <!-- Aurora Segmented Tab Navigation -->
                    <ul class="nav nav-tabs nav-pills border-0 mb-3" id="productTabs" role="tablist">
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link active w-100" data-bs-toggle="tab" data-bs-target="#general-tab" type="button">
                                <span class="material-symbols-outlined" style="font-size: 16px;">info</span> General Info
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100" data-bs-toggle="tab" data-bs-target="#pricing-tab" type="button">
                                <span class="material-symbols-outlined" style="font-size: 16px;">payments</span> Pricing & Stock
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100" data-bs-toggle="tab" data-bs-target="#variants-tab" type="button">
                                <span class="material-symbols-outlined" style="font-size: 16px;">account_tree</span> Variants
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100" data-bs-toggle="tab" data-bs-target="#attributes-tab" type="button">
                                <span class="material-symbols-outlined" style="font-size: 16px;">tune</span> Custom Attributes
                            </button>
                        </li>
                        <li class="nav-item flex-fill d-none" role="presentation" id="recipe_tab_item">
                            <button class="nav-link w-100" data-bs-toggle="tab" data-bs-target="#recipe-tab" type="button">
                                <span class="material-symbols-outlined" style="font-size: 16px;">restaurant</span> Recipe Formula
                            </button>
                        </li>
                    <li class="nav-item flex-fill bundle-tab-switch d-none" data-bundle-context="create"><button type="button" class="nav-link w-100" data-bs-toggle="tab" data-bs-target="#create-bundle-tab"><span class="material-symbols-outlined" aria-hidden="true">inventory_2</span> Package items</button></li></ul>

                    <div class="tab-content" id="productTabsContent"><div class="tab-pane fade bundle-editor" id="create-bundle-tab" data-context="create" role="tabpanel"><div class="bundle-editor-heading"><div><h6>Package contents</h6><p>One fixed package price. Included items consume their own stock or ingredients.</p></div><button type="button" class="btn btn-outline-primary btn-sm bundle-add-item"><span class="material-symbols-outlined inv-button-icon" aria-hidden="true">add</span>Add item</button></div><p class="bundle-editor-feedback" role="status"></p><div class="bundle-editor-rows"></div><div class="bundle-editor-summary" aria-live="polite"></div><p class="small text-muted">Whole item quantities only. No nested packages or variant parents. Preparation stations group items on the preparation ticket. Cost is estimated using available FIFO batches, falling back to recorded product costs if stock is missing.</p></div>
                        <!-- General Info Tab -->
                        <div class="tab-pane fade show active" id="general-tab" role="tabpanel">
                            <div class="row g-3">
                                <!-- Photo Upload Dropzone Box -->
                                <div class="col-md-4">
                                    <div class="image-upload-wrapper text-center">
                                        <div class="preview-container mb-2 overflow-hidden position-relative"
                                            style="aspect-ratio: 1/1; border-radius: 16px; border: 2px dashed #cbd5e1; background: #f8fafc; cursor: pointer; transition: all 0.2s;"
                                            onclick="document.getElementById('create_image_input').click()"
                                            onmouseover="this.style.borderColor='#5d87ff'; this.style.background='#eff6ff';"
                                            onmouseout="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';">
                                            <img id="create_img_preview_new" src="assets/img/default.png" class="w-100 h-100 object-fit-cover d-none"
                                                onerror="this.src='assets/img/default.png'">
                                            <div id="upload_placeholder_new" class="text-muted text-center d-flex flex-column align-items-center justify-content-center h-100 p-3">
                                                <div class="d-flex align-items-center justify-content-center rounded-circle mb-2"
                                                    style="width: 52px; height: 52px; background: #eff6ff; color: #5d87ff;">
                                                    <span class="material-symbols-outlined" style="font-size: 28px;">add_photo_alternate</span>
                                                </div>
                                                <span class="fw-800 text-dark" style="font-size: 0.78rem;">Upload Product Photo</span>
                                                <span class="text-muted mt-1" style="font-size: 0.68rem;">Click to browse image</span>
                                            </div>
                                            <div class="position-absolute bottom-0 start-0 end-0 text-center py-2"
                                                style="background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); font-size: 0.68rem; color: #fff; font-weight: 600; letter-spacing: 0.3px;">
                                                <span class="material-symbols-outlined" style="font-size: 13px; vertical-align: middle;">photo_camera</span> Recommended: 800×800 PNG/JPG
                                            </div>
                                        </div>
                                        <input type="file" id="create_image_input" name="image" class="d-none" accept="image/*">
                                        <div class="text-muted" style="font-size: 0.65rem;">Max file size: 5MB</div>
                                    </div>
                                </div>

                                <!-- Core Details -->
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">Product Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" placeholder="e.g. Classic Fiesta Halo-Halo" required>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">Category <span class="text-danger">*</span></label>
                                            <select name="category_id" class="form-select" required>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">Status</label>
                                            <select name="status" class="form-select">
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">SKU / Product Code</label>
                                            <input type="text" name="sku" class="form-control font-monospace" placeholder="Auto-generated on creation" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">Barcode (EAN / UPC)</label>
                                            <input type="text" name="barcode" class="form-control font-monospace" placeholder="Auto-generated on creation" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">Product Type</label>
                                        <select name="product_type" id="create_product_type" class="form-select">
                                            <option value="simple">Simple Product (Grocery/Retail)</option>
                                            <option value="recipe">Recipe / Made-to-order</option>
                                            <option value="variant">Product with Variants (Sizes/Colors)</option>
                                            <option value="service">Service / Labor</option>
                                            <option value="weighted">Weighted (Per KG/Gram)</option>
                                            <option value="ingredient">Raw Ingredient</option>
                                            <option value="combo">Combo / Bundle</option>
                                            <option value="digital">Digital / E-Load</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-0">
                                        <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">Description & Notes</label>
                                        <textarea name="description" class="form-control" rows="3" placeholder="Provide additional product details, notes, or highlights..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing & Inventory Tab -->
                        <div class="tab-pane fade" id="pricing-tab" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="create-spec-card h-100">
                                        <div class="d-flex align-items-center gap-2 mb-3">
                                            <div class="d-flex align-items-center justify-content-center rounded-2"
                                                style="width: 28px; height: 28px; background: #eef2ff; color: #5d87ff;">
                                                <span class="material-symbols-outlined" style="font-size: 16px;">sell</span>
                                            </div>
                                            <h6 class="fw-800 text-dark mb-0" style="font-size: 0.85rem;">Pricing Specification</h6>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px;">Selling Price</label>
                                                <div class="input-group">
                                                    <span class="input-group-text border-0" style="background: #f1f5f9; font-weight: 700; color: #5d87ff;">₱</span>
                                                    <input type="number" step="0.01" name="price" id="create_prod_price" class="form-control fw-800" style="color: #5d87ff; font-size: 0.95rem;" placeholder="0.00" required>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px;">Cost Price</label>
                                                <div class="input-group">
                                                    <span class="input-group-text border-0" style="background: #f1f5f9; font-weight: 700; color: #64748b;">₱</span>
                                                    <input type="number" step="0.01" name="cost_price" id="create_prod_cost" class="form-control fw-800" style="color: #1e293b; font-size: 0.95rem;" placeholder="0.00">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="create-spec-card h-100" id="inventory_controls">
                                        <div class="d-flex align-items-center gap-2 mb-3">
                                            <div class="d-flex align-items-center justify-content-center rounded-2"
                                                style="width: 28px; height: 28px; background: #ecfdf5; color: #059669;">
                                                <span class="material-symbols-outlined" style="font-size: 16px;">inventory_2</span>
                                            </div>
                                            <h6 class="fw-800 text-dark mb-0" style="font-size: 0.85rem;">Stock & Threshold Controls</h6>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-3">
                                                <label class="form-label fw-700 text-muted mb-1 text-center d-block" style="font-size: 0.62rem; text-transform: uppercase;">Batch No</label>
                                                <input type="text" name="batch_number" id="initial_batch_number" class="form-control text-center font-monospace" style="font-size: 0.78rem;" placeholder="Auto-gen">
                                            </div>
                                            <div class="col-3">
                                                <label class="form-label fw-700 text-muted mb-1 text-center d-block" style="font-size: 0.62rem; text-transform: uppercase;">Initial Stock</label>
                                                <input data-stock-summary type="number" name="stock" id="create_initial_stock" class="form-control text-center fw-800" style="font-size: 0.95rem;" value="0.00" step="0.01" min="0" aria-describedby="create_recipe_stock_help">
                                            </div>
                                            <div class="col-3">
                                                <label class="form-label fw-700 text-muted mb-1 text-center d-block" style="font-size: 0.62rem; text-transform: uppercase;">Low Alert</label>
                                                <input data-stock-summary type="number" name="low_stock_threshold" class="form-control text-center fw-800" style="font-size: 0.95rem; color: #d97706;" value="10.00" step="0.01" min="0">
                                            </div>
                                            <div class="col-3">
                                                <label class="form-label fw-700 text-muted mb-1 text-center d-block" style="font-size: 0.62rem; text-transform: uppercase;">Max Cap</label>
                                                <input data-stock-summary type="number" name="max_stock_threshold" class="form-control text-center fw-800" style="font-size: 0.95rem; color: #5d87ff;" value="100.00" step="0.01" min="0">
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <label class="create-toggle-pill m-0" for="track_stock_sw">
                                                <div class="form-check form-switch p-0 m-0 d-flex align-items-center">
                                                    <input class="form-check-input m-0 shadow-none cursor-pointer" type="checkbox" name="track_stock" checked id="track_stock_sw">
                                                </div>
                                                <span style="font-size: 0.72rem; font-weight: 700; color: #334155;">Track Stock</span>
                                            </label>
                                            <label class="create-toggle-pill m-0" for="sellable_sw">
                                                <div class="form-check form-switch p-0 m-0 d-flex align-items-center">
                                                    <input class="form-check-input m-0 shadow-none cursor-pointer" type="checkbox" name="is_sellable" checked id="sellable_sw">
                                                </div>
                                                <span style="font-size: 0.72rem; font-weight: 700; color: #334155;">Sellable</span>
                                            </label>
                                            <?php $flowerFieldPrefix = ''; require __DIR__ . '/../partials/flower_product_fields.php'; ?>
                                            <label class="d-flex align-items-center gap-2 p-2"><input type="checkbox" name="show_on_website" id="show_on_website" value="1"> Show on website</label><label class="create-toggle-pill m-0" for="discountable_sw">
                                                <div class="form-check form-switch p-0 m-0 d-flex align-items-center">
                                                    <input class="form-check-input m-0 shadow-none cursor-pointer" type="checkbox" name="is_discountable" checked id="discountable_sw">
                                                </div>
                                                <span style="font-size: 0.72rem; font-weight: 700; color: #334155;">Discountable</span>
                                            </label>
                                            <label class="create-toggle-pill m-0" for="ingredient_sw">
                                                <div class="form-check form-switch p-0 m-0 d-flex align-items-center">
                                                    <input class="form-check-input m-0 shadow-none cursor-pointer" type="checkbox" name="is_ingredient" id="ingredient_sw">
                                                </div>
                                                <span style="font-size: 0.72rem; font-weight: 700; color: #334155;">Ingredient</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">Inventory Behavior</label>
                                    <select name="inventory_type" class="form-select">
                                        <option value="stocked">Standard Stocked Item</option>
                                        <option value="non-stock">Non-Stock / Service</option>
                                        <option value="ingredient-based">Ingredient-deducting (Recipe)</option>
                                        <option value="unlimited">Unlimited / Digital</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">Measurement Unit</label>
                                    <select name="unit_type" id="create_unit_type" class="form-select">
                                        <option value="">Select a unit</option>
                                        <option value="pc">Piece (pc)</option>
                                        <option value="pcs">Pieces (pcs)</option>
                                        <option value="kg">Kilogram (kg)</option>
                                        <option value="g">Gram (g)</option>
                                        <option value="l">Liter (l)</option>
                                        <option value="ml">Milliliter (ml)</option>
                                        <option value="bot">Bottle (bot)</option>
                                        <option value="pck">Pack (pck)</option>
                                        <option value="bndl">Bundle (bndl)</option>
                                        <option value="sck">Sack (sck)</option>
                                        <option value="cse">Case (cse)</option>
                                        <option value="rl">Roll (rl)</option>
                                        <option value="dz">Dozen (dz)</option>
                                        <option value="m">Meter (m)</option>
                                        <option value="cm">Centimeter (cm)</option>
                                        <option value="in">Inch (in)</option>
                                        <option value="yd">Yard (yd)</option>
                                        <option value="set">Set</option>
                                        <option value="box">Box</option>
                                        <option value="pr">Pair (pr)</option>
                                        <option value="try">Tray (try)</option>
                                        <option value="gal">Gallon (gal)</option>
                                        <option value="tb">Tub (tb)</option>
                                        <option value="jr">Jar (jr)</option>
                                        <option value="cn">Can (cn)</option>
                                        <option value="can">Can</option>
                                        <option value="pl">Pail (pl)</option>
                                        <option value="cp">Cup (cp)</option>
                                        <option value="gls">Glass (gls)</option>
                                        <option value="srv">Serving (srv)</option>
                                        <option value="hr">Hour (hr)</option>
                                        <option value="day">Day (day)</option>
                                        <option value="tab">Tablet (tab)</option>
                                        <option value="cap">Capsule (cap)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">Assigned Branch</label>
                                    <?php $canPickProductBranches=User::is_product_branch_admin($_SESSION['user']['id']); ?>
                                    <?php if($canPickProductBranches): ?><div class="product-branch-picker"><?php foreach($branches as $branch): if(($branch['status']??'Active')!=='Active')continue; ?><label><input type="checkbox" name="branch_ids[]" value="<?= (int)$branch['id'] ?>" <?= (int)($_SESSION['user']['branch_id']??0)===(int)$branch['id']?'checked':'' ?>><span><?= htmlspecialchars($branch['name'],ENT_QUOTES) ?></span></label><?php endforeach; ?></div><input type="hidden" name="branch_id" value="<?= (int)($_SESSION['user']['branch_id']??0) ?>"><small class="text-muted d-block mt-2">Choose one or more branches. A separate product and the entered initial stock are created in each branch. Multiple branches use shared ingredients only.</small>
                                    <?php else: ?><input type="hidden" name="branch_id" value="<?= (int)($_SESSION['user']['branch_id']??0) ?>"><div class="form-control bg-light"><?php foreach($branches as $branch)if((int)$branch['id']===(int)($_SESSION['user']['branch_id']??0))echo htmlspecialchars($branch['name'],ENT_QUOTES); ?></div><small class="text-muted">Products are assigned to your branch.</small><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Variants Tab -->
                        <div class="tab-pane fade" id="variants-tab" role="tabpanel">
                            <div class="alert d-flex align-items-center gap-2 rounded-3 border-0 mb-3 py-2 px-3"
                                style="background: #eff6ff; color: #1e40af; font-size: 0.75rem; border: 1px solid #bfdbfe;">
                                <span class="material-symbols-outlined" style="font-size: 18px; color: #3b82f6;">lightbulb</span>
                                <div>Define variants (Sizes, Colors, Flavors, etc). Rows will be saved upon product creation.</div>
                            </div>
                            <div id="variants_config_area">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="material-symbols-outlined" style="font-size: 18px; color: #5d87ff;">account_tree</span>
                                        <h6 class="fw-800 mb-0 text-dark" style="font-size: 0.88rem;">Variant Matrix</h6>
                                    </div>
                                    <button type="button" class="btn btn-sm rounded-pill d-flex align-items-center gap-1 px-3 border" id="add_variant_btn_create"
                                        style="font-size: 0.72rem; font-weight: 700; background: #eef2ff; color: #5d87ff; border-color: #dbeafe !important;">
                                        <span class="material-symbols-outlined" style="font-size: 14px;">add</span> Add Row
                                    </button>
                                </div>
                                <div class="table-responsive rounded-3 border bg-white overflow-hidden">
                                    <table class="table table-hover align-middle mb-0" style="font-size: 0.78rem;">
                                        <thead class="text-muted" style="font-size: 0.65rem; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; background: #f8fafc;">
                                            <tr>
                                                <th class="ps-3 py-2">Variant Name</th>
                                                <th class="py-2">SKU</th>
                                                <th class="py-2">Price (₱)</th>
                                                <th class="py-2">Cost (₱)</th>
                                                <th class="py-2">Stock</th>
                                                <th class="py-2 text-end pe-3">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="variants_list_body_create">
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted" style="font-size: 0.75rem;">No variants added. Click "Add Row" to start.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Attributes Tab -->
                        <div class="tab-pane fade" id="attributes-tab" role="tabpanel">
                            <div class="alert d-flex align-items-center gap-2 rounded-3 border-0 mb-3 py-2 px-3"
                                style="background: #ecfdf5; color: #065f46; font-size: 0.75rem; border: 1px solid #a7f3d0;">
                                <span class="material-symbols-outlined" style="font-size: 18px; color: #10b981;">settings_suggest</span>
                                <div>Store custom business-specific metadata, nutritional info, or custom fields for this product.</div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #10b981;">tune</span>
                                    <h6 class="fw-800 mb-0 text-dark" style="font-size: 0.88rem;">Custom Fields Matrix</h6>
                                </div>
                            </div>
                            <div id="attributes_container_create" class="row g-2 mb-3">
                                <!-- Dynamic Attributes injected here -->
                            </div>
                            <div id="no_attributes_msg_create" class="text-center py-4 text-muted fs-8 rounded-3 border bg-white mb-3">
                                No custom attributes added yet. Add a custom field below.
                            </div>
                            <div class="pt-3 border-top d-flex justify-content-between gap-2" style="border-color: #f1f5f9 !important;">
                                <input type="text" id="new_attr_name_create" class="form-control flex-grow-1" placeholder="Custom Field Name (e.g. Brand, Calories, Origin, Material)">
                                <button type="button" class="btn btn-modal-primary" id="add_custom_attr_btn_create" style="white-space: nowrap;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">add</span> Add Field
                                </button>
                            </div>
                        </div>

                        <!-- Recipe Tab -->
                        <div class="tab-pane fade" id="recipe-tab" role="tabpanel">
                            <?php require_once __DIR__ . '/../../models/RecipeLibrary.php'; $saved_recipes = RecipeInventory::ready() ? RecipeLibrary::available() : []; ?>
                            <div class="border rounded p-3 mb-3">
                                <label class="form-label fw-bold" for="create_recipe_source">Load a saved recipe</label>
                                <select id="create_recipe_source" class="form-select">
                                    <option value="">Custom recipe — configure ingredients below</option>
                                    <?php foreach ($saved_recipes as $saved_recipe): ?>
                                    <option value="<?= (int)$saved_recipe['id'] ?>"><?= htmlspecialchars($saved_recipe['name'] . ' · ' . ($saved_recipe['branch_name'] ?: 'Shared'), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="small text-muted mt-2 mb-1">Loads a copy of the ingredients for this product. Later changes to the saved recipe do not change products already created.</p>
                                <a href="<?= url('?route=inventory/recipes') ?>" target="_blank" rel="noopener"><span class="material-symbols-outlined inv-button-icon" aria-hidden="true">menu_book</span>Open Recipe Management</a>
                                <p id="create_recipe_source_feedback" class="small mt-2 mb-0" role="status" aria-live="polite"></p>
                            </div>
                            <div class="border rounded p-3 mb-3">
                                <label class="form-label fw-bold" for="create_recipe_mode">When should ingredients be consumed?</label>
                                <select class="form-select" name="recipe_mode" id="create_recipe_mode">
                                    <option value="prepared_batch">Finished stock — consume ingredients when stock is created</option>
                                    <option value="made_to_order">Made to order — consume ingredients at checkout</option>
                                </select>
                                <p class="small text-muted mt-2 mb-1">Configure ingredients for one unit sold, including all items in an offer or bundle. Adding or editing ingredient rows does not change inventory.</p>
                                <p id="create_recipe_stock_help" class="small text-muted mb-1">Enter Initial Stock in Pricing &amp; Stock. Saving deducts this recipe multiplied by that stock and creates finished units. Leave stock at 0 to save only the recipe. Selling finished stock does not deduct these ingredients again.</p>
                                <div class="recipe-cost-preview small fw-bold" aria-live="polite">Select ingredients to estimate cost per stock unit.</div>
                                <?php include __DIR__ . '/../partials/recipe_quantity_preview.php'; ?>
                                
                            </div>

                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="fw-800 text-dark mb-1" style="font-size: 0.88rem;">Bill of Materials & Formula</h6>
                                    <p class="text-muted mb-0" style="font-size: 0.72rem;">Ingredient requirements update automatically from the recipe and initial stock.</p>
                                </div>
                                <button type="button" class="btn btn-sm rounded-pill d-flex align-items-center gap-1 px-3 border" id="add_ingredient_row_create"
                                    style="font-size: 0.72rem; font-weight: 700; background: #eef2ff; color: #5d87ff; border-color: #dbeafe !important;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">add</span> Add Ingredient
                                </button>
                            </div>
                            <div class="rounded-3 p-3 border mb-3" style="background: #f8fafc; min-height: 180px; max-height: 360px; overflow-y: auto;">
                                <div id="ingredients_list_create" class="d-flex flex-column gap-2">
                                    <div id="no_ingredients_msg_create" class="text-center py-4">
                                        <span class="material-symbols-outlined text-muted opacity-50 d-block mb-1" style="font-size: 32px;">kitchen</span>
                                        <p class="text-muted mb-0" style="font-size: 0.75rem;">No ingredients selected. Create Raw Ingredient products first, then select them here.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="pt-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="material-symbols-outlined" style="font-size: 18px; color: #d97706;">tune</span>
                                        <h6 class="fw-800 text-dark mb-0" style="font-size: 0.85rem;">Custom Modifiers & Add-ons</h6>
                                    </div>
                                    <button type="button" id="add_modifier_row_create" class="btn btn-sm rounded-pill d-flex align-items-center gap-1 px-3 border-0"
                                        style="font-size: 0.72rem; font-weight: 700; background: #fef3c7; color: #b45309;">
                                        <span class="material-symbols-outlined" style="font-size: 14px;">add</span> Add Modifier
                                    </button>
                                </div>
                                <p class="text-muted mb-2" style="font-size: 0.7rem;">Extra charges applied when selected during checkout.</p>
                                <div class="table-responsive rounded-3 border bg-white overflow-hidden">
                                    <table class="table table-hover align-middle mb-0" style="font-size: 0.78rem;">
                                        <thead style="font-size: 0.65rem; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; background: #f8fafc;">
                                            <tr>
                                                <th class="ps-3 py-2 text-muted" style="min-width:180px;">Modifier Name</th>
                                                <th class="py-2 text-muted">Deducts Stock</th>
                                                <th class="py-2 text-muted" style="width:130px;">Extra Price (₱)</th>
                                                <th class="py-2 text-end pe-3 text-muted" style="width:60px;">Del</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modifiers_list_create">
                                            <tr id="no_modifiers_msg_create">
                                                <td colspan="4" class="text-center py-3 text-muted" style="font-size: 0.75rem;">No modifiers yet.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="modal-footer border-0 px-4 pb-4 pt-3 d-flex justify-content-end align-items-center gap-2" style="border-top: 1px solid #f1f5f9 !important;">
                    <button type="button" class="btn btn-modal-secondary" data-bs-dismiss="modal"><span class="material-symbols-outlined inv-button-icon" aria-hidden="true">close</span>Discard</button>
                    <button type="submit" class="btn btn-modal-primary">
                        <span class="material-symbols-outlined" style="font-size: 16px;">add_task</span> Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
