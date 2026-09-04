<?php
/**
 * Product Validator Helper
 * Used by admin/product_action.php for server-side validation.
 */

require_once __DIR__ . '/helpers.php';

const ALLOWED_CATEGORIES = ['Pour Homme', 'Pour Femme', 'Unisex', 'Uncategorized'];
const ALLOWED_STATUSES   = ['Active', 'Draft'];

/**
 * Validate and sanitize product fields from a form submission.
 * 
 * @param array $input Typically $_POST
 * @return array ['errors' => array, 'data' => array]
 */
function validate_product_form(array $input): array {
    $errors = [];
    $data = [];

    // Sanitize input
    $data['name']        = sanitize_raw($input['name'] ?? '');
    $data['description'] = sanitize_raw($input['description'] ?? '');
    $data['price']       = $input['price'] ?? '';
    $data['stock']       = $input['stock'] ?? '';
    $data['category']    = sanitize_raw($input['category'] ?? 'Uncategorized');
    $data['status']      = sanitize_raw($input['status'] ?? 'Active');

    // Validation Rules
    if ($data['name'] === '') {
        $errors['name'] = 'Product name is required.';
    } elseif (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 255) {
        $errors['name'] = 'Product name must be between 2 and 255 characters.';
    }

    if (!validate_price($data['price'])) {
        $errors['price'] = 'Price must be a positive number greater than zero.';
    } else {
        $data['price'] = (float) $data['price'];
    }

    if (!validate_stock($data['stock'])) {
        $errors['stock'] = 'Stock must be a non-negative whole number.';
    } else {
        $data['stock'] = (int) $data['stock'];
    }

    if (!validate_in_list($data['category'], ALLOWED_CATEGORIES)) {
        $errors['category'] = 'Invalid category selected.';
    }

    if (!validate_in_list($data['status'], ALLOWED_STATUSES)) {
        $errors['status'] = 'Invalid status selected.';
    }

    return [
        'errors' => $errors,
        'data'   => $data,
    ];
}
