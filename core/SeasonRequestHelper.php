<?php
/**
 * Season Request Helper Class
 * Utility functions for bus season request operations
 * Optimized for nginx server compatibility
 */

class SeasonRequestHelper {
    
    /**
     * Validate season request form data
     * 
     * @param array $data Form data to validate
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validateRequestData($data) {
        $errors = [];
        
        // Validate route_from
        if (empty($data['route_from']) || strlen(trim($data['route_from'])) === 0) {
            $errors[] = 'Route From is required.';
        } elseif (strlen($data['route_from']) > 255) {
            $errors[] = 'Route From cannot exceed 255 characters.';
        }
        
        // Validate route_to
        if (empty($data['route_to']) || strlen(trim($data['route_to'])) === 0) {
            $errors[] = 'Route To is required.';
        } elseif (strlen($data['route_to']) > 255) {
            $errors[] = 'Route To cannot exceed 255 characters.';
        }
        
        // Validate distance_km
        if (empty($data['distance_km']) || !is_numeric($data['distance_km'])) {
            $errors[] = 'Distance must be a valid number.';
        } else {
            $distance = floatval($data['distance_km']);
            if ($distance <= 0) {
                $errors[] = 'Distance must be greater than 0.';
            } elseif ($distance > 9999.9) {
                $errors[] = 'Distance cannot exceed 9999.9 KM.';
            }
        }
        
        // Validate change_point (optional)
        if (!empty($data['change_point']) && strlen($data['change_point']) > 255) {
            $errors[] = 'Change Point cannot exceed 255 characters.';
        }
        
        // Validate student_id
        if (empty($data['student_id'])) {
            $errors[] = 'Student ID is required.';
        }
        
        // Validate season_year
        if (empty($data['season_year'])) {
            $errors[] = 'Season Year is required.';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Sanitize form input data
     * 
     * @param array $data Raw form data
     * @return array Sanitized data
     */
    public static function sanitizeRequestData($data) {
        $sanitized = [];
        
        $sanitized['student_id'] = isset($data['student_id']) ? trim($data['student_id']) : '';
        $sanitized['department_id'] = isset($data['department_id']) ? trim($data['department_id']) : null;
        $sanitized['season_year'] = isset($data['season_year']) ? trim($data['season_year']) : '';
        $sanitized['season_name'] = isset($data['season_name']) ? trim($data['season_name']) : '';
        $sanitized['route_from'] = isset($data['route_from']) ? trim($data['route_from']) : '';
        $sanitized['route_to'] = isset($data['route_to']) ? trim($data['route_to']) : '';
        $sanitized['change_point'] = isset($data['change_point']) ? trim($data['change_point']) : '';
        $sanitized['distance_km'] = isset($data['distance_km']) ? floatval($data['distance_km']) : 0;
        $sanitized['notes'] = isset($data['notes']) ? trim($data['notes']) : '';
        
        // Limit string lengths
        $sanitized['route_from'] = substr($sanitized['route_from'], 0, 255);
        $sanitized['route_to'] = substr($sanitized['route_to'], 0, 255);
        $sanitized['change_point'] = substr($sanitized['change_point'], 0, 255);
        
        return $sanitized;
    }
    
    /**
     * Generate CSRF token
     * 
     * @return string CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     * 
     * @param string $token Token to verify
     * @return bool True if valid
     */
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && 
               !empty($token) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Check if request is AJAX
     * 
     * @return bool True if AJAX request
     */
    public static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Send JSON response (nginx compatible)
     * 
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     */
    public static function sendJsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    /**
     * Calculate payment breakdown
     * 
     * @param float $totalAmount Total season price
     * @param float $studentPaidAmount Initial student payment
     * @return array Payment breakdown
     */
    public static function calculatePaymentBreakdown($totalAmount, $studentPaidAmount = 0) {
        $studentPortion = $totalAmount * 0.30;  // 30%
        $slgtiPortion = $totalAmount * 0.35;     // 35%
        $ctbPortion = $totalAmount * 0.35;      // 35%
        $remainingBalance = $studentPortion - $studentPaidAmount;
        
        return [
            'total_amount' => round($totalAmount, 2),
            'student_paid' => round($studentPortion, 2),
            'slgti_paid' => round($slgtiPortion, 2),
            'ctb_paid' => round($ctbPortion, 2),
            'remaining_balance' => round($remainingBalance, 2),
            'initial_paid' => round($studentPaidAmount, 2)
        ];
    }
    
