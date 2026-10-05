<?php
/**
 * Core Helper Functions for Hoodie Store
 */

define('DATA_PATH', __DIR__ . '/../data/product.json');
define('ADMIN_PATH', __DIR__ . '/../data/admin.json');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', 'uploads/');

/**
 * Load product & catalog data from JSON file
 */
function get_product_data() {
    if (!file_exists(DATA_PATH)) {
        return [];
    }
    $raw = file_get_contents(DATA_PATH);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Save product & catalog data to JSON file
 */
function save_product_data($data) {
    $dir = dirname(DATA_PATH);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return file_put_contents(DATA_PATH, $json, LOCK_EX) !== false;
}

/**
 * Get specific hoodie by ID
 */
function get_hoodie_by_id($hoodies, $id) {
    foreach ($hoodies as $h) {
        if (($h['id'] ?? '') === $id) {
            return $h;
        }
    }
    return null;
}

/**
 * Load admin configuration
 */
function get_admin_config() {
    if (!file_exists(ADMIN_PATH)) {
        return [
            'username' => 'admin',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT)
        ];
    }
    $raw = file_get_contents(ADMIN_PATH);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Save admin configuration
 */
function save_admin_config($data) {
    $dir = dirname(ADMIN_PATH);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return file_put_contents(ADMIN_PATH, $json, LOCK_EX) !== false;
}

/**
 * Sanitize plain string output
 */
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize WhatsApp phone number (strip spaces, dashes, parentheses)
 */
function clean_phone_number($phone) {
    $cleaned = preg_replace('/[^\d]/', '', $phone);
    // Auto-convert Pakistani local format (e.g. 03418956864 -> 923418956864)
    if (strlen($cleaned) === 11 && substr($cleaned, 0, 2) === '03') {
        $cleaned = '92' . substr($cleaned, 1);
    }
    return $cleaned;
}

/**
 * Build prefilled WhatsApp URL for a specific hoodie
 */
function build_whatsapp_url_for_hoodie($store, $hoodie, $selected_color = '', $selected_size = '', $quantity = 1) {
    $phone = clean_phone_number($store['whatsapp_number'] ?? '');
    if (empty($phone)) {
        $phone = '1234567890';
    }

    $template = $store['whatsapp_message_template'] ?? "Hello! I would like to order {product_name} ({hoodie_type}).\nColor: {color}\nSize: {size}\nQuantity: {quantity}\nTotal: {total_price}";

    $unit_price = (float)($hoodie['sale_price'] ?? $hoodie['original_price'] ?? 0);
    $total_price = ($store['currency'] ?? '$') . number_format($unit_price * max(1, (int)$quantity), 2);

    $color_name = $selected_color ?: ($hoodie['colors'][0]['name'] ?? 'Standard');
    $size_name = $selected_size ?: ($hoodie['sizes'][0]['label'] ?? 'Standard');
    $hoodie_type = $hoodie['type'] ?? 'Hoodie';

    $message = str_replace(
        ['{product_name}', '{hoodie_type}', '{color}', '{size}', '{quantity}', '{total_price}'],
        [$hoodie['title'] ?? 'Hoodie', $hoodie_type, $color_name, $size_name, (string)$quantity, $total_price],
        $template
    );

    return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
}

/**
 * Handle secure file upload for images
 */
function handle_image_upload($file_array) {
    if (!isset($file_array['error']) || $file_array['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error code: ' . ($file_array['error'] ?? 'unknown')];
    }

    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];

    $extension = strtolower(pathinfo($file_array['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowed_extensions)) {
        return ['success' => false, 'error' => 'Invalid file extension. Only JPG, PNG, WEBP allowed.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file_array['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_mimes)) {
        return ['success' => false, 'error' => 'Invalid file type: ' . $mime];
    }

    // Limit size to 10MB
    if ($file_array['size'] > 10 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File too large. Maximum size is 10MB.'];
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $filename = 'hoodie_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = UPLOAD_DIR . $filename;

    if (move_uploaded_file($file_array['tmp_name'], $destination)) {
        return ['success' => true, 'path' => UPLOAD_URL . $filename];
    }

    return ['success' => false, 'error' => 'Failed to move uploaded file. Check directory permissions.'];
}
