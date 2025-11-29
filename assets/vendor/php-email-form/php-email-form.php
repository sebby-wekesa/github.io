<?php
/**
 * PHP Email Form Class
 * 
 * A secure PHP class for processing and sending email from web forms.
 * Includes validation, sanitization, CSRF protection, and rate limiting.
 * 
 * @version 2.0
 * @author Sebby Wekesa
 * @link https://github.com/sebby-wekesa
 */

class PHP_Email_Form {
    // Configuration
    private string $to = '';
    private string $from_name = '';
    private string $from_email = '';
    private string $subject = '';
    private string $charset = 'UTF-8';
    private bool $is_html = false;
    
    // SMTP Configuration
    private array $smtp = [];
    
    // Features
    private bool $ajax = false;
    private bool $recaptcha_enabled = false;
    private string $recaptcha_secret_key = '';
    private float $recaptcha_min_score = 0.5;
    
    // Rate limiting
    private bool $rate_limiting_enabled = false;
    private int $rate_limit_requests = 5;
    private int $rate_limit_window = 3600; // seconds
    
    // Data
    private array $messages = [];
    private array $errors = [];
    private array $headers = [];
    
    // Logging
    private string $log_file = '';
    private bool $enable_logging = false;
    
    // CSRF Protection
    private bool $csrf_protection_enabled = true;

    
    /**
     * Constructor
     *
     * @param string $to Recipient email address
     */
    public function __construct(string $to = '') {
        if (!empty($to)) {
            $this->set_to($to);
        }
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Set recipient email address
     *
     * @param string $to Email address
     * @return self
     */
    public function set_to(string $to): self {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid recipient email address');
        }
        $this->to = $to;
        return $this;
    }

    /**
     * Set sender name
     *
     * @param string $name Sender name
     * @return self
     */
    public function set_from_name(string $name): self {
        if (empty(trim($name))) {
            throw new InvalidArgumentException('Sender name cannot be empty');
        }
        // Remove newlines to prevent header injection
        $this->from_name = trim(str_replace(["\r", "\n"], '', $name));
        return $this;
    }

