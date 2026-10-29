<style>
    /* ── Aurora Edit Product Modal Styles ── */
    #editProductModal .modal-content {
        border-radius: 22px !important;
        border: 1px solid #e8edf5 !important;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.14), 0 4px 16px rgba(93, 135, 255, 0.06) !important;
        overflow: hidden;
        background: #ffffff;
    }

    #editProductModal .edit-modal-accent-bar {
        height: 4px;
        width: 100%;
        background: linear-gradient(90deg, #5d87ff, #49beff);
    }

    #editProductModal .modal-header {
        display: flex !important;
        align-items: flex-start !important;
        justify-content: space-between !important;
        width: 100% !important;
    }

    #editProductModal .view-modal-btn-close {
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
    #editProductModal .view-modal-btn-close:hover {
        background: #e2e8f0;
        color: #1e293b;
        transform: scale(1.08);
    }

    /* Aurora Segmented Tabs */
    #editProductTabs {
        background: #f1f5f9;
        border-radius: 12px;
        padding: 5px;
        gap: 4px;
        border: none;
    }
    #editProductTabs .nav-link {
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
    #editProductTabs .nav-link:hover {
        color: #1e293b;
        background: rgba(255, 255, 255, 0.6);
    }
    #editProductTabs .nav-link.active {
        background: #ffffff !important;
        color: #5d87ff !important;
        box-shadow: 0 2px 8px rgba(93, 135, 255, 0.12) !important;
        font-weight: 800;
    }

    /* Aurora Inputs */
    #editProductModal .form-control {
        border: 1.5px solid #edf2f7 !important;
        border-radius: 10px !important;
        background-color: #ffffff !important;
        font-size: 0.83rem;
        color: #1e293b;
        padding: 8px 12px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    #editProductModal .form-control:focus {
        border-color: #5d87ff !important;
        box-shadow: 0 0 0 3px rgba(93, 135, 255, 0.12) !important;
        outline: none;
    }

    /* Aurora Select Dropdown with Visible Chevron */
    #editProductModal .form-select {
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
    #editProductModal .form-select:hover {
        border-color: #cbd5e1 !important;
    }
    #editProductModal .form-select:focus {
        border-color: #5d87ff !important;
        box-shadow: 0 0 0 3px rgba(93, 135, 255, 0.12) !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%235d87ff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.2' d='m3 6 5 5 5-5'/%3e%3c/svg%3e") !important;
        outline: none;
    }

    /* Spec Cards & Toggle Pills */
    .edit-spec-card {
        border: 1px solid #edf2f7;
        border-radius: 14px;
        background: #ffffff;
        padding: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    }
    .edit-toggle-pill {
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
    .edit-toggle-pill:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    /* Modal Action Buttons */
    .btn-modal-secondary {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #64748b;
        font-weight: 700;
        font-size: 0.82rem;
        padding: 8px 20px;
        border-radius: 50px;
        transition: all 0.2s ease;
    }
    .btn-modal-secondary:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #1e293b;
        transform: translateY(-1px);
    }
    .btn-modal-primary {
        background: linear-gradient(135deg, #5d87ff 0%, #436fe8 100%);
        border: none;
        color: #ffffff;
        font-weight: 700;
        font-size: 0.82rem;
        padding: 8px 24px;
        border-radius: 50px;
        box-shadow: 0 4px 14px rgba(93, 135, 255, 0.28);
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-modal-primary:hover {
        background: linear-gradient(135deg, #4d77ef 0%, #365ec8 100%);
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(93, 135, 255, 0.38);
    }
    .btn-modal-primary:active, .btn-modal-secondary:active {
        transform: scale(0.97);
    }
</style>

<div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0">
            <!-- Top Gradient Accent -->
            <div class="edit-modal-accent-bar"></div>

            <form id="editProductForm" data-inline-validation novalidate action="<?= url('?route=inventory/products') ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="product_id" id="edit_product_id">

                <!-- Header -->
                <div class="modal-header border-0 px-4 pt-4 pb-2 d-flex align-items-start justify-content-between w-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center justify-content-center"
                            style="width: 46px; height: 46px; border-radius: 14px; background: #eef2ff; color: #5d87ff;">
                            <span class="material-symbols-outlined" style="font-size: 24px;">edit_square</span>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h5 class="modal-title fw-800 text-dark mb-0" style="font-size: 1.15rem; letter-spacing: -0.3px;">Edit Product Details</h5>
                                <span class="badge rounded-pill" style="background: #eef2ff; color: #5d87ff; font-size: 0.68rem; font-weight: 700; padding: 4px 9px;">Catalog Editor</span>
                            </div>
                            <p class="text-muted mb-0" style="font-size: 0.78rem;">Modifying: <span id="edit_display_name" class="fw-800 text-dark"></span></p>
                        </div>
                    </div>
                    <button type="button" class="view-modal-btn-close shadow-none ms-auto" data-bs-dismiss="modal" aria-label="Close" title="Close">
                        <span class="material-symbols-outlined" style="font-size: 18px;">close</span>
                    </button>
                </div>

                <div class="modal-body px-4 pt-2 pb-0">
                    <!-- Aurora Segmented Tab Navigation -->
                    <ul class="nav nav-tabs nav-pills border-0 mb-3" id="editProductTabs" role="tablist">
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link active w-100" data-bs-toggle="tab" data-bs-target="#edit-general-tab" type="button">
                                <span class="material-symbols-outlined" style="font-size: 16px;">info</span> General Info
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100" data-bs-toggle="tab" data-bs-target="#edit-pricing-tab" type="button">
                                <span class="material-symbols-outlined" style="font-size: 16px;">payments</span> Pricing & Stock
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100" data-bs-toggle="tab" data-bs-target="#edit-variants-tab" type="button">
                                <span class="material-symbols-outlined" style="font-size: 16px;">account_tree</span> Variants
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100" data-bs-toggle="tab" data-bs-target="#edit-attributes-tab" type="button">
                                <span class="material-symbols-outlined" style="font-size: 16px;">tune</span> Custom Attributes
                            </button>
                        </li>
                        <li class="nav-item flex-fill d-none" role="presentation" id="edit_recipe_tab_item">
                            <button class="nav-link w-100" data-bs-toggle="tab" data-bs-target="#edit-recipe-tab" type="button">
                                <span class="material-symbols-outlined" style="font-size: 16px;">restaurant</span> Recipe Formula
                            </button>
                        </li>
                    <li class="nav-item flex-fill bundle-tab-switch d-none" data-bundle-context="edit"><button type="button" class="nav-link w-100" data-bs-toggle="tab" data-bs-target="#edit-bundle-tab"><span class="material-symbols-outlined" aria-hidden="true">inventory_2</span> Package items</button></li></ul>

                    <div class="tab-content" id="editProductTabsContent"><div class="tab-pane fade bundle-editor" id="edit-bundle-tab" data-context="edit" role="tabpanel"><div class="bundle-editor-heading"><div><h6>Package contents</h6><p>One fixed package price. Included items consume their own stock or ingredients.</p></div><button type="button" class="btn btn-outline-primary btn-sm bundle-add-item"><span class="material-symbols-outlined inv-button-icon" aria-hidden="true">add</span>Add item</button></div><p class="bundle-editor-feedback" role="status"></p><div class="bundle-editor-rows"></div><div class="bundle-editor-summary" aria-live="polite"></div><p class="small text-muted">Whole item quantities only. No nested packages or variant parents. Preparation stations group items on the preparation ticket. Cost is estimated using available FIFO batches, falling back to recorded product costs if stock is missing.</p></div>
                        <!-- General Info Tab -->
                        <div class="tab-pane fade show active" id="edit-general-tab" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="image-upload-wrapper text-center">
                                        <div class="preview-container mb-2 overflow-hidden position-relative"
                                            style="aspect-ratio: 1/1; border-radius: 16px; border: 2px dashed #cbd5e1; background: #f8fafc; cursor: pointer; transition: all 0.2s;"
                                            onclick="document.getElementById('edit_image_input').click()"
                                            onmouseover="this.style.borderColor='#5d87ff'; this.style.background='#eff6ff';"
                                            onmouseout="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';">
                                            <img id="edit_img_preview" src="assets/img/default.png" class="w-100 h-100 object-fit-cover"
                                                onerror="this.src='assets/img/default.png'">
                                            <div class="position-absolute bottom-0 start-0 end-0 text-center py-2"
                                                style="background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); font-size: 0.68rem; color: #fff; font-weight: 600; letter-spacing: 0.3px;">
                                                <span class="material-symbols-outlined" style="font-size: 13px; vertical-align: middle;">photo_camera</span> Click to change photo
                                            </div>
                                        </div>
                                        <input type="file" id="edit_image_input" name="image" class="d-none" accept="image/*">
                                        <div class="text-muted" style="font-size: 0.65rem;">Recommended: 800×800 PNG or JPG</div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">Product Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="edit_prod_name" class="form-control" placeholder="e.g. Classic Fiesta Halo-Halo" required>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">Category <span class="text-danger">*</span></label>
                                            <select name="category_id" id="edit_prod_cat" class="form-select" required>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">Status</label>
                                            <select name="status" id="edit_status" class="form-select">
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">SKU / Product Code</label>
                                            <input type="text" name="sku" id="edit_sku" class="form-control font-monospace" placeholder="e.g. SKX-2600004">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">Barcode (EAN / UPC)</label>
                                            <input type="text" name="barcode" id="edit_barcode" class="form-control font-monospace" placeholder="e.g. 127249720204">
                                        </div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">Product Type</label>
                                        <select name="product_type" id="edit_product_type" class="form-select">
                                            <option value="simple">Simple Product (Grocery/Retail)</option>
                                            <option value="recipe">Recipe / Made-to-order</option>
                                            <option value="variant">Product with Variants</option>
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
                                        <textarea name="description" id="edit_prod_desc" class="form-control" rows="3" placeholder="Provide additional product details, notes, or highlights..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing & Inventory Tab -->
                        <div class="tab-pane fade" id="edit-pricing-tab" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="edit-spec-card h-100">
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
                                                    <input type="number" step="0.01" name="price" id="edit_prod_price" class="form-control fw-800" style="color: #5d87ff; font-size: 0.95rem;" required>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px;">Cost Price</label>
                                                <div class="input-group">
                                                    <span class="input-group-text border-0" style="background: #f1f5f9; font-weight: 700; color: #64748b;">₱</span>
                                                    <input type="number" step="0.01" name="cost_price" id="edit_prod_cost" class="form-control fw-800" style="color: #1e293b; font-size: 0.95rem;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="edit-spec-card h-100">
                                        <div class="d-flex align-items-center gap-2 mb-3">
                                            <div class="d-flex align-items-center justify-content-center rounded-2"
                                                style="width: 28px; height: 28px; background: #ecfdf5; color: #059669;">
                                                <span class="material-symbols-outlined" style="font-size: 16px;">inventory_2</span>
                                            </div>
                                            <h6 class="fw-800 text-dark mb-0" style="font-size: 0.85rem;">Stock & Threshold Controls</h6>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-4">
                                                <label class="form-label fw-700 text-muted mb-1 text-center d-block" style="font-size: 0.65rem; text-transform: uppercase;">Stock</label>
                                                <input data-stock-summary type="number" name="stock" id="edit_prod_stock" readonly title="Use stock adjustments to change stock" class="form-control text-center fw-800" style="font-size: 0.95rem;" step="0.01" min="0">
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label fw-700 text-muted mb-1 text-center d-block" style="font-size: 0.65rem; text-transform: uppercase;">Low Alert</label>
                                                <input data-stock-summary type="number" name="low_stock_threshold" id="edit_prod_thresh" class="form-control text-center fw-800" style="font-size: 0.95rem; color: #d97706;" step="0.01" min="0">
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label fw-700 text-muted mb-1 text-center d-block" style="font-size: 0.65rem; text-transform: uppercase;">Max Cap</label>
                                                <input data-stock-summary type="number" name="max_stock_threshold" id="edit_prod_max_thresh" class="form-control text-center fw-800" style="font-size: 0.95rem; color: #5d87ff;" step="0.01" min="0">
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <label class="edit-toggle-pill m-0" for="edit_track_stock">
                                                <div class="form-check form-switch p-0 m-0 d-flex align-items-center">
                                                    <input class="form-check-input m-0 shadow-none cursor-pointer" type="checkbox" name="track_stock" id="edit_track_stock">
                                                </div>
                                                <span style="font-size: 0.72rem; font-weight: 700; color: #334155;">Track Stock</span>
                                            </label>
                                            <label class="edit-toggle-pill m-0" for="edit_is_sellable">
                                                <div class="form-check form-switch p-0 m-0 d-flex align-items-center">
                                                    <input class="form-check-input m-0 shadow-none cursor-pointer" type="checkbox" name="is_sellable" id="edit_is_sellable">
                                                </div>
                                                <span style="font-size: 0.72rem; font-weight: 700; color: #334155;">Sellable</span>
                                            </label>
                                            <?php $flowerFieldPrefix = 'edit_'; require __DIR__ . '/../partials/flower_product_fields.php'; ?>
                                            <label class="d-flex align-items-center gap-2 p-2"><input type="checkbox" name="show_on_website" id="edit_show_on_website" value="1"> Show on website</label><label class="edit-toggle-pill m-0" for="edit_is_discountable">
                                                <div class="form-check form-switch p-0 m-0 d-flex align-items-center">
                                                    <input class="form-check-input m-0 shadow-none cursor-pointer" type="checkbox" name="is_discountable" id="edit_is_discountable">
                                                </div>
                                                <span style="font-size: 0.72rem; font-weight: 700; color: #334155;">Discountable</span>
                                            </label>
                                            <label class="edit-toggle-pill m-0" for="edit_is_ingredient">
                                                <div class="form-check form-switch p-0 m-0 d-flex align-items-center">
                                                    <input class="form-check-input m-0 shadow-none cursor-pointer" type="checkbox" name="is_ingredient" id="edit_is_ingredient">
                                                </div>
                                                <span style="font-size: 0.72rem; font-weight: 700; color: #334155;">Ingredient</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">Inventory Behavior</label>
                                    <select name="inventory_type" id="edit_inventory_type" class="form-select">
                                        <option value="stocked">Standard Stocked Item</option>
                                        <option value="non-stock">Non-Stock / Service</option>
                                        <option value="ingredient-based">Ingredient-deducting (Recipe)</option>
                                        <option value="unlimited">Unlimited / Digital</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">Measurement Unit</label>
                                    <select name="unit_type" id="edit_unit_type" class="form-select">
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
                                <?php if ($is_admin): ?>
                                    <div class="col-md-4">
                                        <label class="form-label fw-700 text-muted mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">Assigned Branch</label>
                                        <select id="edit_prod_branch" <?= User::can_change_product_branch($_SESSION['user']['id'])?'name="product_branch_id"':'disabled' ?> class="form-select">
                                            <option value="">Global (All Branches)</option>
                                            <?php foreach ($branches as $branch): ?>
                                                <option value="<?= $branch['id'] ?>"><?= htmlspecialchars($branch['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Only Admin and Superadmin can change this assignment. Products with stock history must use the audited branch transfer workflow.</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Variants Tab -->
                        <div class="tab-pane fade" id="edit-variants-tab" role="tabpanel">
                            <div class="alert d-flex align-items-center gap-2 rounded-3 border-0 mb-3 py-2 px-3"
                                style="background: #eff6ff; color: #1e40af; font-size: 0.75rem; border: 1px solid #bfdbfe;">
                                <span class="material-symbols-outlined" style="font-size: 18px; color: #3b82f6;">lightbulb</span>
                                <div>Define variants (Size, Flavor, etc). Variant records are <strong>saved separately</strong> via the green button below.</div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #5d87ff;">account_tree</span>
                                    <h6 class="fw-800 mb-0 text-dark" style="font-size: 0.88rem;">Variant Matrix</h6>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm rounded-pill d-flex align-items-center gap-1 px-3 border" id="add_variant_btn_edit"
                                        style="font-size: 0.72rem; font-weight: 700; background: #eef2ff; color: #5d87ff; border-color: #dbeafe !important;">
                                        <span class="material-symbols-outlined" style="font-size: 14px;">add</span> Add Row
                                    </button>
                                    <button type="button" class="btn btn-sm rounded-pill d-flex align-items-center gap-1 px-3 border-0 fw-800" id="save_variants_btn"
                                        style="font-size: 0.72rem; background: #059669; color: #fff; box-shadow: 0 2px 8px rgba(5,150,105,0.25);">
                                        <span class="material-symbols-outlined" style="font-size: 14px;">save</span> Save Variants
                                    </button>
                                </div>
                            </div>
                            <div id="variants_loading" class="text-center py-3 d-none">
                                <div class="spinner-border spinner-border-sm text-primary"></div>
                                <span class="small text-muted ms-2">Loading variants...</span>
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
                                            <th class="py-2 text-end pe-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="variants_list_body_edit">
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted" style="font-size: 0.75rem;">No variants found. Add one above.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Attributes Tab -->
                        <div class="tab-pane fade" id="edit-attributes-tab" role="tabpanel">
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
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm rounded-pill d-flex align-items-center gap-1 px-3 border-0 fw-800" id="save_attributes_btn"
                                        style="font-size: 0.72rem; background: #059669; color: #fff; box-shadow: 0 2px 8px rgba(5,150,105,0.25);">
                                        <span class="material-symbols-outlined" style="font-size: 14px;">save</span> Save Attributes
                                    </button>
                                </div>
                            </div>
                            <div id="attributes_loading_edit" class="text-center py-3 d-none">
                                <div class="spinner-border spinner-border-sm text-success"></div>
                                <span class="small text-muted ms-2">Loading attributes...</span>
                            </div>
                            <div id="attributes_container_edit" class="row g-2 mb-3">
                                <!-- Dynamic Attributes injected here -->
                            </div>
                            <div id="no_attributes_msg_edit" class="text-center py-4 text-muted fs-8 d-none rounded-3 border bg-white mb-3">
                                No custom attributes added yet. Add a custom field below.
                            </div>
                            <div class="pt-3 border-top d-flex justify-content-between gap-2" style="border-color: #f1f5f9 !important;">
                                <input type="text" id="new_attr_name_edit" class="form-control flex-grow-1" placeholder="Custom Field Name (e.g. Brand, Calories, Origin, Material)">
                                <button type="button" class="btn btn-modal-primary" id="add_custom_attr_btn_edit" style="white-space: nowrap;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">add</span> Add Field
                                </button>
                            </div>
                        </div>

                        <!-- Recipe Tab -->
                        <div class="tab-pane fade" id="edit-recipe-tab" role="tabpanel">
                            <div class="border rounded p-3 mb-3">
                                <label class="form-label fw-bold" for="edit_recipe_mode">When should ingredients be consumed?</label>
                                <select class="form-select" name="recipe_mode" id="edit_recipe_mode"><option value="legacy">Existing recipe — reconcile stock before selling</option>
                                    <option value="made_to_order">Made to order — consume ingredients at checkout</option>
                                    <option value="prepared_batch">Prepared batch — consume ingredients when a batch is prepared</option>
                                </select>
                                <p class="small text-muted mt-2 mb-1">Recipe quantities are for one stock unit, including everything in an offer or bundle. Saving recipe changes does not change existing inventory. Use Prepare batch to create additional finished stock.</p>
                                <div class="recipe-cost-preview small fw-bold" aria-live="polite">Select ingredients to estimate cost per stock unit.</div>
                                <?php include __DIR__ . '/../partials/recipe_quantity_preview.php'; ?>
                                <label class="small d-block my-2"><input type="checkbox" name="recipe_reconciled" value="1" id="recipe_reconciled"> I reviewed existing ingredient and finished stock before changing this mode.</label>
                                <?php if (User::has_permission($_SESSION['user']['id'], 'prepare_recipe_stock')): ?>
                                <div class="d-flex gap-2 align-items-end mt-3">
                                    <label class="small">Batch quantity<input type="number" class="form-control" id="prepare_recipe_quantity" value="1" min="1" step="1"></label>
                                    <button type="button" class="btn btn-outline-primary" id="prepare_recipe_batch"><span class="material-symbols-outlined inv-button-icon" aria-hidden="true">inventory_2</span>Prepare batch</button>
                                </div>
                                <p class="small text-muted">Save the prepared-batch mode first. Preparing consumes ingredients and creates finished stock; checkout will not consume them again.</p>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="fw-800 text-dark mb-1" style="font-size: 0.88rem;">Bill of Materials & Formula</h6>
                                    <p class="text-muted mb-0" style="font-size: 0.72rem;">Changes to recipe components are <strong>saved separately</strong> via the button below.</p>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm rounded-pill d-flex align-items-center gap-1 px-3 border" id="add_ingredient_row"
                                        style="font-size: 0.72rem; font-weight: 700; background: #eef2ff; color: #5d87ff; border-color: #dbeafe !important;">
                                        <span class="material-symbols-outlined" style="font-size: 14px;">add</span> Add Ingredient
                                    </button>
                                    <button type="button" class="btn btn-sm rounded-pill d-flex align-items-center gap-1 px-3 border-0 fw-800" id="save_recipe_btn"
                                        style="font-size: 0.72rem; background: #059669; color: #fff; box-shadow: 0 2px 8px rgba(5,150,105,0.25);">
                                        <span class="material-symbols-outlined" style="font-size: 14px;">save</span> Save Recipe
                                    </button>
                                </div>
                            </div>
                            <div class="rounded-3 p-3 border mb-3" style="background: #f8fafc; min-height: 180px; max-height: 360px; overflow-y: auto;">
                                <div id="ingredients_list" class="d-flex flex-column gap-2">
                                    <!-- Ingredients dynamically loaded -->
                                </div>
                                <div id="no_ingredients_msg" class="text-center py-4 d-none">
                                    <span class="material-symbols-outlined text-muted opacity-50 d-block mb-1" style="font-size: 32px;">kitchen</span>
                                    <p class="text-muted mb-0" style="font-size: 0.75rem;">No ingredients added yet.</p>
                                </div>
                            </div>
                            <div id="recipe_availability_alert" class="mb-3 d-none">
                                <div class="d-flex align-items-start gap-2 rounded-3 py-2 px-3" style="background: #fef2f2; color: #991b1b; font-size: 0.72rem; border: 1px solid #fecaca;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">warning</span>
                                    <div>
                                        <div class="fw-800">Ingredient Shortage Detected!</div>
                                        <div id="shortage_details" style="font-size: 0.68rem;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="pt-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="material-symbols-outlined" style="font-size: 18px; color: #d97706;">tune</span>
                                        <h6 class="fw-800 text-dark mb-0" style="font-size: 0.85rem;">Custom Modifiers & Add-ons</h6>
                                    </div>
                                    <button type="button" id="add_modifier_row_edit" class="btn btn-sm rounded-pill d-flex align-items-center gap-1 px-3 border-0"
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
                                        <tbody id="modifiers_list_edit">
                                            <tr id="no_modifiers_msg_edit">
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
                    <button type="button" class="btn btn-modal-secondary" data-bs-dismiss="modal"><span class="material-symbols-outlined inv-button-icon" aria-hidden="true">close</span>Cancel</button>
                    <button type="submit" class="btn btn-modal-primary">
                        <span class="material-symbols-outlined" style="font-size: 16px;">save</span> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
