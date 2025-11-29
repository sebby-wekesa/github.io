<?php
/**
 * Contact Form Handler
 * 
 * Handles form submissions from the contact form with validation,
 * security checks, and email sending.
 * 
 * @version 2.0
 */

// Enable error reporting (disable in production if not needed)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to users

// Set headers for AJAX response
header('Content-Type: application/json; charset=utf-8');

// Configuration
$receiving_email_address = 'sebbywakis@gmail.com';
$php_email_form_path = '../assets/vendor/php-email-form/php-email-form.php';

// Response variables
$response = [
    'success' => false,
    'message' => ''
];

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method. POST required.');
    }

    // Check if email form library exists
    if (!file_exists($php_email_form_path)) {
        throw new Exception('Unable to load the PHP Email Form library!');
    }

    // Include the email form library
    include($php_email_form_path);

    // Validate that all required POST fields are present
    $required_fields = ['name', 'email', 'subject', 'message'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception(ucfirst($field) . ' field is required.');
        }
    }

    // Sanitize and validate input
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    // Validate name length
    if (strlen($name) < 2) {
        throw new Exception('Name must be at least 2 characters long.');
    }
    if (strlen($name) > 100) {
        throw new Exception('Name must not exceed 100 characters.');
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please provide a valid email address.');
    }

    // Validate subject length
    if (strlen($subject) < 3) {
        throw new Exception('Subject must be at least 3 characters long.');
    }
    if (strlen($subject) > 200) {
        throw new Exception('Subject must not exceed 200 characters.');
    }

    // Validate message length
    if (strlen($message) < 10) {
        throw new Exception('Message must be at least 10 characters long.');
    }
    if (strlen($message) > 5000) {
        throw new Exception('Message must not exceed 5000 characters.');
    }

    // Check if message looks like spam (very basic check)
    $spam_keywords = ['viagra', 'casino', 'lottery', 'prize', 'click here', 'buy now'];
    $message_lower = strtolower($message);
    foreach ($spam_keywords as $keyword) {
        if (strpos($message_lower, $keyword) !== false) {
            throw new Exception('Your message contains suspicious content. Please review and resubmit.');
        }
    }

    // Initialize the email form handler
    $contact = new PHP_Email_Form($receiving_email_address);

    // Configure the email handler with the new improved API
    $contact->set_from_name($name)
            ->set_from_email($email)
            ->set_subject($subject)
            ->set_html(true)
            ->enable_rate_limiting(5, 3600) // 5 requests per hour
            ->enable_logging(__DIR__ . '/email_form.log');

    // Optional: Enable reCAPTCHA if you have a secret key
    // Uncomment and set your reCAPTCHA secret key
    /*
    $recaptcha_secret_key = 'YOUR_RECAPTCHA_SECRET_KEY_HERE';
    if (!empty($recaptcha_secret_key)) {
        $contact->enable_recaptcha($recaptcha_secret_key, 0.5);
    }
    */

    // Optional: Configure SMTP if you want to use it instead of PHP mail()
    // Uncomment and fill in your SMTP credentials
    /*
    $contact->set_smtp([
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'your-email@gmail.com',
        'password' => 'your-app-password',
        'secure' => 'tls'
    ]);
    */

    // Add form messages with validation
    $contact->add_message($name, 'From', 2)
            ->add_message($email, 'Email', 5)
            ->add_message($message, 'Message', 10);

    // Send the email
    if ($contact->send()) {
        $response['success'] = true;
        $response['message'] = 'Thank you! Your message has been sent successfully. I will get back to you as soon as possible.';
        http_response_code(200);
    } else {
        // If send() returned false, get the error details
        $errors = $contact->get_errors();
        $error_message = !empty($errors) ? reset($errors) : 'Failed to send email. Please try again later.';
        throw new Exception($error_message);
    }

} catch (InvalidArgumentException $e) {
    // Handle validation errors from the email form class
    $response['success'] = false;
    $response['message'] = 'Validation Error: ' . $e->getMessage();
    http_response_code(400);
} catch (RuntimeException $e) {
    // Handle sending errors
    $response['success'] = false;
    $response['message'] = 'Error sending email: ' . $e->getMessage();
    http_response_code(500);
} catch (Exception $e) {
    // Handle any other errors
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

// Return JSON response
echo json_encode($response);
?>
