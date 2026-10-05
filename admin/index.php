<?php
require_once __DIR__ . '/auth.php';
require_admin();

$store = get_product_data();
$admin_cfg = get_admin_config();

$success_msg = '';
$error_msg = '';

$hoodies = $store['hoodies'] ?? [];
$categories = $store['categories'] ?? ['Oversized Pullovers', 'Zip-Up Hoodies', 'Vintage Washes'];

// Determine currently active hoodie being edited (if any)
$edit_id = $_GET['edit'] ?? '';
$editing_hoodie = null;
if (!empty($edit_id)) {
    foreach ($hoodies as $h) {
        if (($h['id'] ?? '') === $edit_id) {
            $editing_hoodie = $h;
            break;
        }
    }
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($token)) {
        $error_msg = 'Security token invalid or expired. Please refresh and try again.';
    } else {
        // 1. SAVE HOODIE (ADD OR UPDATE)
        if ($action === 'save_hoodie') {
            $h_id = trim($_POST['hoodie_id'] ?? '');
            if (empty($h_id)) {
                $h_id = 'hoodie_' . time();
            }

            // Determine type/category
            $custom_type = trim($_POST['custom_type'] ?? '');
            $selected_type = trim($_POST['type'] ?? '');
            $final_type = !empty($custom_type) ? $custom_type : (!empty($selected_type) ? $selected_type : 'Oversized Pullover');

            // If a new custom type was entered, auto-add to categories list
            if (!empty($custom_type) && !in_array($custom_type, $categories)) {
                $categories[] = $custom_type;
                $store['categories'] = array_values(array_unique($categories));
            }

            // Parse colors
            $colors = [];
            if (!empty($_POST['colors']) && is_array($_POST['colors'])) {
                foreach ($_POST['colors'] as $c) {
                    $c_name = trim($c['name'] ?? '');
                    if (!empty($c_name)) {
                        $colors[] = [
                            'name' => $c_name,
                            'hex' => trim($c['hex'] ?? '#1e1e1e'),
                            'image' => trim($c['image'] ?? '')
                        ];
                    }
                }
            }
            if (empty($colors)) {
                $colors = [
                    ['name' => 'Washed Onyx', 'hex' => '#1e1e1e', 'image' => 'assets/images/hoodie-black.jpg']
                ];
            }

            // Parse sizes
            $sizes = [];
            if (!empty($_POST['sizes']) && is_array($_POST['sizes'])) {
                foreach ($_POST['sizes'] as $s) {
                    $s_label = trim($s['label'] ?? '');
                    if (!empty($s_label)) {
                        $sizes[] = [
                            'label' => $s_label,
                            'chest' => trim($s['chest'] ?? ''),
                            'length' => trim($s['length'] ?? ''),
                            'available' => !empty($s['available'])
                        ];
                    }
                }
            }
            if (empty($sizes)) {
                $sizes = [
                    ['label' => 'S', 'chest' => '48"', 'length' => '27"', 'available' => true],
                    ['label' => 'M', 'chest' => '50"', 'length' => '28"', 'available' => true],
                    ['label' => 'L', 'chest' => '52"', 'length' => '29"', 'available' => true],
                    ['label' => 'XL', 'chest' => '54"', 'length' => '30"', 'available' => true]
                ];
            }

            // Image Upload Handling
            $primary_image = trim($_POST['existing_image'] ?? 'assets/images/hoodie-black.jpg');
            if (!empty($_FILES['hoodie_image']['name'])) {
                $upload_res = handle_image_upload($_FILES['hoodie_image']);
                if ($upload_res['success']) {
                    $primary_image = $upload_res['path'];
                } else {
                    $error_msg = 'Image upload warning: ' . $upload_res['error'];
                }
            }

            $hoodie_data = [
                'id' => $h_id,
                'title' => trim($_POST['title'] ?? 'Heavyweight Hoodie'),
                'type' => $final_type,
                'badge' => trim($_POST['badge'] ?? ''),
                'sale_price' => (float)($_POST['sale_price'] ?? 0),
                'original_price' => (float)($_POST['original_price'] ?? 0),
                'stock_status' => in_array($_POST['stock_status'] ?? '', ['in_stock', 'low_stock', 'sold_out']) ? $_POST['stock_status'] : 'in_stock',
                'stock_text' => trim($_POST['stock_text'] ?? ''),
                'rating' => (float)($_POST['rating'] ?? 4.95),
                'review_count' => (int)($_POST['review_count'] ?? 50),
                'image' => $primary_image,
                'secondary_image' => trim($_POST['secondary_image'] ?? $primary_image),
                'short_description' => trim($_POST['short_description'] ?? ''),
                'fabric' => trim($_POST['fabric'] ?? '500 GSM Heavyweight French Terry Cotton'),
                'fit' => trim($_POST['fit'] ?? 'Boxy drop-shoulder cut'),
                'colors' => $colors,
                'sizes' => $sizes
            ];

            // Check if updating existing or appending new
            $found = false;
            foreach ($hoodies as $idx => $existing) {
                if (($existing['id'] ?? '') === $h_id) {
                    $hoodies[$idx] = $hoodie_data;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $hoodies[] = $hoodie_data;
            }

            $store['hoodies'] = $hoodies;
            if (save_product_data($store)) {
                $success_msg = 'Hoodie saved successfully!';
                $editing_hoodie = $hoodie_data;
            } else {
                $error_msg = 'Failed to save product data to data/product.json.';
            }

        // 2. DELETE HOODIE
        } elseif ($action === 'delete_hoodie') {
            $del_id = trim($_POST['del_id'] ?? '');
            if (!empty($del_id)) {
                $hoodies = array_values(array_filter($hoodies, function($item) use ($del_id) {
                    return ($item['id'] ?? '') !== $del_id;
                }));
                $store['hoodies'] = $hoodies;
                save_product_data($store);
                $success_msg = 'Hoodie removed from store.';
                $editing_hoodie = null;
            }

        // 3. ADD CATEGORY / FILTER
        } elseif ($action === 'add_category') {
            $new_cat = trim($_POST['new_category'] ?? '');
            if (!empty($new_cat)) {
                if (!in_array($new_cat, $categories)) {
                    $categories[] = $new_cat;
                    $store['categories'] = array_values($categories);
                    save_product_data($store);
                    $success_msg = "Filter category '{$new_cat}' added successfully!";
                } else {
                    $error_msg = 'Category already exists.';
                }
            } else {
                $error_msg = 'Please enter a category name.';
            }

        // 4. DELETE CATEGORY / FILTER
        } elseif ($action === 'delete_category') {
            $cat_name = trim($_POST['category_name'] ?? '');
            if (!empty($cat_name)) {
                $categories = array_values(array_filter($categories, function($c) use ($cat_name) {
                    return $c !== $cat_name;
                }));
                $store['categories'] = $categories;
                save_product_data($store);
                $success_msg = "Filter category '{$cat_name}' removed.";
            }

        // 5. SAVE GLOBAL STORE SETTINGS
        } elseif ($action === 'save_store_settings') {
            $store['store_name'] = trim($_POST['store_name'] ?? 'THE NORTH HOODIES');
            $store['tagline'] = trim($_POST['tagline'] ?? '');
            $store['announcement'] = trim($_POST['announcement'] ?? '');
            $store['currency'] = trim($_POST['currency'] ?? '$');
            $store['currency_code'] = strtoupper(trim($_POST['currency_code'] ?? 'USD'));
            $store['whatsapp_number'] = clean_phone_number($_POST['whatsapp_number'] ?? '');
            $store['whatsapp_message_template'] = trim($_POST['whatsapp_message_template'] ?? '');

            if (save_product_data($store)) {
                $success_msg = 'Store and WhatsApp settings updated!';
            } else {
                $error_msg = 'Failed to write store settings.';
            }

        // 6. CHANGE PASSWORD
        } elseif ($action === 'change_password') {
            $current_pass = trim($_POST['current_password'] ?? '');
            $new_pass = trim($_POST['new_password'] ?? '');
            $confirm_pass = trim($_POST['confirm_password'] ?? '');

            if (!password_verify($current_pass, $admin_cfg['password_hash'] ?? '')) {
                $error_msg = 'Current password is incorrect.';
            } elseif (strlen($new_pass) < 6) {
                $error_msg = 'New password must be at least 6 characters.';
            } elseif ($new_pass !== $confirm_pass) {
                $error_msg = 'New password and confirmation do not match.';
            } else {
                $admin_cfg['password_hash'] = password_hash($new_pass, PASSWORD_DEFAULT);
                if (save_admin_config($admin_cfg)) {
                    $success_msg = 'Admin password changed successfully!';
                } else {
                    $error_msg = 'Failed to update admin credentials file.';
                }
            }
        }
    }
}

