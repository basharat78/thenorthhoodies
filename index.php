<?php
require_once __DIR__ . '/includes/functions.php';

$store = get_product_data();
$hoodies = $store['hoodies'] ?? [];
$initial_phone = clean_phone_number($store['whatsapp_number'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= e($store['store_name'] ?? 'THE NORTH HOODIES') ?> — <?= e($store['tagline'] ?? 'thenorthhoodies.com') ?></title>
    
    <!-- SEO & Social Meta -->
    <meta name="description" content="Exclusive streetwear collection of luxury 500 GSM heavyweight hoodies. Oversized pullovers, boxy zip-ups, and mineral-washed fleeces. Order directly via WhatsApp.">
    <meta property="og:title" content="<?= e($store['store_name'] ?? 'THE NORTH HOODIES') ?>">
    <meta property="og:description" content="Dedicated 500 GSM Heavyweight Hoodie Atelier. Order directly via WhatsApp.">
    <meta property="og:type" content="website">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
    
    <!-- CSS Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Store Data for Interactive Client App -->
    <script id="store-json-data" type="application/json">
        <?= json_encode($store, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
</head>
<body>

<!-- Announcement Bar -->
<?php if (!empty($store['announcement'])): ?>
<div class="top-ticker">
    <span class="badge-pulse"></span>
    <span><?= e($store['announcement']) ?></span>
</div>
<?php endif; ?>

<!-- Main Header -->
<header class="site-header">
    <div class="header-inner">
        <div class="brand-wrap">
            <span class="brand-title"><?= e($store['store_name'] ?? 'THE NORTH HOODIES') ?></span>
            <span class="brand-tag"><?= e($store['tagline'] ?? 'thenorthhoodies.com') ?></span>
        </div>
        <div class="header-right">
            <span style="font-size:12px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px;" class="hide-mobile">
                <?= count($hoodies) ?> Models Available
            </span>
            <a href="https://wa.me/<?= e($initial_phone) ?>?text=<?= rawurlencode("Hello! I have a question about your hoodie collection.") ?>" target="_blank" rel="noopener" class="header-wa-btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"></path></svg>
                <span>WhatsApp Concierge</span>
            </a>
        </div>
    </div>
</header>

<!-- Hero Collection Presentation -->
<section class="collection-hero">
    <div class="hero-inner">
        <div class="hero-badge">
            <span class="badge-pulse"></span>
            Drop 04 — Heavyweight French Terry
        </div>
        <h1 class="hero-title">The North Hoodies</h1>
        <p class="hero-desc">
            We do one thing, with zero compromises: <strong>Heavyweight Hoodies</strong>. 480 to 500 GSM loopback cotton, architectural boxy fits, seamless structured hoods, and vintage garment dyes.
        </p>

        <!-- Category Filter Pills (Dynamic from Admin Panel) -->
        <div class="filter-tabs">
            <button type="button" class="filter-btn active" data-filter="all">All Hoodies (<?= count($hoodies) ?>)</button>
            <?php 
            $categories = $store['categories'] ?? [];
            foreach ($categories as $cat): 
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
                <button type="button" class="filter-btn" data-filter="<?= e($cat) ?>"><?= e($cat) ?> (<?= $count ?>)</button>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Hoodie Catalog Grid -->
<section class="catalog-section">
    <div class="catalog-container">
        <div class="hoodie-grid" id="hoodie-grid-container">
            <?php foreach ($hoodies as $hoodie): 
                $sale = (float)($hoodie['sale_price'] ?? 0);
                $orig = (float)($hoodie['original_price'] ?? 0);
                $pct = ($orig > $sale && $orig > 0) ? round((($orig - $sale) / $orig) * 100) : 0;
            ?>
                <div class="hoodie-card" data-type="<?= e($hoodie['type'] ?? '') ?>">
                    <div class="hoodie-thumb-wrap open-hoodie-buy" data-hoodie-id="<?= e($hoodie['id']) ?>">
                        <?php if (!empty($hoodie['badge'])): ?>
                            <span class="card-badge"><?= e($hoodie['badge']) ?></span>
                        <?php endif; ?>
                        <img src="<?= e($hoodie['image'] ?? 'assets/images/hoodie-black.jpg') ?>" alt="<?= e($hoodie['title']) ?>" class="hoodie-thumb" loading="lazy">
                    </div>

                    <div class="hoodie-card-body">
                        <div>
                            <div class="hoodie-type-tag"><?= e($hoodie['type'] ?? 'Heavyweight Hoodie') ?></div>
                            <h2 class="hoodie-card-title"><?= e($hoodie['title']) ?></h2>
                            <p class="hoodie-card-desc"><?= e($hoodie['short_description'] ?? '') ?></p>

                            <!-- Color dots preview -->
                            <?php if (!empty($hoodie['colors'])): ?>
                                <div class="colors-preview-strip">
                                    <?php foreach ($hoodie['colors'] as $c): ?>
                                        <span class="color-dot" style="background-color: <?= e($c['hex'] ?? '#000') ?>;" title="<?= e($c['name']) ?>"></span>
                                    <?php endforeach; ?>
                                    <span class="color-count-label"><?= count($hoodie['colors']) ?> Colors Available</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <div class="card-price-row">
                                <div>
                                    <span class="card-sale-price"><?= e($store['currency'] ?? '$') ?><?= number_format($sale, 2) ?></span>
                                    <?php if ($orig > $sale): ?>
                                        <span class="card-orig-price"><?= e($store['currency'] ?? '$') ?><?= number_format($orig, 2) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($pct > 0): ?>
                                    <span class="card-discount-pill"><?= $pct ?>% OFF</span>
                                <?php endif; ?>
                            </div>

                            <button type="button" class="btn-card-order open-hoodie-buy" data-hoodie-id="<?= e($hoodie['id']) ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"></path></svg>
                                <span>Select & Order on WhatsApp</span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Why Only Hoodies Section -->
<section class="why-hoodies-section">
    <div class="why-inner">
        <div style="text-align:center;">
            <div style="font-size:11px; font-weight:800; letter-spacing:2px; color:var(--wa-green); text-transform:uppercase; margin-bottom:8px;">The Specialist Approach</div>
            <h2 style="font-family:var(--font-display); font-size:32px; font-weight:900; text-transform:uppercase; color:#fff;">Why We Only Make Hoodies</h2>
        </div>

        <div class="highlights-cards-grid">
            <div class="highlight-card">
                <div class="highlight-num">01</div>
                <div class="highlight-title">500 GSM French Terry</div>
                <p style="font-size:13px; color:var(--text-secondary); margin-top:6px;">Nearly double the density of high-street hoodies. Unmatched drape, warmth, and lifelong shape retention.</p>
            </div>
            <div class="highlight-card">
                <div class="highlight-num">02</div>
                <div class="highlight-title">Architectural Boxy Cuts</div>
                <p style="font-size:13px; color:var(--text-secondary); margin-top:6px;">Drop-shoulder silhouette with heavy ribbed cuffs that sit naturally at your hips without bagginess.</p>
            </div>
            <div class="highlight-card">
                <div class="highlight-num">03</div>
                <div class="highlight-title">Structured Seamless Hoods</div>
                <p style="font-size:13px; color:var(--text-secondary); margin-top:6px;">Double-layer construction that stands upright cleanly on your neck without floppy drawstrings.</p>
            </div>
            <div class="highlight-card">
                <div class="highlight-num">04</div>
                <div class="highlight-title">Direct WhatsApp Support</div>
                <p style="font-size:13px; color:var(--text-secondary); margin-top:6px;">Every order is confirmed by a real specialist. Get real-time photos, sizing advice, and tracking on your phone.</p>
            </div>
        </div>
    </div>
</section>

<!-- Customer Reviews Section -->
<?php if (!empty($store['reviews'])): ?>
<section class="reviews-section">
    <div style="max-width:var(--container-max); margin:0 auto; text-align:center;">
        <div style="font-size:11px; font-weight:800; letter-spacing:2px; color:var(--wa-green); text-transform:uppercase; margin-bottom:8px;">Real Customer Reviews</div>
        <h2 style="font-family:var(--font-display); font-size:32px; font-weight:900; text-transform:uppercase; color:#fff;">What Streetwear Collectors Say</h2>
    </div>

    <div class="reviews-grid">
        <?php foreach ($store['reviews'] as $rev): ?>
            <div class="review-card">
                <div>
                    <div class="review-stars">★★★★★</div>
                    <p style="font-size:14px; color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">“<?= e($rev['comment']) ?>”</p>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-light); padding-top:14px;">
                    <div>
                        <div style="font-size:14px; font-weight:700; color:#fff;"><?= e($rev['name']) ?></div>
                        <div style="font-size:12px; color:var(--text-muted);"><?= e($rev['variant'] ?? '') ?></div>
                    </div>
                    <span style="font-size:11px; color:var(--wa-green); font-weight:700;">Verified Buyer ✓</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- FAQ Section -->
<?php if (!empty($store['faqs'])): ?>
<section class="specs-section">
    <div style="text-align:center; margin-bottom:28px;">
        <div style="font-size:11px; font-weight:800; letter-spacing:2px; color:var(--wa-green); text-transform:uppercase; margin-bottom:8px;">Questions & Answers</div>
        <h2 style="font-family:var(--font-display); font-size:28px; font-weight:900; text-transform:uppercase; color:#fff;">Frequently Asked Questions</h2>
    </div>

    <?php foreach ($store['faqs'] as $qidx => $faq): ?>
        <div class="accordion-item <?= $qidx === 0 ? 'active' : '' ?>">
            <button type="button" class="accordion-header">
                <span><?= e($faq['question']) ?></span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="accordion-body" <?= $qidx === 0 ? 'style="max-height: 200px;"' : '' ?>>
                <?= nl2br(e($faq['answer'])) ?>
            </div>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<!-- Interactive Quick-Buy Customization Modal -->
<div id="quickbuy-modal" class="modal-overlay">
    <div class="modal-container">
        <button type="button" id="modal-close-btn" class="modal-close-btn" title="Close">✕</button>

        <!-- Left Image View -->
        <div class="modal-image-pane">
            <span id="modal-hoodie-badge" class="card-badge" style="position:absolute; top:16px; left:16px;">NEW</span>
            <img id="modal-hoodie-img" src="" alt="Hoodie Preview">
        </div>

        <!-- Right Customizer -->
        <div class="modal-content-pane">
            <div id="modal-hoodie-type" style="font-size:11px; font-weight:800; color:var(--wa-green); text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;"></div>
            <h2 id="modal-hoodie-title" style="font-family:var(--font-display); font-size:24px; font-weight:900; color:#fff; text-transform:uppercase; margin-bottom:8px; line-height:1.2;"></h2>
            
            <div style="display:flex; align-items:baseline; gap:12px; margin-bottom:12px;">
                <span id="modal-hoodie-price" style="font-size:26px; font-weight:900; color:#fff;"></span>
                <span id="modal-hoodie-stock" style="font-size:12px; color:var(--accent-gold); font-weight:700;"></span>
            </div>

            <p id="modal-hoodie-desc" style="font-size:13px; color:var(--text-secondary); line-height:1.5; margin-bottom:18px;"></p>

            <!-- Color Swatches -->
            <div style="margin-bottom:16px;">
                <div style="font-size:12px; font-weight:700; color:#cbd5e1; text-transform:uppercase; letter-spacing:0.5px;">Select Color:</div>
                <div id="modal-swatches-container" class="swatches-row"></div>
            </div>

            <!-- Sizes -->
            <div style="margin-bottom:18px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div style="font-size:12px; font-weight:700; color:#cbd5e1; text-transform:uppercase; letter-spacing:0.5px;">Select Size:</div>
                    <span style="font-size:12px; color:var(--text-muted);">True to oversized fit</span>
                </div>
                <div id="modal-sizes-container" class="modal-sizes-row"></div>
            </div>

            <!-- Quantity & Calculated Total -->
            <div style="display:flex; align-items:center; justify-content:space-between; background:var(--bg-surface); padding:10px 14px; border-radius:var(--radius-sm); border:1px solid var(--border-light); margin-top:auto;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="font-size:12px; font-weight:700; color:var(--text-secondary);">QTY</span>
                    <button type="button" id="modal-qty-minus" style="width:28px; height:28px; background:var(--bg-highlight); border:1px solid var(--border-light); color:#fff; border-radius:4px; font-weight:700; cursor:pointer;">−</button>
                    <span id="modal-qty-val" style="font-size:15px; font-weight:800; min-width:20px; text-align:center;">1</span>
                    <button type="button" id="modal-qty-plus" style="width:28px; height:28px; background:var(--bg-highlight); border:1px solid var(--border-light); color:#fff; border-radius:4px; font-weight:700; cursor:pointer;">+</button>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:10px; color:var(--text-muted); text-transform:uppercase;">Order Total</div>
                    <div id="modal-calc-total" style="font-size:18px; font-weight:900; color:#fff;"></div>
                </div>
            </div>

            <!-- WhatsApp Order CTA -->
            <button type="button" id="modal-order-btn" class="modal-checkout-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"></path></svg>
                <span>Order via WhatsApp</span>
            </button>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="site-footer">
    <p>© <?= date('Y') ?> <?= e($store['store_name'] ?? 'THE NORTH HOODIES') ?>. All rights reserved.</p>
    <p style="margin-top:6px;">THE NORTH HOODIES • thenorthhoodies.com • Direct WhatsApp Checkout</p>
</footer>

<script src="assets/js/app.js"></script>
</body>
</html>
