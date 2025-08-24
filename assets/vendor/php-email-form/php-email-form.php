<?php
/**
 * PHP Email Form Class
 * 
 * A simple PHP class for processing and sending email from web forms
 * 
 * @version 1.0
 * @author Sebby Wekesa
 */

class PHP_Email_Form {
    public $to;
    public $from_name;
    public $from_email;
    public $subject;
    public $smtp = array();
    public $ajax = false;
    public $recaptcha = false;
    public $recaptcha_secret_key;
    
    private $messages = array();
    private $errors = array();
    private $headers = array();

    /**
     * Add a message to the email
     *
     * @param string $value The value/content of the message
     * @param string $label The label/description for this value
     * @param int $min_length Minimum length requirement (0 for no requirement)
     */
    public function add_message($value, $label, $min_length = 0) {
        $this->messages[] = array(
            'label' => $label,
            'value' => $value,
            'min_length' => $min_length
        );
    }

    /**
     * Validate form data
     *
     * @return bool True if validation passes, false otherwise
     */
    private function validate() {
        $this->errors = array();

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
        if ($this->recaptcha && !empty($this->recaptcha_secret_key)) {
            if (empty($_POST['g-recaptcha-response'])) {
                $this->errors[] = 'Please complete the reCAPTCHA verification';
            } else {
                $recaptcha_response = $_POST['g-recaptcha-response'];
                $recaptcha_verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$this->recaptcha_secret_key}&response={$recaptcha_response}");
                $recaptcha_data = json_decode($recaptcha_verify);
                
                if (!$recaptcha_data->success) {
                    $this->errors[] = 'reCAPTCHA verification failed';
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Build email headers
     */
    private function build_headers() {
        $this->headers = array();
        
        $this->headers[] = 'From: ' . $this->from_name . ' <' . $this->from_email . '>';
        $this->headers[] = 'Reply-To: ' . $this->from_email;
        $this->headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $this->headers[] = 'X-Mailer: PHP/' . phpversion();
    }

    /**
     * Build email content
     *
     * @return string The formatted email content
     */
    private function build_content() {
        $content = "You have received a new message from your website contact form.\n\n";
        
        foreach ($this->messages as $message) {
            $content .= $message['label'] . ": " . trim($message['value']) . "\n";
        }
        
        $content .= "\n";
        $content .= "---\n";
        $content .= "This email was sent from your website's contact form.\n";
        
        return $content;
    }

    /**
     * Send email using PHP's mail() function
     *
     * @return string Success message or error description
     */
    private function send_via_mail() {
        $content = $this->build_content();
        $headers = implode("\r\n", $this->headers);
        
        if (mail($this->to, $this->subject, $content, $headers)) {
            return 'OK';
        } else {
            return 'Failed to send email using PHP mail() function';
        }
    }

    /**
     * Send email using SMTP
     *
     * @return string Success message or error description
     */
    private function send_via_smtp() {
        // This is a simplified SMTP implementation
        // For production use, consider using a library like PHPMailer
        
        $content = $this->build_content();
        
        // SMTP configuration should be properly set up on the server
        // This is just a placeholder implementation
        
        $headers = implode("\r\n", $this->headers);
        
        // In a real implementation, you would use PHPMailer or similar here
        // For now, we'll fall back to mail()
        return $this->send_via_mail();
    }

    /**
     * Process and send the email
     *
     * @return string Result of the send operation
     */
    public function send() {
        // Validate form data
        if (!$this->validate()) {
            return implode(', ', $this->errors);
        }
        
        // Build headers
        $this->build_headers();
        
        // Send email
        if (!empty($this->smtp) && !empty($this->smtp['host'])) {
            return $this->send_via_smtp();
        } else {
            return $this->send_via_mail();
        }
    }
}
?>