$csrf = get_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoodie Store Manager — <?= e($store['store_name'] ?? 'THE NORTH HOODIES') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .hoodies-admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 16px;
        }
        .hoodie-admin-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s, border-color 0.2s;
        }
        .hoodie-admin-card:hover {
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        /* Fixed 1:1 bulletproof container for admin cards */
        .hoodie-card-thumb-wrap {
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 100%;
            background: #000;
            overflow: hidden;
        }
        .hoodie-card-thumb {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }
        .hoodie-card-content {
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex-grow: 1;
        }
        .hoodie-type-badge {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--accent-wa);
            letter-spacing: 0.5px;
        }
        .hoodie-card-title {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            line-height: 1.3;
        }
        .hoodie-card-price {
            font-size: 18px;
            font-weight: 800;
            color: #ffffff;
            margin-top: 4px;
        }
        .hoodie-card-actions {
            padding: 12px 18px;
            background: var(--bg-input);
            border-top: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .category-pill-item {
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            padding: 10px 16px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<header class="admin-navbar">
    <div class="nav-brand">
        <span class="brand-logo-badge">THE NORTH HOODIES</span>
        <div>
            <span class="nav-brand-title"><?= e($store['store_name'] ?? 'THE NORTH HOODIES') ?></span>
            <span class="nav-brand-subtitle">Catalog & WhatsApp Manager</span>
        </div>
    </div>
    <div class="nav-actions">
        <a href="../index.php" target="_blank" class="btn btn-secondary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
            View Live Store
        </a>
        <a href="logout.php" class="btn btn-danger btn-sm">
            Log Out
        </a>
    </div>
</header>

<main class="admin-container">

    <!-- Flash Alerts -->
    <?php if (!empty($success_msg)): ?>
        <div class="alert-banner alert-success">
            <div style="display:flex; align-items:center; gap:10px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <?= e($success_msg) ?>
            </div>
            <span style="cursor:pointer;" onclick="this.parentElement.remove()">✕</span>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert-banner alert-error">
            <div style="display:flex; align-items:center; gap:10px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <?= e($error_msg) ?>
            </div>
            <span style="cursor:pointer;" onclick="this.parentElement.remove()">✕</span>
        </div>
    <?php endif; ?>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <h1>Hoodie Collection Manager</h1>
        <p>Manage all your hoodie models, customize filter categories, update prices, and adjust WhatsApp routing.</p>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats-grid">
        <div class="stat-card">
            <span class="stat-label">Hoodie Models</span>
            <span class="stat-value"><?= count($hoodies) ?> in Catalog</span>
            <span class="stat-sub">Strictly hoodies showcase</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Active Filter Categories</span>
            <span class="stat-value"><?= count($categories) ?> Filters</span>
            <span class="stat-sub">Editable in Category Manager</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">WhatsApp Target</span>
            <span class="stat-value" style="font-size:20px;"><?= e($store['whatsapp_number'] ? '+' . $store['whatsapp_number'] : 'Not Configured') ?></span>
            <span class="stat-sub">Pre-filled order routing</span>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <nav class="tabs-nav">
        <button type="button" class="tab-btn <?= empty($editing_hoodie) && empty($_GET['add']) ? 'active' : '' ?>" data-tab="tab-catalog">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            All Hoodies (<?= count($hoodies) ?>)
        </button>
        <button type="button" class="tab-btn <?= !empty($editing_hoodie) || !empty($_GET['add']) ? 'active' : '' ?>" data-tab="tab-edit-hoodie">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            <?= !empty($editing_hoodie) ? 'Edit: ' . e($editing_hoodie['title']) : '+ Add New Hoodie' ?>
        </button>
        <button type="button" class="tab-btn" data-tab="tab-filters">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
            Categories & Filters (<?= count($categories) ?>)
        </button>
        <button type="button" class="tab-btn" data-tab="tab-settings">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
            Store & WhatsApp Settings
        </button>
        <button type="button" class="tab-btn" data-tab="tab-security">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            Password & Security
        </button>
    </nav>

    <!-- TAB 1: ALL HOODIES CATALOG -->
    <div class="tab-panel <?= empty($editing_hoodie) && empty($_GET['add']) ? 'active' : '' ?>" id="tab-catalog">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
            <div>
                <h2 style="font-size:20px; font-weight:800;">Current Hoodie Collection</h2>
                <p style="font-size:13px; color:var(--text-muted);">All card images maintain a strict 1:1 ratio so they never break alignment.</p>
            </div>
            <a href="index.php?add=1" class="btn btn-primary" onclick="document.querySelector('[data-tab=\'tab-edit-hoodie\']').click(); return false;">
                + Add New Hoodie
            </a>
        </div>

        <div class="hoodies-admin-grid">
            <?php foreach ($hoodies as $h): ?>
                <div class="hoodie-admin-card">
                    <div class="hoodie-card-thumb-wrap">
                        <img src="../<?= e($h['image'] ?? 'assets/images/hoodie-black.jpg') ?>" alt="Hoodie" class="hoodie-card-thumb" onerror="this.src='https://via.placeholder.com/400?text=Hoodie'">
                        <?php if (!empty($h['badge'])): ?>
                            <span class="primary-badge" style="position:absolute; top:10px; left:10px; z-index:2;"><?= e($h['badge']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="hoodie-card-content">
                        <span class="hoodie-type-badge"><?= e($h['type'] ?? 'Hoodie') ?></span>
                        <h3 class="hoodie-card-title"><?= e($h['title']) ?></h3>
                        <div class="hoodie-card-price">
                            <?= e($store['currency'] ?? '$') ?><?= number_format($h['sale_price'] ?? 0, 2) ?>
                            <?php if (!empty($h['original_price']) && $h['original_price'] > $h['sale_price']): ?>
                                <del style="font-size:13px; color:var(--text-muted); margin-left:6px;"><?= e($store['currency'] ?? '$') ?><?= number_format($h['original_price'], 2) ?></del>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:12px; color:var(--text-dim); margin-top:4px;">
                            Colors: <?= count($h['colors'] ?? []) ?> · Sizes: <?= count($h['sizes'] ?? []) ?>
                        </div>
                    </div>
                    <div class="hoodie-card-actions">
                        <a href="index.php?edit=<?= urlencode($h['id']) ?>" class="btn btn-secondary btn-sm">
                            Edit Details
                        </a>
                        <form method="POST" action="index.php" onsubmit="return confirm('Are you sure you want to delete this hoodie?');" style="margin:0;">
                            <input type="hidden" name="action" value="delete_hoodie">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="del_id" value="<?= e($h['id']) ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- TAB 2: EDIT / ADD HOODIE FORM -->
    <div class="tab-panel <?= !empty($editing_hoodie) || !empty($_GET['add']) ? 'active' : '' ?>" id="tab-edit-hoodie">
        <form method="POST" action="index.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_hoodie">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="hoodie_id" value="<?= e($editing_hoodie['id'] ?? '') ?>">
            <input type="hidden" name="existing_image" value="<?= e($editing_hoodie['image'] ?? 'assets/images/hoodie-black.jpg') ?>">

            <div class="card-section">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; flex-wrap:wrap; gap:10px;">
                    <h2 class="card-title" style="margin-bottom:0;">
                        <?= !empty($editing_hoodie) ? 'Edit Hoodie: ' . e($editing_hoodie['title']) : 'Create New Hoodie' ?>
                    </h2>
                    <?php if (!empty($editing_hoodie)): ?>
                        <a href="index.php" class="btn btn-secondary btn-sm">← Back to All Hoodies</a>
                    <?php endif; ?>
                </div>
                <p class="card-desc">Enter the details, filter category, pricing, colors, and sizes for this hoodie.</p>

                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Hoodie Model Title <span class="req">*</span></label>
                        <input type="text" name="title" class="form-input" value="<?= e($editing_hoodie['title'] ?? '') ?>" placeholder="e.g. Heavyweight Boxy Zip-Up" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category / Filter Tag <span class="req">*</span></label>
                        <select name="type" class="form-select" id="hoodie-type-select">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= e($cat) ?>" <?= ($editing_hoodie['type'] ?? '') === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                            <?php endforeach; ?>
                            <option value="__custom__">+ Or Add New Category Below...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Product Badge</label>
                        <input type="text" name="badge" class="form-input" value="<?= e($editing_hoodie['badge'] ?? 'NEW DROP') ?>" placeholder="e.g. BEST SELLER, LIMITED, ARCHIVE">
                    </div>
                </div>

                <!-- Optional custom category input if admin wants to type a new one -->
                <div class="form-group" id="custom-type-group" style="display:none;">
                    <label class="form-label">New Custom Filter Category Name</label>
                    <input type="text" name="custom_type" class="form-input" placeholder="e.g. Cropped Hoodies, Sherpa Lined">
                    <div class="form-hint">This will also be automatically added to your store's filter tabs.</div>
                </div>

                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Selling Price <span class="req">*</span></label>
                        <input type="number" step="0.01" name="sale_price" class="form-input" value="<?= e($editing_hoodie['sale_price'] ?? 79) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Original Price (Strikethrough)</label>
                        <input type="number" step="0.01" name="original_price" class="form-input" value="<?= e($editing_hoodie['original_price'] ?? 120) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stock Status</label>
                        <select name="stock_status" class="form-select">
                            <option value="in_stock" <?= ($editing_hoodie['stock_status'] ?? '') === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
                            <option value="low_stock" <?= ($editing_hoodie['stock_status'] ?? '') === 'low_stock' ? 'selected' : '' ?>>Low Stock (Urgency Badge)</option>
                            <option value="sold_out" <?= ($editing_hoodie['stock_status'] ?? '') === 'sold_out' ? 'selected' : '' ?>>Sold Out</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Stock Urgency Notice</label>
                    <input type="text" name="stock_text" class="form-input" value="<?= e($editing_hoodie['stock_text'] ?? 'Only 8 pieces remaining in this batch') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Short Description</label>
                    <textarea name="short_description" class="form-textarea" rows="3"><?= e($editing_hoodie['short_description'] ?? '') ?></textarea>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Fabric & GSM Specification</label>
                        <input type="text" name="fabric" class="form-input" value="<?= e($editing_hoodie['fabric'] ?? '500 GSM Heavyweight French Terry Cotton') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fit & Silhouette Guidance</label>
                        <input type="text" name="fit" class="form-input" value="<?= e($editing_hoodie['fit'] ?? 'Relaxed boxy streetwear fit. True to size.') ?>">
                    </div>
                </div>
            </div>

            <!-- Image Upload Section -->
            <div class="card-section">
                <h2 class="card-title">Hoodie Photography (Fixed 1:1 Ratio Guaranteed)</h2>
                <p class="card-desc">You can upload any photo size (portrait, square, or wide); the system automatically centers and fits it into a clean 1:1 container so it never distorts or breaks the card layout.</p>

                <div style="display:flex; gap:20px; align-items:center; flex-wrap:wrap;">
                    <div style="width:120px; height:120px; border-radius:var(--radius-md); overflow:hidden; background:#000; border:1px solid var(--border-color); flex-shrink:0; position:relative;">
                        <img src="../<?= e($editing_hoodie['image'] ?? 'assets/images/hoodie-black.jpg') ?>" style="width:100%; height:100%; object-fit:cover; position:absolute; top:0; left:0;">
                    </div>
                    <div style="flex-grow:1;">
                        <label class="form-label">Upload New Photo (JPG, PNG, WEBP)</label>
                        <input type="file" name="hoodie_image" accept="image/*" class="form-input" style="padding:8px;">
                        <div class="form-hint">Or specify an existing asset path below:</div>
                        <input type="text" name="secondary_image" class="form-input" style="margin-top:6px;" value="<?= e($editing_hoodie['secondary_image'] ?? $editing_hoodie['image'] ?? 'assets/images/hoodie-black.jpg') ?>" placeholder="assets/images/...">
                    </div>
                </div>
            </div>

            <!-- Colors Table -->
            <div class="card-section">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                    <h2 class="card-title" style="margin-bottom:0;">Available Colors</h2>
                    <button type="button" id="btn-add-color" class="btn btn-secondary btn-sm">+ Add Color</button>
                </div>
                <div style="overflow-x:auto;">
                    <table class="dynamic-table">
                        <thead>
                            <tr>
                                <th style="width: 30%;">Color Name</th>
                                <th style="width: 25%;">Hex Swatch</th>
                                <th style="width: 35%;">Associated Image Path</th>
                                <th style="width: 10%; text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="colors-tbody">
                            <?php 
                            $colors_list = !empty($editing_hoodie['colors']) ? $editing_hoodie['colors'] : [
                                ['name' => 'Washed Onyx', 'hex' => '#1e1e1e', 'image' => 'assets/images/hoodie-black.jpg'],
                                ['name' => 'Bone Oatmeal', 'hex' => '#e7dfd1', 'image' => 'assets/images/hoodie-bone.jpg']
                            ];
                            foreach ($colors_list as $cidx => $col): 
                            ?>
                                <tr>
                                    <td>
                                        <input type="text" name="colors[<?= $cidx ?>][name]" class="form-input" value="<?= e($col['name']) ?>" required>
                                    </td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <input type="color" name="colors[<?= $cidx ?>][hex]" value="<?= e($col['hex'] ?? '#000000') ?>" style="width:40px; height:38px; border:none; border-radius:6px; cursor:pointer; background:none;">
                                            <input type="text" class="form-input color-hex-val" value="<?= e($col['hex'] ?? '#000000') ?>" style="width:90px;" onchange="this.previousElementSibling.value = this.value">
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="colors[<?= $cidx ?>][image]" class="form-input" value="<?= e($col['image'] ?? '') ?>" placeholder="assets/images/... or URL">
                                    </td>
                                    <td style="text-align:right;">
                                        <button type="button" class="btn btn-danger btn-sm btn-remove-row">Remove</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sizes Table -->
            <div class="card-section">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                    <h2 class="card-title" style="margin-bottom:0;">Available Sizes</h2>
                    <button type="button" id="btn-add-size" class="btn btn-secondary btn-sm">+ Add Size</button>
                </div>
                <div style="overflow-x:auto;">
                    <table class="dynamic-table">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Size</th>
                                <th style="width: 30%;">Chest</th>
                                <th style="width: 30%;">Length</th>
                                <th style="width: 10%; text-align:center;">In Stock</th>
                                <th style="width: 10%; text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="sizes-tbody">
                            <?php 
                            $sizes_list = !empty($editing_hoodie['sizes']) ? $editing_hoodie['sizes'] : [
                                ['label' => 'S', 'chest' => '48"', 'length' => '27"', 'available' => true],
                                ['label' => 'M', 'chest' => '50"', 'length' => '28"', 'available' => true],
                                ['label' => 'L', 'chest' => '52"', 'length' => '29"', 'available' => true],
                                ['label' => 'XL', 'chest' => '54"', 'length' => '30"', 'available' => true]
                            ];
                            foreach ($sizes_list as $sidx => $sz): 
                            ?>
                                <tr>
                                    <td>
                                        <input type="text" name="sizes[<?= $sidx ?>][label]" class="form-input" value="<?= e($sz['label']) ?>" required style="width:80px;">
                                    </td>
                                    <td>
                                        <input type="text" name="sizes[<?= $sidx ?>][chest]" class="form-input" value="<?= e($sz['chest'] ?? '') ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="sizes[<?= $sidx ?>][length]" class="form-input" value="<?= e($sz['length'] ?? '') ?>">
                                    </td>
                                    <td style="text-align:center;">
                                        <input type="checkbox" name="sizes[<?= $sidx ?>][available]" value="1" <?= !empty($sz['available']) ? 'checked' : '' ?> style="width:18px; height:18px; cursor:pointer;">
                                    </td>
                                    <td style="text-align:right;">
                                        <button type="button" class="btn btn-danger btn-sm btn-remove-row">Remove</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Save Sticky Bar -->
            <div class="admin-sticky-footer">
                <div style="font-size:13px; color:var(--text-muted);">
                    Saves directly to <code>data/product.json</code>
                </div>
                <button type="submit" class="btn btn-primary" style="padding:12px 28px;">
                    Save Hoodie Changes
                </button>
            </div>
        </form>
    </div>

    <!-- TAB 3: CATEGORIES & FILTERS MANAGER -->
    <div class="tab-panel" id="tab-filters">
        <div class="card-section" style="max-width: 750px;">
            <h2 class="card-title">Filter Categories Manager</h2>
            <p class="card-desc">These categories power the filter buttons on your storefront. You can add new hoodie categories, rename them, or delete unused ones.</p>

            <!-- Add Category Form -->
            <form method="POST" action="index.php" style="display:flex; gap:10px; margin-bottom:24px; flex-wrap:wrap;">
                <input type="hidden" name="action" value="add_category">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="text" name="new_category" class="form-input" placeholder="e.g. Cropped Hoodies, Acid Wash, Fleece" style="flex:1; min-width:240px;" required>
                <button type="submit" class="btn btn-primary">+ Add Category</button>
            </form>

            <div style="font-size:13px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.8px; margin-bottom:12px;">Active Store Filters:</div>

            <?php foreach ($categories as $cat): 
                // Count how many hoodies currently use this category
                $count = 0;
                $c_words = preg_split('/\s+/', strtolower($cat));
                foreach ($hoodies as $h) {
                    $h_text = strtolower($h['type'] ?? '');
                    if ($h_text === strtolower($cat)) {
                        $count++;
                    } else {
                        foreach ($c_words as $w) {
                            $stem = rtrim($w, 'es');
                            if (strlen($stem) >= 3 && stripos($h_text, $stem) !== false) {
                                $count++;
                                break;
                            }
                        }
                    }
                }
            ?>
                <div class="category-pill-item">
                    <div>
                        <strong style="color:#fff; font-size:15px;"><?= e($cat) ?></strong>
                        <span style="font-size:12px; color:var(--text-dim); margin-left:8px;">(<?= $count ?> hoodies assigned)</span>
                    </div>
                    <form method="POST" action="index.php" onsubmit="return confirm('Remove category <?= addslashes(e($cat)) ?>?');" style="margin:0;">
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="category_name" value="<?= e($cat) ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- TAB 4: STORE & WHATSAPP SETTINGS -->
    <div class="tab-panel" id="tab-settings">
        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="save_store_settings">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

            <div class="card-section">
                <h2 class="card-title">Brand & Top Announcement</h2>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Store Brand Name <span class="req">*</span></label>
                        <input type="text" name="store_name" class="form-input" value="<?= e($store['store_name'] ?? 'THE NORTH HOODIES') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Store Tagline</label>
                        <input type="text" name="tagline" class="form-input" value="<?= e($store['tagline'] ?? 'thenorthhoodies.com') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Top Announcement Bar Text</label>
                    <input type="text" name="announcement" class="form-input" value="<?= e($store['announcement'] ?? '') ?>">
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Currency Symbol</label>
                        <input type="text" name="currency" class="form-input" value="<?= e($store['currency'] ?? '$') ?>" placeholder="$">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Currency Code</label>
                        <input type="text" name="currency_code" class="form-input" value="<?= e($store['currency_code'] ?? 'USD') ?>" placeholder="USD">
                    </div>
                </div>
            </div>

            <div class="card-section">
                <h2 class="card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="#25d366"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"></path></svg>
                    WhatsApp Business Checkout Settings
                </h2>
                <p class="card-desc">All customer orders across any hoodie route directly to this WhatsApp number.</p>

                <div class="form-group">
                    <label class="form-label">WhatsApp Business Phone Number <span class="req">*</span></label>
                    <input type="text" name="whatsapp_number" class="form-input" value="<?= e($store['whatsapp_number'] ?? '') ?>" placeholder="e.g. 15550192834 (With country code, no +)" required>
                    <div class="form-hint">Enter your phone number including country code (e.g., <code>1</code> for USA, <code>44</code> for UK, <code>92</code> for PK).</div>
                </div>

                <div class="form-group">
                    <label class="form-label">WhatsApp Pre-filled Order Message Template</label>
                    <textarea name="whatsapp_message_template" class="form-textarea" rows="6"><?= e($store['whatsapp_message_template'] ?? '') ?></textarea>
                    <div class="form-hint">Supported placeholders: <code>{product_name}</code>, <code>{hoodie_type}</code>, <code>{color}</code>, <code>{size}</code>, <code>{quantity}</code>, <code>{total_price}</code></div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top:10px;">Save Store & WhatsApp Settings</button>
            </div>
        </form>
    </div>

    <!-- TAB 5: PASSWORD & SECURITY -->
    <div class="tab-panel" id="tab-security">
        <div class="card-section" style="max-width: 600px;">
            <h2 class="card-title">Change Admin Password</h2>
            <p class="card-desc">Update your admin login password.</p>

            <form method="POST" action="index.php">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                <div class="form-group">
                    <label class="form-label">Current Password <span class="req">*</span></label>
                    <input type="password" name="current_password" class="form-input" required placeholder="••••••••">
                </div>

                <div class="form-group">
                    <label class="form-label">New Password <span class="req">*</span> (min 6 characters)</label>
                    <input type="password" name="new_password" class="form-input" required minlength="6" placeholder="••••••••">
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password <span class="req">*</span></label>
                    <input type="password" name="confirm_password" class="form-input" required minlength="6" placeholder="••••••••">
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top:10px;">Update Password</button>
            </form>
        </div>
    </div>

</main>

<script>
// Toggle custom category field if selected
const typeSelect = document.getElementById('hoodie-type-select');
const customTypeGroup = document.getElementById('custom-type-group');
if (typeSelect && customTypeGroup) {
    typeSelect.addEventListener('change', () => {
        if (typeSelect.value === '__custom__') {
            customTypeGroup.style.display = 'block';
            customTypeGroup.querySelector('input').focus();
        } else {
            customTypeGroup.style.display = 'none';
        }
    });
}
</script>
<script src="../assets/js/admin.js"></script>
</body>
</html>