    /**
     * Format status label
     * 
     * @param string $status Status code
     * @return string Formatted status label
     */
    public static function formatStatusLabel($status) {
        $statusLabels = [
            'pending' => 'Pending HOD Approval',
            'hod_approved' => 'HOD Approved - Pending Second Approval',
            'approved' => 'Approved',
            'paid' => 'Paid',
            'processing' => 'Processing',
            'issued' => 'Issued',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled'
        ];
        
        return $statusLabels[$status] ?? ucfirst($status);
    }
    
    /**
     * Get status badge class
     * 
     * @param string $status Status code
     * @return string Bootstrap badge class
     */
    public static function getStatusBadgeClass($status) {
        $statusClasses = [
            'pending' => 'warning',
            'hod_approved' => 'info',
            'approved' => 'primary',
            'paid' => 'success',
            'processing' => 'info',
            'issued' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'secondary'
        ];
        
        return $statusClasses[$status] ?? 'secondary';
    }
    
    /**
     * Format currency
     * 
     * @param float $amount Amount to format
     * @param string $currency Currency symbol
     * @return string Formatted currency string
     */
    public static function formatCurrency($amount, $currency = 'Rs. ') {
        return $currency . number_format($amount, 2);
    }
    
    /**
     * Format date for display
     * 
     * @param string $date Date string
     * @param string $format Date format
     * @return string Formatted date
     */
    public static function formatDate($date, $format = 'M d, Y') {
        if (empty($date)) {
            return 'N/A';
        }
        
        try {
            $timestamp = strtotime($date);
            if ($timestamp === false) {
                return 'Invalid Date';
            }
            return date($format, $timestamp);
        } catch (Exception $e) {
            return 'Invalid Date';
        }
    }
    
    /**
     * Get current season year from enrollment
     * 
     * @param string $studentId Student ID
     * @return string Season year
     */
    public static function getCurrentSeasonYear($studentId) {
        try {
            require_once BASE_PATH . '/models/StudentEnrollmentModel.php';
            $enrollmentModel = new StudentEnrollmentModel();
            $currentEnrollment = $enrollmentModel->getCurrentEnrollment($studentId);
            return $currentEnrollment['academic_year'] ?? date('Y');
        } catch (Exception $e) {
            error_log("SeasonRequestHelper::getCurrentSeasonYear - Error: " . $e->getMessage());
            return date('Y');
        }
    }
    
