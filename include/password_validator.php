<?php
/**
 * SwineGuard Password Validator Module
 * Enforces strong security requirements for user accounts.
 */

if (!function_exists('validate_password_strength')) {
    /**
     * Validate a password against security rules.
     *
     * @param string $password The password to validate
     * @param string $username Optional username to check against
     * @param string $fullName Optional full name to check against
     * @return array ['valid' => bool, 'errors' => array, 'message' => string]
     */
    function validate_password_strength($password, $username = '', $fullName = '')
    {
        $errors = [];
        $password = (string)$password;

        // 1. Minimum length (8+ characters)
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }

        // 2. Requires uppercase and lowercase letters
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain both uppercase and lowercase letters.";
        }

        // 3. Requires at least one number
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number.";
        }

        // 4. Requires at least one special character
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character (e.g. !@#$%^&*()_+-=).";
        }

        // 5. Doesn't match common/weak passwords
        $commonWeakPasswords = [
            '123456', 'password', '12345678', 'qwerty', '123456789', '12345', '1234',
            '111111', '1234567', 'dragon', 'welcome', 'admin', 'admin123', 'admin888',
            'swineguard', 'pass1234', 'password123', 'iloveyou', 'sunshine', 'princess',
            'monkey', 'shadow', 'master', 'football', 'baseball', 'superman', 'trustno1',
            'letmein', 'login', 'p@ssword', 'p@ssw0rd', 'password1', '123123', 'root'
        ];

        $lowerPassword = strtolower($password);
        if (in_array($lowerPassword, $commonWeakPasswords, true)) {
            $errors[] = "Password is too common or easily guessed. Please choose a stronger password.";
        }

        // 6. Doesn't contain username or personal info
        if (!empty($username)) {
            $cleanUsername = strtolower(trim($username));
            if (strlen($cleanUsername) >= 3 && strpos($lowerPassword, $cleanUsername) !== false) {
                $errors[] = "Password must not contain your username.";
            }
        }

        if (!empty($fullName)) {
            $cleanFullName = strtolower(trim($fullName));
            // Split name into parts (first name, middle name, last name)
            $nameParts = preg_split('/[\s,\.]+/', $cleanFullName);
            foreach ($nameParts as $part) {
                $part = trim($part);
                if (strlen($part) >= 3 && strpos($lowerPassword, $part) !== false) {
                    $errors[] = "Password must not contain your name or personal information.";
                    break;
                }
            }
        }

        return [
            'valid'   => empty($errors),
            'errors'  => $errors,
            'message' => !empty($errors) ? implode(' ', $errors) : ''
        ];
    }
}
