<?php
// Set content type to JSON
header('Content-Type: application/json');

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Initialize response array
$response = array('success' => false, 'message' => '');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Method not allowed';
    echo json_encode($response);
    exit;
}

// Validation rules
$validation_rules = array(
    'name' => array(
        'required' => true,
        'min_length' => 2,
        'pattern' => '/^[a-zA-Z\s]+$/',
        'label' => 'Nama'
    ),
    'email' => array(
        'required' => true,
        'pattern' => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
        'label' => 'Email'
    ),
    'phone' => array(
        'required' => false,
        'pattern' => '/^[\+]?[0-9\s\-$$$$]+$/',
        'label' => 'No. Telepon'
    ),
    'subject' => array(
        'required' => true,
        'min_length' => 5,
        'label' => 'Subject'
    ),
    'message' => array(
        'required' => true,
        'min_length' => 10,
        'label' => 'Pesan'
    )
);

// Function to validate field
function validate_field($field_name, $value, $rules) {
    $errors = array();
    
    // Check if required
    if ($rules['required'] && empty(trim($value))) {
        $errors[] = $rules['label'] . ' wajib diisi';
        return $errors;
    }
    
    // Skip other validations if field is empty and not required
    if (empty(trim($value)) && !$rules['required']) {
        return $errors;
    }
    
    // Check minimum length
    if (isset($rules['min_length']) && strlen(trim($value)) < $rules['min_length']) {
        $errors[] = $rules['label'] . ' minimal ' . $rules['min_length'] . ' karakter';
    }
    
    // Check pattern
    if (isset($rules['pattern']) && !preg_match($rules['pattern'], trim($value))) {
        $errors[] = 'Format ' . $rules['label'] . ' tidak valid';
    }
    
    return $errors;
}

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validate all fields
$errors = array();
$sanitized_data = array();

foreach ($validation_rules as $field_name => $rules) {
    $value = isset($_POST[$field_name]) ? $_POST[$field_name] : '';
    $field_errors = validate_field($field_name, $value, $rules);
    
    if (!empty($field_errors)) {
        $errors[$field_name] = $field_errors;
    } else {
        $sanitized_data[$field_name] = sanitize_input($value);
    }
}

// If there are validation errors
if (!empty($errors)) {
    $response['message'] = 'Data tidak valid';
    $response['errors'] = $errors;
    echo json_encode($response);
    exit;
}

// Additional security checks
if (strlen($sanitized_data['message']) > 1000) {
    $response['message'] = 'Pesan terlalu panjang (maksimal 1000 karakter)';
    echo json_encode($response);
    exit;
}

// Check for spam patterns
$spam_patterns = array(
    '/\b(viagra|cialis|casino|poker|lottery|winner|congratulations)\b/i',
    '/\b(click here|visit now|act now|limited time)\b/i',
    '/(http|https|www\.)/i'
);

foreach ($spam_patterns as $pattern) {
    if (preg_match($pattern, $sanitized_data['message']) || 
        preg_match($pattern, $sanitized_data['subject'])) {
        $response['message'] = 'Pesan terdeteksi sebagai spam';
        echo json_encode($response);
        exit;
    }
}

// Rate limiting (simple implementation)
session_start();
$current_time = time();
$rate_limit_key = 'last_contact_' . $_SERVER['REMOTE_ADDR'];

if (isset($_SESSION[$rate_limit_key])) {
    $time_diff = $current_time - $_SESSION[$rate_limit_key];
    if ($time_diff < 60) { // 1 minute rate limit
        $response['message'] = 'Mohon tunggu ' . (60 - $time_diff) . ' detik sebelum mengirim pesan lagi';
        echo json_encode($response);
        exit;
    }
}

$_SESSION[$rate_limit_key] = $current_time;

// Prepare email content
$to = 'refva.lena@webmail.uad.ac.id'; // Your email address
$email_subject = 'Pesan Baru dari Portfolio: ' . $sanitized_data['subject'];

$email_body = "
Pesan baru dari portfolio website:

Nama: " . $sanitized_data['name'] . "
Email: " . $sanitized_data['email'] . "
Telepon: " . (!empty($sanitized_data['phone']) ? $sanitized_data['phone'] : 'Tidak disediakan') . "
Subject: " . $sanitized_data['subject'] . "

Pesan:
" . $sanitized_data['message'] . "

---
Dikirim pada: " . date('Y-m-d H:i:s') . "
IP Address: " . $_SERVER['REMOTE_ADDR'] . "
User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "
";

// Email headers
$headers = array(
    'From: Portfolio Website <noreply@portfolio.com>',
    'Reply-To: ' . $sanitized_data['email'],
    'X-Mailer: PHP/' . phpversion(),
    'Content-Type: text/plain; charset=UTF-8'
);

// Try to send email
$mail_sent = false;

// Method 1: Try using mail() function
if (function_exists('mail')) {
    $mail_sent = mail($to, $email_subject, $email_body, implode("\r\n", $headers));
}

// Method 2: If mail() fails, save to file (for development/testing)
if (!$mail_sent) {
    $log_file = 'contact_messages.txt';
    $log_entry = "
=== PESAN BARU ===
Tanggal: " . date('Y-m-d H:i:s') . "
Nama: " . $sanitized_data['name'] . "
Email: " . $sanitized_data['email'] . "
Telepon: " . (!empty($sanitized_data['phone']) ? $sanitized_data['phone'] : 'Tidak disediakan') . "
Subject: " . $sanitized_data['subject'] . "
Pesan: " . $sanitized_data['message'] . "
IP: " . $_SERVER['REMOTE_ADDR'] . "
==================

";
    
    if (file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX)) {
        $mail_sent = true;
    }
}


// Send response
if ($mail_sent) {
    $response['success'] = true;
    $response['message'] = 'Pesan berhasil dikirim! Terima kasih telah menghubungi saya.';
    
    // Log successful submission
    error_log("Contact form submitted successfully by: " . $sanitized_data['email']);
} else {
    $response['message'] = 'Maaf, terjadi kesalahan saat mengirim pesan. Silakan coba lagi nanti.';
    
    // Log error
    error_log("Failed to send contact form from: " . $sanitized_data['email']);
}

echo json_encode($response);
?>