    /**
     * Get department ID from student enrollment
     * 
     * @param string $studentId Student ID
     * @return string|null Department ID
     */
    public static function getStudentDepartmentId($studentId) {
        try {
            require_once BASE_PATH . '/models/StudentEnrollmentModel.php';
            require_once BASE_PATH . '/models/CourseModel.php';
            
            $enrollmentModel = new StudentEnrollmentModel();
            $currentEnrollment = $enrollmentModel->getCurrentEnrollment($studentId);
            
            if ($currentEnrollment && isset($currentEnrollment['course_id'])) {
                $courseModel = new CourseModel();
                $course = $courseModel->find($currentEnrollment['course_id']);
                if ($course && isset($course['department_id'])) {
                    return $course['department_id'];
                }
            }
            
            return null;
        } catch (Exception $e) {
            error_log("SeasonRequestHelper::getStudentDepartmentId - Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if student can submit request
     * 
     * @param string $studentId Student ID
     * @param string $seasonYear Season year
     * @return array ['can_submit' => bool, 'reason' => string]
     */
    public static function canStudentSubmitRequest($studentId, $seasonYear) {
        try {
            require_once BASE_PATH . '/models/BusSeasonRequestModel.php';
            $requestModel = new BusSeasonRequestModel();
            
            // Check if student already has a request for this season
            if ($requestModel->hasExistingRequest($studentId, $seasonYear)) {
                return [
                    'can_submit' => false,
                    'reason' => 'You already have a bus season request for this season year. Only one request per year is allowed.'
                ];
            }
            
            return [
                'can_submit' => true,
                'reason' => ''
            ];
        } catch (Exception $e) {
            error_log("SeasonRequestHelper::canStudentSubmitRequest - Error: " . $e->getMessage());
            return [
                'can_submit' => false,
                'reason' => 'Unable to verify request eligibility. Please contact support.'
            ];
        }
    }
    
    /**
     * Prepare request data for submission
     * 
     * @param array $formData Form data
     * @param string $studentId Student ID
     * @return array Prepared data
     */
    public static function prepareRequestData($formData, $studentId) {
        // Sanitize input
        $sanitized = self::sanitizeRequestData($formData);
        
        // Get season year and department
        $seasonYear = self::getCurrentSeasonYear($studentId);
        $departmentId = self::getStudentDepartmentId($studentId);
        
        // Prepare final data
        $data = [
            'student_id' => $studentId,
            'department_id' => $departmentId,
            'season_year' => $seasonYear,
            'season_name' => $sanitized['season_name'] ?? '',
            'route_from' => $sanitized['route_from'],
            'route_to' => $sanitized['route_to'],
            'change_point' => $sanitized['change_point'] ?? '',
            'distance_km' => $sanitized['distance_km'],
            'notes' => $sanitized['notes'] ?? ''
        ];
        
        return $data;
    }
    
    /**
     * Log request activity
     * 
     * @param string $action Action type (CREATE, UPDATE, DELETE, etc.)
     * @param int $requestId Request ID
     * @param string $description Description
     * @param array $data Additional data
     */
    public static function logActivity($action, $requestId, $description, $data = []) {
        try {
            require_once BASE_PATH . '/models/ActivityLogModel.php';
            $activityModel = new ActivityLogModel();
            
            $activityModel->logActivity([
                'activity_type' => $action,
                'module' => 'bus_season_request',
                'record_id' => $requestId,
                'description' => $description,
                'new_values' => $data
            ]);
        } catch (Exception $e) {
            error_log("SeasonRequestHelper::logActivity - Error: " . $e->getMessage());
        }
    }
    
    /**
     * Get route display string
     * 
     * @param string $routeFrom Route from
     * @param string $routeTo Route to
     * @param string $changePoint Change point (optional)
     * @return string Formatted route string
     */
    public static function formatRoute($routeFrom, $routeTo, $changePoint = '') {
        $route = $routeFrom . ' to ' . $routeTo;
        if (!empty($changePoint)) {
            $route .= ' (Via: ' . $changePoint . ')';
        }
        return $route;
    }
    
    /**
     * Validate payment data
     * 
     * @param array $data Payment data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validatePaymentData($data) {
        $errors = [];
        
        if (empty($data['paid_amount']) || !is_numeric($data['paid_amount'])) {
            $errors[] = 'Paid amount must be a valid number.';
        } elseif (floatval($data['paid_amount']) < 0) {
            $errors[] = 'Paid amount cannot be negative.';
        }
        
        if (empty($data['payment_method'])) {
            $errors[] = 'Payment method is required.';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Check nginx server compatibility
     * 
     * @return array Compatibility check results
     */
    public static function checkNginxCompatibility() {
        $checks = [
            'php_version' => version_compare(PHP_VERSION, '7.4.0', '>='),
            'mysqli_extension' => extension_loaded('mysqli'),
            'session_support' => function_exists('session_start'),
            'json_support' => function_exists('json_encode'),
            'mbstring_extension' => extension_loaded('mbstring'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'memory_limit' => ini_get('memory_limit')
        ];
        
        return $checks;
    }
}