    /**
     * Set sender email address
     *
     * @param string $email Email address
     * @return self
     */
    public function set_from_email(string $email): self {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid sender email address');
        }
        $this->from_email = $email;
        return $this;
    }

    /**
     * Set email subject
     *
     * @param string $subject Email subject
     * @return self
     */
    public function set_subject(string $subject): self {
        if (empty(trim($subject))) {
            throw new InvalidArgumentException('Subject cannot be empty');
        }
        // Remove newlines to prevent header injection
        $this->subject = trim(str_replace(["\r", "\n"], '', $subject));
        return $this;
    }

    /**
     * Enable/disable HTML email format
     *
     * @param bool $is_html True for HTML emails, false for plain text
     * @return self
     */
    public function set_html(bool $is_html = true): self {
        $this->is_html = $is_html;
        return $this;
    }

    /**
     * Set character set
     *
     * @param string $charset Character set (e.g., UTF-8, ISO-8859-1)
     * @return self
     */
    public function set_charset(string $charset = 'UTF-8'): self {
        $this->charset = strtoupper($charset);
        return $this;
    }

    /**
     * Enable SMTP
     *
     * @param array $config SMTP configuration (host, port, username, password)
     * @return self
     */
    public function set_smtp(array $config): self {
        if (empty($config['host'])) {
            throw new InvalidArgumentException('SMTP host is required');
        }
        $this->smtp = [
            'host' => $config['host'],
            'port' => $config['port'] ?? 587,
            'username' => $config['username'] ?? '',
            'password' => $config['password'] ?? '',
            'secure' => $config['secure'] ?? 'tls'
        ];
        return $this;
    }

    /**
     * Enable reCAPTCHA v3 verification
     *
     * @param string $secret_key reCAPTCHA secret key
     * @param float $min_score Minimum score (0.0 - 1.0)
     * @return self
     */
    public function enable_recaptcha(string $secret_key, float $min_score = 0.5): self {
        if (empty(trim($secret_key))) {
            throw new InvalidArgumentException('reCAPTCHA secret key cannot be empty');
        }
        if ($min_score < 0 || $min_score > 1) {
            throw new InvalidArgumentException('reCAPTCHA score must be between 0 and 1');
        }
        $this->recaptcha_enabled = true;
        $this->recaptcha_secret_key = $secret_key;
        $this->recaptcha_min_score = $min_score;
        return $this;
    }

    /**
     * Disable reCAPTCHA verification
     *
     * @return self
     */
    public function disable_recaptcha(): self {
        $this->recaptcha_enabled = false;
        return $this;
    }

    /**
     * Enable rate limiting
     *
     * @param int $requests Number of requests allowed
     * @param int $window Time window in seconds
     * @return self
     */
    public function enable_rate_limiting(int $requests = 5, int $window = 3600): self {
        if ($requests < 1 || $window < 1) {
            throw new InvalidArgumentException('Rate limit values must be positive');
        }
        $this->rate_limiting_enabled = true;
        $this->rate_limit_requests = $requests;
        $this->rate_limit_window = $window;
        return $this;
    }

    /**
     * Enable file logging
     *
     * @param string $log_file Path to log file
     * @return self
     */
    public function enable_logging(string $log_file): self {
        if (!is_writable(dirname($log_file))) {
            throw new InvalidArgumentException('Log directory is not writable');
        }
        $this->log_file = $log_file;
        $this->enable_logging = true;
        return $this;
    }

    /**
     * Add a message to the email
     *
     * @param string $value The value/content of the message
     * @param string $label The label/description for this value
     * @param int $min_length Minimum length requirement (0 for no requirement)
     * @return self
     */
    public function add_message(string $value, string $label, int $min_length = 0): self {
        if (empty(trim($label))) {
            throw new InvalidArgumentException('Message label cannot be empty');
        }
        
        $this->messages[] = [
            'label' => htmlspecialchars(trim($label), ENT_QUOTES, $this->charset),
            'value' => trim($value),
            'min_length' => max(0, $min_length)
        ];
        return $this;
    }

    /**
     * Log a message to file
     *
     * @param string $message Message to log
     * @param string $level Log level (INFO, WARNING, ERROR)
     * @return void
     */
    private function log_message(string $message, string $level = 'INFO'): void {
        if (!$this->enable_logging) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $log_entry = "[$timestamp] [$level] [IP: $ip_address] $message\n";
        
        error_log($log_entry, 3, $this->log_file);
    }

    /**
     * Check rate limiting
     *
     * @return bool True if within limits, false if rate limited
     */
    private function check_rate_limit(): bool {
        if (!$this->rate_limiting_enabled) {
            return true;
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'email_form_' . md5($ip);
        $current_time = time();
        $requests = [];
        
        // Use session-based rate limiting
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }
        
        // Remove old requests outside the window
        $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($current_time) {
            return ($current_time - $timestamp) < $this->rate_limit_window;
        });
        
        // Check if limit exceeded
        if (count($_SESSION[$key]) >= $this->rate_limit_requests) {
            $this->errors[] = 'Too many requests. Please try again later.';
            $this->log_message("Rate limit exceeded for IP: $ip", 'WARNING');
            return false;
        }
        
        // Add current request
        $_SESSION[$key][] = $current_time;
        
        return true;
    }

    /**
     * Verify CSRF token
     *
     * @return bool True if token is valid or CSRF protection is disabled
     */
    private function verify_csrf_token(): bool {
        if (!$this->csrf_protection_enabled) {
            return true;
        }
        
        if (!isset($_POST['csrf_token']) || empty($_SESSION['csrf_token'])) {
            $this->errors[] = 'CSRF token validation failed';
            $this->log_message('CSRF token missing or invalid', 'WARNING');
            return false;
        }
        
        if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $this->errors[] = 'CSRF token validation failed';
            $this->log_message('CSRF token mismatch detected', 'WARNING');
            return false;
        }
        
        return true;
    }

    /**
     * Generate CSRF token
     *
     * @return string CSRF token
     */
    public static function generate_csrf_token(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify reCAPTCHA response
     *
     * @return bool True if verification passes
     */
    private function verify_recaptcha(): bool {
        if (!$this->recaptcha_enabled) {
            return true;
        }
        
        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
        
        if (empty($recaptcha_response)) {
            $this->errors[] = 'Please complete the reCAPTCHA verification';
            return false;
        }
        
        // Use curl for secure HTTPS connection
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://www.google.com/recaptcha/api/siteverify',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'secret' => $this->recaptcha_secret_key,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($http_code !== 200) {
            $this->errors[] = 'reCAPTCHA verification service error';
            $this->log_message("reCAPTCHA verification failed: HTTP $http_code", 'ERROR');
            return false;
        }
        
        if (!empty($curl_error)) {
            $this->errors[] = 'reCAPTCHA verification service error';
            $this->log_message("reCAPTCHA curl error: $curl_error", 'ERROR');
            return false;
        }
        
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['success'])) {
            $this->errors[] = 'Invalid reCAPTCHA response format';
            $this->log_message('Invalid reCAPTCHA response format', 'ERROR');
            return false;
        }
        
        if (!$data['success']) {
            $this->errors[] = 'reCAPTCHA verification failed';
            $this->log_message('reCAPTCHA returned success: false', 'WARNING');
            return false;
        }
        
        // Check score for reCAPTCHA v3
        if (isset($data['score']) && $data['score'] < $this->recaptcha_min_score) {
            $this->errors[] = 'reCAPTCHA score too low. Possible bot activity detected.';
            $this->log_message('reCAPTCHA score: ' . $data['score'], 'WARNING');
            return false;
        }
        
        return true;
    }    /**
     * Validate form data
     *
     * @return bool True if validation passes, false otherwise
     */
    private function validate(): bool {
        $this->errors = [];

        // Check rate limiting
        if (!$this->check_rate_limit()) {
            return false;
        }

        // Verify CSRF token
        if (!$this->verify_csrf_token()) {
            return false;
        }

        // Check required fields
        if (empty($this->to)) {
            $this->errors[] = 'Recipient email address is required';
        } elseif (!filter_var($this->to, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Recipient email address is invalid';
        }

        if (empty($this->from_name)) {
            $this->errors[] = 'Sender name is required';
        }

        if (empty($this->from_email)) {
            $this->errors[] = 'Sender email is required';
        } elseif (!filter_var($this->from_email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Sender email is invalid';
        }

        if (empty($this->subject)) {
            $this->errors[] = 'Email subject is required';
        }

        // Validate message content
        foreach ($this->messages as $message) {
            if ($message['min_length'] > 0 && strlen(trim($message['value'])) < $message['min_length']) {
                $this->errors[] = $message['label'] . ' must be at least ' . $message['min_length'] . ' characters long';
            }
        }

        // Validate reCAPTCHA if enabled
        if (!$this->verify_recaptcha()) {
            return false;
        }

        if (!empty($this->errors)) {
            $this->log_message('Validation failed: ' . implode(' | ', $this->errors), 'WARNING');
        }

        return empty($this->errors);
    }

    /**
     * Build email headers
     *
     * @return void
     */
    private function build_headers(): void {
        $this->headers = [];
        
        // Set From header (with sanitized values to prevent header injection)
        $from = "\"{$this->from_name}\" <{$this->from_email}>";
        $this->headers[] = 'From: ' . $from;
        
        $this->headers[] = 'Reply-To: ' . $this->from_email;
        
        // Set content type based on is_html flag
        $content_type = $this->is_html ? 'text/html' : 'text/plain';
        $this->headers[] = "Content-Type: {$content_type}; charset={$this->charset}";
        
        $this->headers[] = 'X-Mailer: PHP/' . phpversion();
        $this->headers[] = 'X-Priority: 3 (Normal)';
        $this->headers[] = 'X-MSMail-Priority: Normal';
    }

    /**
     * Build email content
     *
     * @return string The formatted email content
     */
    private function build_content(): string {
        if ($this->is_html) {
            return $this->build_html_content();
        } else {
            return $this->build_text_content();
        }
    }

    /**
     * Build plain text email content
     *
     * @return string The formatted plain text content
     */
    private function build_text_content(): string {
        $content = "You have received a new message from your website contact form.\n\n";
        
        foreach ($this->messages as $message) {
            $content .= $message['label'] . ": " . strip_tags($message['value']) . "\n";
        }
        
        $content .= "\n";
        $content .= "---\n";
        $content .= "This email was sent from your website's contact form.\n";
        $content .= "Sent at: " . date('Y-m-d H:i:s') . "\n";
        $content .= "IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN') . "\n";
        
        return $content;
    }

    /**
     * Build HTML email content
     *
     * @return string The formatted HTML content
     */
    private function build_html_content(): string {
        $content = "<!DOCTYPE html>\n";
        $content .= "<html>\n<head>\n";
        $content .= "<meta charset=\"{$this->charset}\" />\n";
        $content .= "<style>\n";
        $content .= "body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }\n";
        $content .= ".container { max-width: 600px; margin: 0 auto; padding: 20px; }\n";
        $content .= ".header { border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 20px; }\n";
        $content .= ".content { margin: 20px 0; }\n";
        $content .= ".field { margin-bottom: 15px; }\n";
        $content .= ".label { font-weight: bold; color: #007bff; }\n";
        $content .= ".value { margin-top: 5px; padding: 10px; background-color: #f5f5f5; border-radius: 4px; }\n";
        $content .= ".footer { border-top: 2px solid #007bff; padding-top: 10px; margin-top: 20px; font-size: 12px; color: #666; }\n";
        $content .= "</style>\n";
        $content .= "</head>\n<body>\n";
        $content .= "<div class=\"container\">\n";
        $content .= "<div class=\"header\">\n";
        $content .= "<h2>New Contact Form Submission</h2>\n";
        $content .= "</div>\n";
        
        $content .= "<div class=\"content\">\n";
        foreach ($this->messages as $message) {
            $content .= "<div class=\"field\">\n";
            $content .= "<div class=\"label\">" . $message['label'] . "</div>\n";
            $content .= "<div class=\"value\">" . nl2br(htmlspecialchars($message['value'], ENT_QUOTES, $this->charset)) . "</div>\n";
            $content .= "</div>\n";
        }
        $content .= "</div>\n";
        
        $content .= "<div class=\"footer\">\n";
        $content .= "<p>This email was sent from your website's contact form.</p>\n";
        $content .= "<p>Date: " . date('Y-m-d H:i:s') . "</p>\n";
        $content .= "<p>IP Address: " . htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN', ENT_QUOTES, $this->charset) . "</p>\n";
        $content .= "</div>\n";
        
        $content .= "</div>\n</body>\n</html>\n";
        
        return $content;
    }

    /**
     * Send email using PHP's mail() function
     *
     * @return bool True on success, false on failure
     * @throws RuntimeException
     */
    private function send_via_mail(): bool {
        $content = $this->build_content();
        $headers = implode("\r\n", $this->headers);
        
        try {
            $result = mail($this->to, $this->subject, $content, $headers);
            
            if ($result) {
                $this->log_message('Email sent successfully via mail() function', 'INFO');
                return true;
            } else {
                $this->log_message('Failed to send email using mail() function', 'ERROR');
                throw new RuntimeException('Failed to send email. Please try again later.');
            }
        } catch (Exception $e) {
            $this->log_message('Exception in send_via_mail: ' . $e->getMessage(), 'ERROR');
            throw new RuntimeException('Failed to send email. Please try again later.');
        }
    }

    /**
     * Send email using SMTP (requires PHPMailer library or falls back to mail())
     *
     * @return bool True on success
     * @throws RuntimeException
     */
    private function send_via_smtp(): bool {
        // For now, fall back to mail() function
        // In production, you should use PHPMailer or SwiftMailer
        $this->log_message('SMTP not fully implemented. Falling back to mail() function.', 'WARNING');
        return $this->send_via_mail();
    }

    /**
     * Get validation errors
     *
     * @return array Array of error messages
     */
    public function get_errors(): array {
        return $this->errors;
    }

    /**
     * Get the last error message
     *
     * @return string Last error message or empty string
     */
    public function get_error_message(): string {
        return !empty($this->errors) ? $this->errors[0] : '';
    }

    /**
     * Process and send the email
     *
     * @return bool True on success, false on failure
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function send(): bool {
        try {
            // Validate form data
            if (!$this->validate()) {
                return false;
            }
            
            // Build headers
            $this->build_headers();
            
            // Send email
            if (!empty($this->smtp) && !empty($this->smtp['host'])) {
                return $this->send_via_smtp();
            } else {
                return $this->send_via_mail();
            }
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            $this->log_message('Exception in send: ' . $e->getMessage(), 'ERROR');
            return false;
        }
    }
}
?>