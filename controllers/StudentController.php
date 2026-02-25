<?php
/**
 * Student Controller
 */

class StudentController extends Controller {
    
    public function index() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict student users - they should use the student portal
        if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $_SESSION['error'] = 'Access denied. Students cannot access this page.';
            $this->redirect('student/dashboard');
            return;
        }
        
        $studentModel = $this->model('StudentModel');
        $departmentModel = $this->model('DepartmentModel');
        $courseModel = $this->model('CourseModel');
        
        $page = $this->get('page', 1);
        
        // Get user's department if user is HOD, IN1, IN2, or IN3
        $userDepartmentId = $this->getUserDepartment();
        $isDepartmentRestricted = $this->isDepartmentRestricted();
        
        // HOD, IN1, IN2, IN3: load full students with student_status = Active only; no other filters (district, gender, course, etc.)
        if ($isDepartmentRestricted) {
            $filters = [
                'search' => $this->get('search', ''),
                'status' => 'Active',
                'department_id' => $userDepartmentId ?: '',
                'district' => '',
                'gender' => '',
                'course_id' => '',
                'academic_year' => '',
                'course_mode' => '',
                'group_id' => ''
            ];
        } else {
            $filters = [
                'search' => $this->get('search', ''),
                'status' => $this->get('status', ''),
                'district' => $this->get('district', ''),
                'gender' => $this->get('gender', ''),
                'department_id' => $userDepartmentId ? $userDepartmentId : $this->get('department_id', ''),
                'course_id' => $this->get('course_id', ''),
                'academic_year' => $this->get('academic_year', ''),
                'course_mode' => $this->get('course_mode', ''),
                'group_id' => $this->get('group_id', '')
            ];
        }
        
        $students = $studentModel->getStudents($page, 20, $filters);
        $total = $studentModel->getTotalStudents($filters);
        $totalPages = ceil($total / 20);
        $districts = $studentModel->getDistricts();
        
        // For department-restricted users (HOD, IN1, IN2, IN3), only show their department
        if ($userDepartmentId) {
            $departments = [$departmentModel->getById($userDepartmentId)];
            $departments = array_filter($departments); // Remove null if department not found
        } else {
            $departments = $departmentModel->getAll();
        }
        
        // Filter courses by department if user is department-restricted
        if ($userDepartmentId) {
            $courses = $courseModel->getCoursesWithDepartment(['department_id' => $userDepartmentId]);
        } else {
            $courses = $courseModel->all('course_name ASC');
        }
        $academicYears = $studentModel->getAcademicYears();
        
        // Check if user is ADM or SAO for export and edit features
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isSAO = $userModel->isSAO($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM');
        $isHOD = $this->isHOD();
        $canExport = $isADM || $isSAO || $isHOD;
        $canEdit = $isADM || $isSAO; // Only SAO and ADM can add, edit, delete students
        
        $data = [
            'title' => 'Students',
            'page' => 'students',
            'students' => $students,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'filters' => $filters,
            'districts' => $districts,
            'departments' => $departments,
            'courses' => $courses,
            'academicYears' => $academicYears,
            'statuses' => ['Active', 'Inactive', 'Graduated'],
            'genders' => ['Male', 'Female'],
            'isHOD' => $isDepartmentRestricted,
            'canExport' => $canExport,
            'isADM' => $isADM,
            'canEdit' => $canEdit,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('students/index', $data);
    }
    
    public function show() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        $id = $this->get('id', '');
        
        if (empty($id)) {
            $_SESSION['error'] = 'Student ID is required.';
            $this->redirect('students');
            return;
        }
        
        $studentModel = $this->model('StudentModel');
        $student = $studentModel->find($id);
        
        if (!$student) {
            $_SESSION['error'] = 'Student not found.';
            $this->redirect('students');
            return;
        }
        
        // Get enrollment information
        $enrollmentModel = $this->model('StudentEnrollmentModel');
        $enrollments = $enrollmentModel->getByStudentId($id);
        $currentEnrollment = $enrollmentModel->getCurrentEnrollment($id);
        
        // Get hostel information
        // Note: Hostel allocation information is visible to all authorized users including:
        // FIN, ACC, HOD, IN1, IN2, IN3, SAO, ADM, DIR, REG, and Admin
        $roomAllocationModel = $this->model('RoomAllocationModel');
        $hostelAllocation = $roomAllocationModel->getActiveByStudentId($id);
        
        // Get all allocations (history) for this student
        $filters = ['student_id' => $id];
        $hostelHistory = $roomAllocationModel->getAllocations(1, 100, $filters);
        
        // Get payment information
        // Note: Payment information is visible to all authorized users including:
        // FIN, ACC, HOD, IN1, IN2, IN3, SAO, ADM, DIR, REG, and Admin
        $paymentModel = $this->model('PaymentModel');
        $payments = $paymentModel->getByStudentId($id);
        
        // Calculate payment statistics
        $totalPayments = count($payments);
        $totalAmount = 0;
        $approvedAmount = 0;
        $pendingAmount = 0;
        foreach ($payments as $payment) {
            $amount = floatval($payment['pays_amount'] ?? 0);
            $totalAmount += $amount;
            if (!empty($payment['approved']) && $payment['approved'] == 1) {
                $approvedAmount += $amount;
            } else {
                $pendingAmount += $amount;
            }
        }
        
        // Check if user is SAO or ADM for edit/reset access
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isSAO = $userModel->isSAO($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM');
        $canEdit = $isSAO || $isADM;
        
        $data = [
            'title' => 'Student Details',
            'page' => 'students',
            'student' => $student,
            'enrollments' => $enrollments,
            'currentEnrollment' => $currentEnrollment,
            'hostelAllocation' => $hostelAllocation,
            'hostelHistory' => $hostelHistory,
            'hasHostel' => !empty($hostelAllocation),
            'payments' => $payments,
            'paymentStats' => [
                'total' => $totalPayments,
                'totalAmount' => $totalAmount,
                'approvedAmount' => $approvedAmount,
                'pendingAmount' => $pendingAmount
            ],
            'canEdit' => $canEdit
        ];
        
        return $this->view('students/view', $data);
    }
    
    /**
     * Show student's own profile (for student portal)
     */
    public function showStudentProfile() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Check if user is a student
        if (!isset($_SESSION['user_table']) || $_SESSION['user_table'] !== 'student') {
            $this->redirect('dashboard');
            return;
        }
        
        $studentId = $_SESSION['user_name'];
        $studentModel = $this->model('StudentModel');
        $student = $studentModel->find($studentId);
        
        if (!$student) {
            $_SESSION['error'] = 'Student record not found.';
            $this->redirect('logout');
            return;
        }
        
        // Get enrollment information
        $enrollmentModel = $this->model('StudentEnrollmentModel');
        $enrollments = $enrollmentModel->getByStudentId($studentId);
        $currentEnrollment = $enrollmentModel->getCurrentEnrollment($studentId);
        
        // Get hostel information
        $roomAllocationModel = $this->model('RoomAllocationModel');
        $hostelAllocation = $roomAllocationModel->getActiveByStudentId($studentId);
        
        $data = [
            'title' => 'My Profile',
            'page' => 'student-profile',
            'student' => $student,
            'enrollments' => $enrollments,
            'currentEnrollment' => $currentEnrollment,
            'hostelAllocation' => $hostelAllocation,
            'hasHostel' => !empty($hostelAllocation)
        ];
        
        return $this->view('students/view', $data);
    }
    
    /**
     * Edit student's own profile (for student portal)
     * Students can edit personal information and bank details, but NOT photos or enrollment
     */
    public function editStudentProfile() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Check if user is a student
        if (!isset($_SESSION['user_table']) || $_SESSION['user_table'] !== 'student') {
            $_SESSION['error'] = 'Access denied. Only students can edit their own profile.';
            $this->redirect('dashboard');
            return;
        }
        
        $studentId = $_SESSION['user_name'];
        $studentModel = $this->model('StudentModel');
        $student = $studentModel->find($studentId);
        
        if (!$student) {
            $_SESSION['error'] = 'Student record not found.';
            $this->redirect('logout');
            return;
        }
        
        // Handle POST request for updates
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $updateSection = $this->post('update_section', '');
            $data = [];
            $validationRequired = false;
            $successMessage = '';
            
            // Handle updates based on section
            if ($updateSection === 'personal') {
                // Personal Information fields only (NO PHOTOS)
                $data = [
                    'student_title' => trim($this->post('student_title', '')),
                    'student_fullname' => trim($this->post('student_fullname', '')),
                    'student_ininame' => trim($this->post('student_ininame', '')),
                    'student_gender' => trim($this->post('student_gender', '')),
                    'student_civil' => trim($this->post('student_civil', '')),
                    'student_email' => trim($this->post('student_email', '')),
                    'student_nic' => trim($this->post('student_nic', '')),
                    'student_dob' => trim($this->post('student_dob', '')),
                    'student_phone' => trim($this->post('student_phone', '')),
                    'student_address' => trim($this->post('student_address', '')),
                    'student_zip' => trim($this->post('student_zip', '')),
                    'student_district' => trim($this->post('student_district', '')),
                    'student_divisions' => trim($this->post('student_divisions', '')),
                    'student_provice' => trim($this->post('student_provice', '')),
                    'student_blood' => trim($this->post('student_blood', '')),
                    'student_em_name' => trim($this->post('student_em_name', '')),
                    'student_em_address' => trim($this->post('student_em_address', '')),
                    'student_em_phone' => trim($this->post('student_em_phone', '')),
                    'student_em_relation' => trim($this->post('student_em_relation', '')),
                    'student_nationality' => trim($this->post('student_nationality', '')),
                    'student_whatsapp' => trim($this->post('student_whatsapp', '')),
                    'student_religion' => trim($this->post('student_religion', ''))
                ];
                // Note: student_status is NOT included - students cannot change their status
                $validationRequired = true;
                $successMessage = 'Personal information updated successfully.';
                
            } elseif ($updateSection === 'bank') {
                // Bank Details fields only
                $data = [
                    'bank_name' => trim($this->post('bank_name', '')),
                    'bank_account_no' => trim($this->post('bank_account_no', '')),
                    'bank_branch' => trim($this->post('bank_branch', ''))
                ];
                $successMessage = 'Bank details updated successfully.';
            } elseif ($updateSection === 'documents') {
                // Handle PDF document upload
                $successMessage = 'Documents uploaded successfully.';
                $data = [];
                
                // Ensure student_documents_pdf column exists
                $studentModel->addStudentDocumentsPdfColumnIfNotExists();
                
                // Check if file was uploaded
                if (!isset($_FILES['student_documents_pdf'])) {
                    $_SESSION['error'] = 'No file was uploaded. Please check Nginx configuration (client_max_body_size) and PHP settings (upload_max_filesize, post_max_size).';
                    $_SESSION['active_tab'] = 'documents';
                    $this->redirect('student/profile/edit');
                    return;
                }
                
                $file = $_FILES['student_documents_pdf'];
                
                // Check for upload errors
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds PHP upload_max_filesize limit. Please increase upload_max_filesize in php.ini.',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE limit.',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded. Please check Nginx client_max_body_size setting.',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder. Please check PHP temp directory.',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk. Please check directory permissions.',
                        UPLOAD_ERR_EXTENSION => 'File upload stopped by PHP extension.',
                    ];
                    
                    $errorMsg = $errorMessages[$file['error']] ?? 'Unknown upload error (Code: ' . $file['error'] . ').';
                    
                    // Additional Nginx-specific check
                    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                        $errorMsg .= ' Also check Nginx client_max_body_size setting (should be at least 10M).';
                    }
                    
                    $_SESSION['error'] = $errorMsg;
                    $_SESSION['active_tab'] = 'documents';
                    $this->redirect('student/profile/edit');
                    return;
                }
                
                $file = $_FILES['student_documents_pdf'];
                
                // Validate file type
                $allowedMimeTypes = ['application/pdf'];
                $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if ($fileExtension !== 'pdf' || !in_array($file['type'], $allowedMimeTypes)) {
                    $_SESSION['error'] = 'Only PDF files are allowed.';
                    $_SESSION['active_tab'] = 'documents';
                    $this->redirect('student/profile/edit');
                    return;
                }
                
                // Check file size (max 10MB before compression)
                $maxSize = 10 * 1024 * 1024; // 10MB
                if ($file['size'] > $maxSize) {
                    $_SESSION['error'] = 'File size exceeds 10MB limit. Please compress the PDF before uploading.';
                    $_SESSION['active_tab'] = 'documents';
                    $this->redirect('student/profile/edit');
                    return;
                }
                
                // Ensure assets directory exists and is writable
                $assetsDirectory = BASE_PATH . '/assets';
                $assetsCheck = $this->ensureDirectoryWritable($assetsDirectory, 'assets');
                if (!$assetsCheck['success']) {
                    $_SESSION['error'] = $assetsCheck['message'];
                    $_SESSION['active_tab'] = 'documents';
                    $this->redirect('student/profile/edit');
                    return;
                }
                
                // Create studentdoc directory if it doesn't exist and ensure it's writable
                $docDirectory = BASE_PATH . '/assets/studentdoc';
                $docCheck = $this->ensureDirectoryWritable($docDirectory, 'assets/studentdoc');
                if (!$docCheck['success']) {
                    $_SESSION['error'] = $docCheck['message'];
                    $_SESSION['active_tab'] = 'documents';
                    $this->redirect('student/profile/edit');
                    return;
                }
                
                // Generate filename: student_id.pdf
                $safeStudentId = preg_replace('/[^a-zA-Z0-9._-]/', '_', $studentId);
                $newFilename = $safeStudentId . '.pdf';
                $targetPath = $docDirectory . '/' . $newFilename;
                
                // Delete old file if exists
                if (file_exists($targetPath)) {
                    @unlink($targetPath);
                }
                
                // Move uploaded file temporarily
                $tempPath = $docDirectory . '/temp_' . $newFilename;
                
                // Check if we can write to the directory
                if (!is_writable($docDirectory)) {
                    $_SESSION['error'] = 'Cannot write to documents directory. Please contact administrator to set proper permissions.';
                    $_SESSION['active_tab'] = 'documents';
                    $this->redirect('student/profile/edit');
                    return;
                }
                
                // Try to move the uploaded file
                if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
                    $errorMsg = 'Failed to upload file. ';
                    switch ($file['error']) {
                        case UPLOAD_ERR_INI_SIZE:
                        case UPLOAD_ERR_FORM_SIZE:
                            $errorMsg .= 'File size exceeds limit.';
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $errorMsg .= 'File was only partially uploaded.';
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $errorMsg .= 'No file was uploaded.';
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $errorMsg .= 'Missing temporary folder.';
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $errorMsg .= 'Failed to write file to disk. Please check directory permissions.';
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            $errorMsg .= 'File upload stopped by extension.';
                            break;
                        default:
                            $errorMsg .= 'Please check directory permissions (755 or 775 required).';
                    }
                    $_SESSION['error'] = $errorMsg;
                    $_SESSION['active_tab'] = 'documents';
                    $this->redirect('student/profile/edit');
                    return;
                }
                
                // Compress PDF to 1MB or less
                $compressed = $this->compressPdf($tempPath, $targetPath, 1024 * 1024); // 1MB limit
                
                if (!$compressed) {
                    // If compression fails, use original file if it's already under 1MB
                    if (filesize($tempPath) <= 1024 * 1024) {
                        rename($tempPath, $targetPath);
                    } else {
                        @unlink($tempPath);
                        $_SESSION['error'] = 'Failed to compress PDF. Please ensure the file is under 1MB or use a PDF compression tool.';
                        $_SESSION['active_tab'] = 'documents';
                        $this->redirect('student/profile/edit');
                        return;
                    }
                } else {
                    // Remove temp file if compression succeeded
                    @unlink($tempPath);
                }
                
                // Verify final file size
                if (filesize($targetPath) > 1024 * 1024) {
                    @unlink($targetPath);
                    $_SESSION['error'] = 'PDF compression failed. File size is still over 1MB. Please compress the PDF manually before uploading.';
                    $_SESSION['active_tab'] = 'documents';
                    $this->redirect('student/profile/edit');
                    return;
                }
                
                // Update database with filename
                $data['student_documents_pdf'] = $newFilename;
            } else {
                $_SESSION['error'] = 'Invalid update section.';
                $this->redirect('student/profile/edit');
                return;
            }
            
            // Validation for personal information
            if ($validationRequired) {
                if (empty($data['student_fullname']) || empty($data['student_email']) || empty($data['student_nic'])) {
                    $_SESSION['error'] = 'Full Name, Email, and NIC are required.';
                    $_SESSION['active_tab'] = $updateSection;
                    $this->redirect('student/profile/edit');
                    return;
                }
                
                // Validate email format
                if (!filter_var($data['student_email'], FILTER_VALIDATE_EMAIL)) {
                    $_SESSION['error'] = 'Invalid email format.';
                    $_SESSION['active_tab'] = $updateSection;
                    $this->redirect('student/profile/edit');
                    return;
                }
            }
            
            // Update student
            if (!empty($data)) {
                $result = $studentModel->updateStudent($studentId, $data);
                
                if ($result) {
                    $_SESSION['message'] = $successMessage;
                    // Refresh student data after update
                    $student = $studentModel->find($studentId);
                    $_SESSION['active_tab'] = $updateSection;
                } else {
                    $_SESSION['error'] = 'Failed to update profile. Please try again.';
                }
            }
            
            $this->redirect('student/profile/edit');
            return;
        }
        
        // GET request - show edit form
        $activeTab = $_SESSION['active_tab'] ?? 'personal';
        unset($_SESSION['active_tab']);
        
        $data = [
            'title' => 'Edit My Profile',
            'page' => 'student-profile-edit',
            'student' => $student,
            'activeTab' => $activeTab,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        
        return $this->view('student/profile-edit', $data);
    }
    
    /**
     * Compress PDF file to target size (1MB or less)
     * Uses Ghostscript if available, otherwise tries alternative methods
     */
    private function compressPdf($inputPath, $outputPath, $maxSizeBytes) {
        // Check if Ghostscript is available (most reliable method)
        $gsCommand = $this->findGhostscriptCommand();
        
        if ($gsCommand) {
            // Use Ghostscript to compress PDF
            // /screen = 72 dpi (lowest quality, smallest size)
            // /ebook = 150 dpi (medium quality)
            // /printer = 300 dpi (high quality)
            // /prepress = 300 dpi (highest quality)
            
            $quality = '/screen'; // Start with lowest quality for maximum compression
            
            $command = escapeshellarg($gsCommand) . 
                ' -sDEVICE=pdfwrite' .
                ' -dCompatibilityLevel=1.4' .
                ' -dPDFSETTINGS=' . $quality .
                ' -dNOPAUSE' .
                ' -dQUIET' .
                ' -dBATCH' .
                ' -sOutputFile=' . escapeshellarg($outputPath) .
                ' ' . escapeshellarg($inputPath) . ' 2>&1';
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($outputPath)) {
                $fileSize = filesize($outputPath);
                
                // If still too large, try with even more aggressive compression
                if ($fileSize > $maxSizeBytes) {
                    // Try with additional compression options
                    $command = escapeshellarg($gsCommand) . 
                        ' -sDEVICE=pdfwrite' .
                        ' -dCompatibilityLevel=1.4' .
                        ' -dPDFSETTINGS=/screen' .
                        ' -dColorImageResolution=72' .
                        ' -dGrayImageResolution=72' .
                        ' -dMonoImageResolution=72' .
                        ' -dDownsampleColorImages=true' .
                        ' -dDownsampleGrayImages=true' .
                        ' -dDownsampleMonoImages=true' .
                        ' -dColorImageDownsampleThreshold=1.0' .
                        ' -dGrayImageDownsampleThreshold=1.0' .
                        ' -dMonoImageDownsampleThreshold=1.0' .
                        ' -dNOPAUSE' .
                        ' -dQUIET' .
                        ' -dBATCH' .
                        ' -sOutputFile=' . escapeshellarg($outputPath) .
                        ' ' . escapeshellarg($inputPath) . ' 2>&1';
                    
                    exec($command, $output, $returnCode);
                }
                
                if ($returnCode === 0 && file_exists($outputPath) && filesize($outputPath) <= $maxSizeBytes) {
                    return true;
                }
            }
        }
        
        // Fallback: If Ghostscript is not available or compression failed,
        // try using Imagick if available
        if (extension_loaded('imagick')) {
            try {
                $imagick = new Imagick();
                $imagick->setResolution(72, 72);
                $imagick->readImage($inputPath);
                $imagick->setImageCompressionQuality(50);
                $imagick->writeImages($outputPath, true);
                $imagick->clear();
                $imagick->destroy();
                
                if (file_exists($outputPath) && filesize($outputPath) <= $maxSizeBytes) {
                    return true;
                }
            } catch (Exception $e) {
                // Imagick failed, continue to next method
            }
        }
        
        // Last resort: If file is already small enough, just copy it
        if (filesize($inputPath) <= $maxSizeBytes) {
            return copy($inputPath, $outputPath);
        }
        
        return false;
    }
    
    /**
     * Find Ghostscript command path
     */
    private function findGhostscriptCommand() {
        $possiblePaths = [
            'gs',
            '/usr/bin/gs',
            '/usr/local/bin/gs',
            'C:\\Program Files\\gs\\gs9.*\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs9.*\\bin\\gswin32c.exe',
        ];
        
        foreach ($possiblePaths as $path) {
            if (strpos($path, '*') !== false) {
                // Handle wildcard paths (Windows)
                $pattern = str_replace('\\', '/', $path);
                $basePath = dirname($pattern);
                $filename = basename($pattern);
                
                if (is_dir($basePath)) {
                    $files = glob($basePath . '/' . $filename);
                    if (!empty($files)) {
                        $testPath = $files[0];
                        if ($this->testCommand($testPath)) {
                            return $testPath;
                        }
                    }
                }
            } else {
                if ($this->testCommand($path)) {
                    return $path;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Test if a command exists and works
     */
    private function testCommand($command) {
        $testCommand = escapeshellarg($command) . ' --version 2>&1';
        exec($testCommand, $output, $returnCode);
        return $returnCode === 0;
    }
    
    /**
     * Ensure directory exists and is writable, with automatic permission fixing
     */
    private function ensureDirectoryWritable($directory, $directoryName) {
        // Check if directory exists
        if (!is_dir($directory)) {
            // Try to create directory
            if (!@mkdir($directory, 0755, true)) {
                $parentDir = dirname($directory);
                $currentPerms = is_dir($parentDir) ? substr(sprintf('%o', fileperms($parentDir)), -4) : 'unknown';
                return [
                    'success' => false,
                    'message' => "Directory '$directoryName' does not exist and could not be created. " .
                                "Parent directory permissions: $currentPerms. " .
                                "Please run: mkdir -p $directory && chmod 755 $directory"
                ];
            }
        }
        
        // Check current permissions
        $currentPerms = substr(sprintf('%o', fileperms($directory)), -4);
        
        // Check if writable
        if (!is_writable($directory)) {
            // Try multiple permission levels
            $permissions = [0777, 0775, 0755, 0700];
            $fixed = false;
            
            foreach ($permissions as $perm) {
                if (@chmod($directory, $perm)) {
                    if (is_writable($directory)) {
                        $fixed = true;
                        break;
                    }
                }
            }
            
            if (!$fixed) {
                // Get owner info if possible
                $ownerInfo = '';
                if (function_exists('posix_getpwuid')) {
                    $stat = @stat($directory);
                    if ($stat) {
                        $owner = @posix_getpwuid($stat['uid']);
                        $group = @posix_getgrgid($stat['gid']);
                        $ownerInfo = " (Owner: " . ($owner['name'] ?? 'unknown') . ", Group: " . ($group['name'] ?? 'unknown') . ")";
                    }
                }
                
                $webServerUser = 'www-data'; // Default, common for Nginx
                if (function_exists('posix_geteuid')) {
                    $currentUid = posix_geteuid();
                    $currentUser = posix_getpwuid($currentUid);
                    if ($currentUser) {
                        $webServerUser = $currentUser['name'];
                    }
                }
                
                return [
                    'success' => false,
                    'message' => "Directory '$directoryName' is not writable. Current permissions: $currentPerms$ownerInfo. " .
                                "Please run: chmod 755 $directory " .
                                "or: chmod 775 $directory (if web server needs group write access). " .
                                "If using Nginx/PHP-FPM, you may also need: chown -R $webServerUser:$webServerUser $directory"
                ];
            }
        }
        
        return ['success' => true, 'message' => ''];
    }
    
    /**
     * Accept Code of Conduct (AJAX endpoint for students)
     */
    public function acceptConduct() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        // Check if user is a student
        if (!isset($_SESSION['user_table']) || $_SESSION['user_table'] !== 'student') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Access denied. Only students can accept the code of conduct.']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            return;
        }
        
        $studentId = $_SESSION['user_name'];
        $studentModel = $this->model('StudentModel');
        $student = $studentModel->find($studentId);
        
        if (!$student) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Student record not found.']);
            return;
        }
        
        // Check if already accepted
        if (!empty($student['student_conduct_accepted_at'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Code of conduct already accepted.',
                'accepted_at' => $student['student_conduct_accepted_at']
            ]);
            return;
        }
        
        // Update acceptance date
        $currentDateTime = date('Y-m-d H:i:s');
        $result = $studentModel->updateStudent($studentId, [
            'student_conduct_accepted_at' => $currentDateTime
        ]);
        
        if ($result) {
            // Log activity
            require_once BASE_PATH . '/core/ActivityLogger.php';
            $activityLogger = new ActivityLogger();
            $activityLogger->log(
                'student_conduct_accepted',
                "Student {$studentId} accepted Code of Conduct",
                'success',
                $_SESSION['user_id'],
                $studentId
            );
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Code of conduct accepted successfully.',
                'accepted_at' => $currentDateTime
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Failed to update acceptance. Please try again.']);
        }
    }
    
    public function create() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict student users - they should use the student portal
        if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $_SESSION['error'] = 'Access denied. Students cannot access this page.';
            $this->redirect('student/dashboard');
            return;
        }
        
        // Only allow SAO and ADM users
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isSAO = $userModel->isSAO($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM');
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        
        if (!$isSAO && !$isADM && !$isAdmin) {
            $_SESSION['error'] = 'Access denied. Only Student Affairs Office (SAO) and Administrators (ADM) can create students.';
            $this->redirect('students');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $studentModel = $this->model('StudentModel');
            
            // Build data array - only include fields that have values
            $data = [
                'student_id' => trim($this->post('student_id', '')),
                'student_fullname' => trim($this->post('student_fullname', '')),
                'student_nic' => trim($this->post('student_nic', '')),
                'student_status' => trim($this->post('student_status', 'Active'))
            ];
            
            // Add optional fields only if they have values (not empty strings)
            $optionalFields = [
                'student_title', 'student_gender', 'student_ininame', 'student_civil', 
                'student_dob', 'student_phone', 'student_address', 'student_zip', 
                'student_district', 'student_divisions', 'student_provice', 'student_blood', 
                'student_em_name', 'student_em_address', 'student_em_phone', 'student_em_relation'
            ];
            
            foreach ($optionalFields as $field) {
                $value = trim($this->post($field, ''));
                if ($value !== '') {
                    $data[$field] = $value;
                }
            }
            
            // Set student_email to NULL - will be auto-generated in createStudent if needed
            $data['student_email'] = null;
            
            // Get enrollment data first to validate
            $courseId = trim($this->post('course_id', ''));
            $academicYear = trim($this->post('academic_year', ''));
            $courseMode = trim($this->post('course_mode', 'Full Time'));
            $enrollStatus = trim($this->post('student_enroll_status', 'Following'));
            
            // If enrollment is being created, Full Name and NIC are mandatory
            if (!empty($courseId) && !empty($academicYear)) {
                if (empty($data['student_fullname']) || empty($data['student_nic'])) {
                    $_SESSION['error'] = 'Full Name and NIC are required when creating enrollment.';
                    $this->redirect('students/create');
                    return;
                }
            }
            
            // Validation - Only Student ID, Full Name, and NIC are required
            if (empty($data['student_id']) || empty($data['student_fullname']) || empty($data['student_nic'])) {
                $_SESSION['error'] = 'Student ID, Full Name, and NIC are required.';
                $this->redirect('students/create');
                return;
            }
            
            // Check if student ID already exists
            if ($studentModel->exists($data['student_id'])) {
                $_SESSION['error'] = 'Student ID already exists.';
                $this->redirect('students/create');
                return;
            }
            
            // Create student
            $result = $studentModel->createStudent($data);
            
            if ($result) {
                // Log activity
                $this->logActivity(
                    'CREATE',
                    'student',
                    $data['student_id'],
                    "Student created: {$data['student_fullname']} ({$data['student_id']})",
                    null,
                    $data
                );
                
                // Create enrollment if course and academic year are provided
                if (!empty($courseId) && !empty($academicYear)) {
                    $enrollmentModel = $this->model('StudentEnrollmentModel');
                    // Convert course_mode to match database enum: 'Full Time' -> 'Full', 'Part Time' -> 'Part'
                    $enrollmentCourseMode = ($courseMode === 'Full Time') ? 'Full' : (($courseMode === 'Part Time') ? 'Part' : $courseMode);
                    $enrollmentData = [
                        'student_id' => $data['student_id'],
                        'course_id' => $courseId,
                        'academic_year' => $academicYear,
                        'course_mode' => $enrollmentCourseMode,
                        'student_enroll_status' => $enrollStatus,
                        'student_enroll_date' => date('Y-m-d'),
                        'student_enroll_exit_date' => date('Y-m-d', strtotime('+1 year'))
                    ];
                    
                    $enrollResult = $enrollmentModel->createEnrollment($enrollmentData);
                    if ($enrollResult) {
                        // Log enrollment creation
                        $this->logActivity(
                            'CREATE',
                            'student_enrollment',
                            $data['student_id'],
                            "Enrollment created for student {$data['student_id']} in course {$courseId} ({$academicYear})",
                            null,
                            $enrollmentData
                        );
                        
                        $_SESSION['message'] = 'Student and enrollment created successfully.';
                    } else {
                        $_SESSION['message'] = 'Student created successfully, but enrollment creation failed.';
                    }
                } else {
                    $_SESSION['message'] = 'Student created successfully.';
                }
                
                $this->redirect('students');
            } else {
                $_SESSION['error'] = 'Failed to create student.';
                $this->redirect('students/create');
            }
        } else {
            // Get courses, departments, and academic years for dropdowns
            $courseModel = $this->model('CourseModel');
            $departmentModel = $this->model('DepartmentModel');
            $academicYearModel = $this->model('StudentModel');
            
            // Use getCoursesWithDepartment to ensure department_id is included
            $courses = $courseModel->getCoursesWithDepartment();
            $departments = $departmentModel->getAll();
            $academicYears = $academicYearModel->getAcademicYears();
            
            $data = [
                'title' => 'Create Student',
                'page' => 'students',
                'courses' => $courses,
                'departments' => $departments,
                'academicYears' => $academicYears,
                'error' => $_SESSION['error'] ?? null,
                'message' => $_SESSION['message'] ?? null
            ];
            unset($_SESSION['error'], $_SESSION['message']);
            return $this->view('students/create', $data);
        }
    }
    
    public function edit() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict student users - they should use the student portal
        if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $_SESSION['error'] = 'Access denied. Students cannot access this page.';
            $this->redirect('student/dashboard');
            return;
        }
        
        // Restrict HOD users
        if ($this->isHOD()) {
            $_SESSION['error'] = 'Access denied. Head of Department can only view student details.';
            $this->redirect('students');
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Student ID is required.';
            $this->redirect('students');
            return;
        }
        
        $studentModel = $this->model('StudentModel');
        $student = $studentModel->find($id);
        
        if (!$student) {
            $_SESSION['error'] = 'Student not found.';
            $this->redirect('students');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $updateSection = $this->post('update_section', '');
            $data = [];
            $validationRequired = false;
            
            // Handle updates based on section
            if ($updateSection === 'personal') {
                // Personal Information fields only
                $data = [
                    'student_title' => trim($this->post('student_title', '')),
                    'student_fullname' => trim($this->post('student_fullname', '')),
                    'student_ininame' => trim($this->post('student_ininame', '')),
                    'student_gender' => trim($this->post('student_gender', '')),
                    'student_civil' => trim($this->post('student_civil', '')),
                    'student_email' => trim($this->post('student_email', '')),
                    'student_nic' => trim($this->post('student_nic', '')),
                    'student_dob' => trim($this->post('student_dob', '')),
                    'student_phone' => trim($this->post('student_phone', '')),
                    'student_address' => trim($this->post('student_address', '')),
                    'student_zip' => trim($this->post('student_zip', '')),
                    'student_district' => trim($this->post('student_district', '')),
                    'student_divisions' => trim($this->post('student_divisions', '')),
                    'student_provice' => trim($this->post('student_provice', '')),
                    'student_blood' => trim($this->post('student_blood', '')),
                    'student_em_name' => trim($this->post('student_em_name', '')),
                    'student_em_address' => trim($this->post('student_em_address', '')),
                    'student_em_phone' => trim($this->post('student_em_phone', '')),
                    'student_em_relation' => trim($this->post('student_em_relation', '')),
                    'student_status' => trim($this->post('student_status', 'Active')),
                    'student_nationality' => trim($this->post('student_nationality', '')),
                    'student_whatsapp' => trim($this->post('student_whatsapp', '')),
                    'student_religion' => trim($this->post('student_religion', ''))
                ];
                $validationRequired = true;
                $successMessage = 'Personal information updated successfully.';
                
                // Handle profile image upload/removal
                $removeImage = $this->post('remove_profile_image', '');
                if ($removeImage === '1') {
                    // Remove existing image
                    $currentImagePath = $student['student_profile_img'] ?? $student['file_path'] ?? '';
                    if (!empty($currentImagePath)) {
                        // Normalize path - remove leading slash, ensure it's relative to assets
                        $normalizedPath = ltrim($currentImagePath, '/');
                        if (strpos($normalizedPath, 'assets/') === 0) {
                            $normalizedPath = substr($normalizedPath, 7);
                        }
                                // Convert old paths to new Studnet_profile path
                                if (strpos($normalizedPath, 'img/student_profile/') === 0) {
                                    $normalizedPath = str_replace('img/student_profile/', 'img/Studnet_profile/', $normalizedPath);
                                }
                                if (strpos($normalizedPath, 'img/Student_profile/') === 0) {
                                    $normalizedPath = str_replace('img/Student_profile/', 'img/Studnet_profile/', $normalizedPath);
                                }
                                if (strpos($normalizedPath, 'img/Studnet_profile/') !== 0) {
                                    $normalizedPath = 'img/Studnet_profile/' . basename($normalizedPath);
                                }
                        $oldImagePath = BASE_PATH . '/assets/' . $normalizedPath;
                        if (file_exists($oldImagePath)) {
                            @unlink($oldImagePath);
                        }
                    }
                    // Update database to remove image path
                    $studentModel->updateStudentImage($id, '');
                    $successMessage .= ' Profile image removed.';
                } elseif (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    // Handle new image upload
                    $file = $_FILES['profile_image'];
                    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                    
                    if (in_array($fileExtension, $allowedExtensions) && in_array($file['type'], $allowedMimeTypes)) {
                        // Standardized directory: assets/img/Studnet_profile
                        $imageDirectory = BASE_PATH . '/assets/img/Studnet_profile';
                        
                        // Create directory if it doesn't exist
                        if (!is_dir($imageDirectory)) {
                            if (!mkdir($imageDirectory, 0755, true)) {
                                $_SESSION['error'] = 'Could not create image directory. Please check folder permissions.';
                            }
                        }
                        
                        if (is_dir($imageDirectory)) {
                            // Delete old image if exists
                            $currentImagePath = $student['student_profile_img'] ?? $student['file_path'] ?? '';
                            if (!empty($currentImagePath)) {
                                // Normalize path - remove leading slash, ensure it's relative to assets
                                $normalizedPath = ltrim($currentImagePath, '/');
                                if (strpos($normalizedPath, 'assets/') === 0) {
                                    $normalizedPath = substr($normalizedPath, 7);
                                }
                                // Convert old paths to new Studnet_profile path
                                if (strpos($normalizedPath, 'img/student_profile/') === 0) {
                                    $normalizedPath = str_replace('img/student_profile/', 'img/Studnet_profile/', $normalizedPath);
                                }
                                if (strpos($normalizedPath, 'img/Student_profile/') === 0) {
                                    $normalizedPath = str_replace('img/Student_profile/', 'img/Studnet_profile/', $normalizedPath);
                                }
                                if (strpos($normalizedPath, 'img/Studnet_profile/') !== 0) {
                                    $normalizedPath = 'img/Studnet_profile/' . basename($normalizedPath);
                                }
                                $oldImagePath = BASE_PATH . '/assets/' . $normalizedPath;
                                if (file_exists($oldImagePath)) {
                                    @unlink($oldImagePath);
                                }
                            }
                            
                            // Generate unique filename
                            $safeStudentId = preg_replace('/[^a-zA-Z0-9._-]/', '_', $id);
                            $newFilename = $safeStudentId . '_' . time() . '.' . $fileExtension;
                            $targetPath = $imageDirectory . '/' . $newFilename;
                            
                            // Move uploaded file
                            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                                // Store path relative to assets: img/Studnet_profile/filename.jpg
                                $newImagePath = 'img/Studnet_profile/' . $newFilename;
                                $studentModel->updateStudentImage($id, $newImagePath);
                                $successMessage .= ' Profile image updated.';
                            } else {
                                $_SESSION['error'] = ($_SESSION['error'] ?? '') . ' Failed to upload profile image.';
                            }
                        }
                    } else {
                        $_SESSION['error'] = ($_SESSION['error'] ?? '') . ' Invalid image file. Please upload JPG, PNG, or GIF image.';
                    }
                }
                
            } elseif ($updateSection === 'bank') {
                // Bank Details fields only
                $data = [
                    'bank_name' => trim($this->post('bank_name', '')),
                    'bank_account_no' => trim($this->post('bank_account_no', '')),
                    'bank_branch' => trim($this->post('bank_branch', ''))
                ];
                $successMessage = 'Bank details updated successfully.';
                
            } elseif ($updateSection === 'enrollment') {
                // Enrollment update - handled separately
                $enrollmentModel = $this->model('StudentEnrollmentModel');
                $currentEnrollment = $enrollmentModel->getCurrentEnrollment($id);
                $editableEnrollment = $currentEnrollment ?: $enrollmentModel->getLatestEnrollment($id);
                
                $newStudentId = trim($this->post('student_id_new', ''));
                $courseId = trim($this->post('course_id', ''));
                $academicYear = trim($this->post('academic_year', ''));
                $courseMode = trim($this->post('course_mode', 'Full'));
                if ($courseMode === 'Full Time') $courseMode = 'Full';
                if ($courseMode === 'Part Time') $courseMode = 'Part';
                $enrollStatus = trim($this->post('student_enroll_status', 'Following'));
                
                // Update student_id (registration number) if changed
                if (!empty($newStudentId) && $newStudentId !== $id) {
                    // Check if new student_id already exists
                    if ($studentModel->exists($newStudentId)) {
                        $_SESSION['error'] = 'Registration number already exists. Please choose a different one.';
                    } else {
                        // Update student_id in student table
                        $updateIdResult = $studentModel->updateStudentId($id, $newStudentId);
                        if ($updateIdResult) {
                            $id = $newStudentId; // Update current ID for enrollment update
                            $_SESSION['message'] = 'Registration number updated successfully.';
                        } else {
                            $sqlErr = $studentModel->getLastSqlError();
                            $_SESSION['error'] = 'Failed to update registration number.' . ($sqlErr ? ' SQL error: ' . $sqlErr : '');
                        }
                    }
                }
                
                // Update enrollment if we have an enrollment record to update
                if (empty($_SESSION['error']) && !empty($editableEnrollment) && !empty($courseId) && !empty($academicYear)) {
                    $enrollmentData = [
                        'course_id' => $courseId,
                        'academic_year' => $academicYear,
                        'course_mode' => $courseMode,
                        'student_enroll_status' => $enrollStatus
                    ];
                    
                    // Use updateEnrollment for Following; updateEnrollmentByRecord for Dropout/Long Absent
                    if ($editableEnrollment['student_enroll_status'] === 'Following') {
                        $enrollResult = $enrollmentModel->updateEnrollment($id, $enrollmentData);
                    } else {
                        $enrollResult = $enrollmentModel->updateEnrollmentByRecord(
                            $id,
                            $editableEnrollment['course_id'],
                            $editableEnrollment['academic_year'],
                            $enrollmentData
                        );
                    }
                    if ($enrollResult) {
                        // Sync user_active with student_status (e.g. re-register: Active -> user_active=1)
                        $studentModel->syncUserActiveWithStudentStatus($id);
                        $_SESSION['message'] = ($_SESSION['message'] ?? '') . ' Enrollment updated successfully.' . 
                            ($enrollStatus === 'Following' && $editableEnrollment['student_enroll_status'] !== 'Following' ? ' Student re-registered.' : '');
                    } else {
                        $sqlErr = $enrollmentModel->getLastSqlError();
                        $_SESSION['error'] = ($_SESSION['error'] ?? '') . ' Failed to update enrollment.' . ($sqlErr ? ' SQL error: ' . $sqlErr : '');
                    }
                } elseif (empty($_SESSION['error']) && empty($editableEnrollment)) {
                    $_SESSION['error'] = 'No enrollment found to update.';
                }
                
                // Refresh student data after update
                $student = $studentModel->find($id);
                $_SESSION['active_tab'] = 'enrollment';
                // Skip the normal update flow for enrollment
                $updateSection = null;
                
            } elseif ($updateSection === 'eligibility') {
                // Eligibility fields only
                $data = [
                    'allowance_eligible' => $this->post('allowance_eligible', 0) ? 1 : 0,
                    'allowance_eligible_date' => trim($this->post('allowance_eligible_date', ''))
                ];
                // Ensure allowance_eligible_date column exists
                $studentModel->addAllowanceEligibleDateColumnIfNotExists();
                $successMessage = 'Eligibility information updated successfully.';
            }
            
            // Skip update if enrollment section (handled separately above)
            if ($updateSection === null) {
                // Enrollment update already handled, just refresh data
                $student = $studentModel->find($id);
                $_SESSION['active_tab'] = 'enrollment';
            }
            // Validation for personal information
            elseif ($validationRequired) {
                if (empty($data['student_fullname']) || empty($data['student_email']) || empty($data['student_nic'])) {
                    $_SESSION['error'] = 'Full Name, Email, and NIC are required.';
                } else {
                    // Update student
                    // Get old values before update
                    $oldStudent = $studentModel->find($id);
                    $oldValues = $oldStudent ? array_intersect_key($oldStudent, $data) : null;
                    
                    $result = $studentModel->updateStudent($id, $data);
                    
                    if ($result) {
                        // Sync user_active when student_status changes (e.g. Active -> user_active=1)
                        if (isset($data['student_status'])) {
                            $studentModel->syncUserActiveWithStudentStatus($id);
                        }
                        // Log activity
                        $studentName = isset($data['student_fullname']) ? $data['student_fullname'] : ($oldStudent['student_fullname'] ?? 'Unknown');
                        $this->logActivity(
                            'UPDATE',
                            'student',
                            $id,
                            "Student updated: {$studentName} ({$id}) - Section: {$updateSection}",
                            $oldValues,
                            $data
                        );
                        
                        // Log profile image update separately if changed
                        if (isset($removeImage) && $removeImage === '1') {
                            $oldImagePath = isset($oldStudent['student_profile_img']) ? $oldStudent['student_profile_img'] : (isset($oldStudent['file_path']) ? $oldStudent['file_path'] : '');
                            $this->logActivity(
                                'UPDATE',
                                'student_profile_image',
                                $id,
                                "Profile image removed for student {$id}",
                                ['student_profile_img' => $oldImagePath],
                                ['student_profile_img' => '']
                            );
                        }
                        
                        $_SESSION['message'] = $successMessage;
                        // Refresh student data after update
                        $student = $studentModel->find($id);
                        // Store active tab for redirect
                        $_SESSION['active_tab'] = $updateSection;
                    } else {
                        $sectionLabel = $updateSection === 'personal' ? 'Personal information' : ($updateSection === 'bank' ? 'Bank details' : ($updateSection === 'eligibility' ? 'Eligibility' : $updateSection));
                        $sqlErr = $studentModel->getLastSqlError();
                        $_SESSION['error'] = 'Failed to update ' . $sectionLabel . '.' . ($sqlErr ? ' SQL error: ' . $sqlErr : '');
                    }
                }
            } else {
                // Update student (no validation required for bank/eligibility)
                if (!empty($data)) {
                    // Get old values before update
                    $oldStudent = $studentModel->find($id);
                    $oldValues = $oldStudent ? array_intersect_key($oldStudent, $data) : null;
                    
                    $result = $studentModel->updateStudent($id, $data);
                    
                    if ($result) {
                        // Log activity
                        $studentName = isset($data['student_fullname']) ? $data['student_fullname'] : ($oldStudent['student_fullname'] ?? 'Unknown');
                        $this->logActivity(
                            'UPDATE',
                            'student',
                            $id,
                            "Student updated: {$studentName} ({$id}) - Section: {$updateSection}",
                            $oldValues,
                            $data
                        );
                        
                        // Log profile image update separately if changed
                        if (isset($removeImage) && $removeImage === '1') {
                            $oldImagePath = isset($oldStudent['student_profile_img']) ? $oldStudent['student_profile_img'] : (isset($oldStudent['file_path']) ? $oldStudent['file_path'] : '');
                            $this->logActivity(
                                'UPDATE',
                                'student_profile_image',
                                $id,
                                "Profile image removed for student {$id}",
                                ['student_profile_img' => $oldImagePath],
                                ['student_profile_img' => '']
                            );
                        }
                        
                        $_SESSION['message'] = $successMessage;
                        // Refresh student data after update
                        $student = $studentModel->find($id);
                        // Store active tab for redirect
                        $_SESSION['active_tab'] = $updateSection;
                    } else {
                        $sectionLabel = $updateSection === 'personal' ? 'Personal information' : ($updateSection === 'bank' ? 'Bank details' : ($updateSection === 'eligibility' ? 'Eligibility' : $updateSection));
                        $sqlErr = $studentModel->getLastSqlError();
                        $_SESSION['error'] = 'Failed to update ' . $sectionLabel . '.' . ($sqlErr ? ' SQL error: ' . $sqlErr : '');
                    }
                }
            }
        }
        
        // Get enrollment information (for both GET and after POST)
        $enrollmentModel = $this->model('StudentEnrollmentModel');
        $enrollments = $enrollmentModel->getByStudentId($id);
        $currentEnrollment = $enrollmentModel->getCurrentEnrollment($id);
        // For dropout/long absent: use latest enrollment so they can re-register (change status to Following)
        if (empty($currentEnrollment)) {
            $currentEnrollment = $enrollmentModel->getLatestEnrollment($id);
        }
        
        // Get hostel information
        $roomAllocationModel = $this->model('RoomAllocationModel');
        $hostelAllocation = $roomAllocationModel->getActiveByStudentId($id);
        
        // Get courses and departments for enrollment
        $courseModel = $this->model('CourseModel');
        $departmentModel = $this->model('DepartmentModel');
        $academicYearModel = $this->model('StudentModel');
        
        $courses = $courseModel->all('course_name ASC');
        $departments = $departmentModel->getAll();
        $academicYears = $academicYearModel->getAcademicYears();
        
        // Refresh student data if not already refreshed
        if (!isset($student) || empty($student)) {
            $student = $studentModel->find($id);
        }
        
        $data = [
            'title' => 'Edit Student',
            'page' => 'students',
            'student' => $student,
            'enrollments' => $enrollments,
            'currentEnrollment' => $currentEnrollment,
            'hostelAllocation' => $hostelAllocation,
            'courses' => $courses,
            'departments' => $departments,
            'academicYears' => $academicYears,
            'error' => $_SESSION['error'] ?? null,
            'message' => $_SESSION['message'] ?? null
        ];
        unset($_SESSION['error'], $_SESSION['message']);
        return $this->view('students/edit', $data);
    }
    
    public function delete() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict student users - they should use the student portal
        if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $_SESSION['error'] = 'Access denied. Students cannot access this page.';
            $this->redirect('student/dashboard');
            return;
        }
        
        // Restrict HOD users
        if ($this->isHOD()) {
            $_SESSION['error'] = 'Access denied. Head of Department cannot delete students.';
            $this->redirect('students');
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Student ID is required.';
            $this->redirect('students');
            return;
        }
        
        $studentModel = $this->model('StudentModel');
        $student = $studentModel->find($id);
        
        if (!$student) {
            $_SESSION['error'] = 'Student not found.';
            $this->redirect('students');
            return;
        }
        
        // Store old values for logging
        $oldValues = $student;
        
        // Get current filters from query parameters or referrer
        $currentFilters = [];
        $filterKeys = ['department_id', 'course_id', 'academic_year', 'status', 'search', 'district', 'gender', 'course_mode', 'group_id', 'page'];
        
        foreach ($filterKeys as $key) {
            $value = $this->get($key, '');
            if (!empty($value)) {
                $currentFilters[$key] = $value;
            }
        }
        
        // If no filters in URL, try to get from referrer
        if (empty($currentFilters) && !empty($_SERVER['HTTP_REFERER'])) {
            $referer = parse_url($_SERVER['HTTP_REFERER']);
            if (!empty($referer['query'])) {
                parse_str($referer['query'], $queryParams);
                foreach ($filterKeys as $key) {
                    if (!empty($queryParams[$key])) {
                        $currentFilters[$key] = $queryParams[$key];
                    }
                }
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Delete student
            $result = $studentModel->deleteStudent($id);
            
            if ($result) {
                // Log activity
                $this->logActivity(
                    'DELETE',
                    'student',
                    $id,
                    "Student deleted: {$student['student_fullname']} ({$id})",
                    $oldValues,
                    null
                );
                
                $_SESSION['message'] = 'Student deleted successfully.';
            } else {
                $_SESSION['error'] = 'Failed to delete student.';
            }
            
            // Preserve filters from POST or current filters
            $redirectFilters = [];
            foreach ($filterKeys as $key) {
                $value = $this->post($key, '');
                if (empty($value) && isset($currentFilters[$key])) {
                    $value = $currentFilters[$key];
                }
                if (!empty($value)) {
                    $redirectFilters[$key] = $value;
                }
            }
            
            // Build redirect URL with filters
            $redirectUrl = 'students';
            if (!empty($redirectFilters)) {
                $redirectUrl .= '?' . http_build_query($redirectFilters);
            }
            
            $this->redirect($redirectUrl);
        } else {
            $data = [
                'title' => 'Delete Student',
                'page' => 'students',
                'student' => $student,
                'filters' => $currentFilters
            ];
            return $this->view('students/delete', $data);
        }
    }
    
    /**
     * View deleted students (ADM only)
     */
    public function deletedStudents() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }

        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM');
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);

        // Only ADM (and system admin) can access
        if (!$isADM && !$isAdmin) {
            $_SESSION['error'] = 'Access denied. Only ADM users can view deleted students.';
            $this->redirect('students');
            return;
        }

        require_once BASE_PATH . '/models/DeletedStudentModel.php';
        $deletedModel = new DeletedStudentModel();
        $deletedStudents = $deletedModel->all('deleted_at DESC');

        $data = [
            'title' => 'Deleted Students',
            'page' => 'admin-deleted-students',
            'deletedStudents' => $deletedStudents,
            'error' => $_SESSION['error'] ?? null,
            'message' => $_SESSION['message'] ?? null
        ];

        unset($_SESSION['error'], $_SESSION['message']);
        return $this->view('admin/deleted-students', $data);
    }
    
    public function resetPassword() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Student ID is required.';
            $this->redirect('students');
            return;
        }
        
        $studentModel = $this->model('StudentModel');
        $student = $studentModel->find($id);
        
        if (!$student) {
            $_SESSION['error'] = 'Student not found.';
            $this->redirect('students');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPassword = $this->post('new_password', '');
            $confirmPassword = $this->post('confirm_password', '');
            
            if (empty($newPassword) || empty($confirmPassword)) {
                $_SESSION['error'] = 'Both password fields are required.';
                $this->redirect('students/reset-password?id=' . urlencode($id));
                return;
            }
            
            if ($newPassword !== $confirmPassword) {
                $_SESSION['error'] = 'Passwords do not match.';
                $this->redirect('students/reset-password?id=' . urlencode($id));
                return;
            }
            
            if (strlen($newPassword) < 6) {
                $_SESSION['error'] = 'Password must be at least 6 characters long.';
                $this->redirect('students/reset-password?id=' . urlencode($id));
                return;
            }
            
            // Update password in user table
            $db = Database::getInstance();
            $hashedPassword = hash('sha256', $newPassword);
            
            // Find user by student_id
            $stmt = $db->prepare("SELECT user_id FROM `user` WHERE user_name = ? AND user_table = 'student'");
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if ($user) {
                $updateStmt = $db->prepare("UPDATE `user` SET user_password_hash = ? WHERE user_id = ?");
                $updateStmt->bind_param("si", $hashedPassword, $user['user_id']);
                $updateStmt->execute();
                
                $_SESSION['message'] = 'Password reset successfully.';
                $this->redirect('students/view?id=' . urlencode($id));
            } else {
                $_SESSION['error'] = 'User account not found for this student.';
                $this->redirect('students/view?id=' . urlencode($id));
            }
        } else {
            $data = [
                'title' => 'Reset Password',
                'page' => 'students',
                'student' => $student,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('students/reset-password', $data);
        }
    }
    
    /**
     * Get last registration number for a course, academic year, and course mode (AJAX endpoint)
     */
    public function getLastRegNumber() {
        // Set JSON header
        header('Content-Type: application/json');
        
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        $courseId = $this->get('course_id', '');
        $academicYear = $this->get('academic_year', '');
        $courseMode = $this->get('course_mode', 'Full Time');
        
        if (empty($courseId) || empty($academicYear)) {
            echo json_encode(['success' => false, 'error' => 'Course ID and Academic Year are required']);
            return;
        }
        
        try {
            $studentModel = $this->model('StudentModel');
            // Get next available registration number that doesn't exist yet
            $nextAvailableId = $studentModel->getNextAvailableRegistrationNumber($courseId, $academicYear, $courseMode);
            
            if ($nextAvailableId) {
                echo json_encode([
                    'success' => true,
                    'lastRegNumber' => $nextAvailableId // Actually returns next available ID
                ]);
            } else {
                // Try to generate a new ID using generateNextRegistrationNumber as fallback
                $generatedId = $studentModel->generateNextRegistrationNumber($courseId, $academicYear, $courseMode);
                if ($generatedId) {
                    echo json_encode([
                        'success' => true,
                        'lastRegNumber' => $generatedId
                    ]);
                } else {
                    // If still no ID found, return error
                    echo json_encode([
                        'success' => false,
                        'error' => 'Unable to generate student ID. Please enter student ID manually.'
                    ]);
                }
            }
        } catch (Exception $e) {
            error_log("Error in getLastRegNumber: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Error fetching next available student ID: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Download sample Excel/CSV file for student import
     */
    public function downloadSampleExcel() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict HOD users
        if ($this->isHOD()) {
            $_SESSION['error'] = 'Access denied. Head of Department cannot create students.';
            $this->redirect('students');
            return;
        }
        
        // Set headers for CSV download (Excel compatible)
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="student_import_sample.csv"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        // Add BOM for UTF-8 Excel compatibility
        echo "\xEF\xBB\xBF";
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($output, ['student_id', 'fullname', 'NIC'], ',');
        
        // Write sample data rows
        fputcsv($output, ['2025/MET/5PT001', 'John Doe Perera', '199012345678'], ',');
        fputcsv($output, ['2025/MET/5PT002', 'Jane Smith Fernando', '199512345679'], ',');
        fputcsv($output, ['2025/MET/5PT003', 'Kamal Silva', '200012345680'], ',');
        fputcsv($output, ['2025/MET/5PT004', 'Samantha Kumari', '200112345681'], ',');
        fputcsv($output, ['2025/MET/5PT005', 'Amal Wijesinghe', '199812345682'], ',');
        
        fclose($output);
        exit;
    }
    
    /**
     * Import students from Excel/CSV file
     */
    public function importExcel() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict student users - they should use the student portal
        if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $_SESSION['error'] = 'Access denied. Students cannot access this page.';
            $this->redirect('student/dashboard');
            return;
        }
        
        // Restrict HOD users
        if ($this->isHOD()) {
            $_SESSION['error'] = 'Access denied. Head of Department cannot create students.';
            $this->redirect('students');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method.';
            $this->redirect('students/create');
            return;
        }
        
        // Get form data
        $departmentId = trim($this->post('department_id', ''));
        $courseId = trim($this->post('course_id', ''));
        $academicYear = trim($this->post('academic_year', ''));
        $courseMode = trim($this->post('course_mode', 'Full Time'));
        $enrollStatus = trim($this->post('student_enroll_status', 'Following'));
        
        // Validation
        if (empty($departmentId) || empty($courseId) || empty($academicYear)) {
            $_SESSION['error'] = 'Department, Course, and Academic Year are required.';
            $this->redirect('students/create');
            return;
        }
        
        // Check file upload
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Please select a valid Excel/CSV file to upload.';
            $this->redirect('students/create');
            return;
        }
        
        $file = $_FILES['excel_file'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileType = $file['type'];
        
        // Validate file type
        $allowedExtensions = ['csv', 'xlsx', 'xls'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            $_SESSION['error'] = 'Invalid file type. Please upload a CSV, XLSX, or XLS file.';
            $this->redirect('students/create');
            return;
        }
        
        // Validate file size (max 5MB)
        if ($fileSize > 5 * 1024 * 1024) {
            $_SESSION['error'] = 'File size exceeds 5MB limit.';
            $this->redirect('students/create');
            return;
        }
        
        $studentModel = $this->model('StudentModel');
        $enrollmentModel = $this->model('StudentEnrollmentModel');
        $courseModel = $this->model('CourseModel');
        
        // Get course details for registration number generation
        $course = $courseModel->getById($courseId);
        if (!$course) {
            $_SESSION['error'] = 'Selected course not found.';
            $this->redirect('students/create');
            return;
        }
        
        // Parse file
        $students = [];
        $errors = [];
        $lineNumber = 0;
        
        try {
            if ($fileExtension === 'csv') {
                // Handle CSV file
                $handle = fopen($fileTmpName, 'r');
                if ($handle === false) {
                    throw new Exception('Could not open uploaded file.');
                }
                
                // Check for BOM first
                $first3Bytes = fread($handle, 3);
                $hasBOM = ($first3Bytes === "\xEF\xBB\xBF");
                
                if (!$hasBOM) {
                    // Not BOM, rewind to beginning
                    rewind($handle);
                }
                // If it was BOM, file pointer is already at position 3 (past BOM)
                
                // Read a line to detect delimiter
                $currentPos = ftell($handle);
                $firstLine = fgets($handle);
                if ($firstLine === false) {
                    fclose($handle);
                    throw new Exception('File is empty or could not be read. Please check the file format.');
                }
                
                // Detect delimiter (comma, semicolon, or tab)
                $delimiter = ',';
                $commaCount = substr_count($firstLine, ',');
                $semicolonCount = substr_count($firstLine, ';');
                $tabCount = substr_count($firstLine, "\t");
                
                if ($semicolonCount > $commaCount && $semicolonCount > $tabCount) {
                    $delimiter = ';';
                } elseif ($tabCount > $commaCount && $tabCount > $semicolonCount) {
                    $delimiter = "\t";
                } else {
                    $delimiter = ','; // Default to comma
                }
                
                // Rewind to start (or after BOM) and read header with fgetcsv
                fseek($handle, $currentPos);
                $header = fgetcsv($handle, 0, $delimiter); // 0 = no length limit
                $lineNumber++;
                
                if (!$header || empty($header) || count($header) < 3) {
                    fclose($handle);
                    $headerInfo = empty($header) ? 'none' : (count($header) . ' columns: ' . implode(', ', array_slice($header, 0, 5)));
                    throw new Exception('File header is invalid. Found: ' . $headerInfo . '. Expected: student_id, fullname, NIC (3 columns minimum)');
                }
                
                // Normalize header (trim whitespace, case-insensitive, remove BOM)
                $header = array_map(function($col) {
                    // Remove BOM if present
                    $col = str_replace("\xEF\xBB\xBF", '', $col);
                    // Remove any leading/trailing whitespace and newlines
                    $col = trim($col);
                    $col = str_replace(["\r", "\n"], '', $col);
                    return $col;
                }, $header);
                
                // Debug: Log found headers for troubleshooting
                $foundHeaders = implode(', ', $header);
                
                $headerMap = [];
                foreach ($header as $index => $col) {
                    $colNormalized = strtolower(trim($col));
                    // Remove any special characters/spaces for matching (keep only alphanumeric)
                    $colNormalizedClean = preg_replace('/[^a-z0-9]/', '', $colNormalized);
                    
                    // Match student_id column (flexible matching)
                    if (!isset($headerMap['student_id']) && (
                        stripos($colNormalized, 'student_id') !== false ||
                        stripos($colNormalized, 'student id') !== false ||
                        stripos($colNormalized, 'registration') !== false ||
                        $colNormalizedClean === 'studentid' ||
                        ($colNormalizedClean === 'id' && $index === 0) // If first column is just "id"
                    )) {
                        $headerMap['student_id'] = $index;
                    }
                    
                    // Match fullname column (flexible matching)
                    if (!isset($headerMap['fullname']) && (
                        stripos($colNormalized, 'fullname') !== false ||
                        stripos($colNormalized, 'full name') !== false ||
                        stripos($colNormalized, 'full_name') !== false ||
                        (stripos($colNormalized, 'name') !== false && stripos($colNormalized, 'student') === false && stripos($colNormalized, 'initial') === false) ||
                        $colNormalizedClean === 'fullname' ||
                        $colNormalizedClean === 'name'
                    )) {
                        $headerMap['fullname'] = $index;
                    }
                    
                    // Match NIC column (flexible matching)
                    if (!isset($headerMap['nic']) && (
                        stripos($colNormalized, 'nic') !== false ||
                        stripos($colNormalized, 'national id') !== false ||
                        stripos($colNormalized, 'national_id') !== false ||
                        stripos($colNormalized, 'national') !== false ||
                        $colNormalizedClean === 'nic' ||
                        $colNormalizedClean === 'nationalid' ||
                        $colNormalizedClean === 'nid'
                    )) {
                        $headerMap['nic'] = $index;
                    }
                }
                
                // Validate required columns
                $missingColumns = [];
                if (!isset($headerMap['student_id'])) {
                    $missingColumns[] = 'student_id (or Student ID, Registration Number)';
                }
                if (!isset($headerMap['fullname'])) {
                    $missingColumns[] = 'fullname (or Full Name, Name)';
                }
                if (!isset($headerMap['nic'])) {
                    $missingColumns[] = 'NIC (or National ID)';
                }
                
                if (!empty($missingColumns)) {
                    fclose($handle);
                    $errorMsg = 'Invalid file format. Missing required columns: ' . implode(', ', $missingColumns);
                    $errorMsg .= '<br><br><strong>Found headers in your file:</strong><br>' . htmlspecialchars($foundHeaders);
                    $errorMsg .= '<br><br><strong>Expected headers:</strong><br>student_id, fullname, NIC';
                    $_SESSION['error'] = $errorMsg;
                    $this->redirect('students/create');
                    return;
                }
                
                // Read data rows using detected delimiter
                $rowCount = 0;
                $maxColumnIndex = max($headerMap['student_id'], $headerMap['fullname'], $headerMap['nic']);
                
                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) { // Use detected delimiter
                    $lineNumber++;
                    $rowCount++;
                    
                    // Skip if row is null or false (EOF)
                    if ($row === false || $row === null) {
                        break;
                    }
                    
                    // Remove BOM from first cell if present
                    if (!empty($row[0])) {
                        $row[0] = str_replace("\xEF\xBB\xBF", '', $row[0]);
                    }
                    
                    // Trim all values
                    $row = array_map(function($cell) {
                        return trim($cell);
                    }, $row);
                    
                    // Skip completely empty rows (all cells are empty)
                    $hasData = false;
                    foreach ($row as $cell) {
                        if (!empty($cell)) {
                            $hasData = true;
                            break;
                        }
                    }
                    if (!$hasData) {
                        continue;
                    }
                    
                    // Ensure row has enough columns
                    $rowColumnCount = count($row);
                    if ($rowColumnCount <= $maxColumnIndex) {
                        $errors[] = "Line $lineNumber: Row has insufficient columns. Expected at least " . ($maxColumnIndex + 1) . " columns but got " . $rowColumnCount . " (Row: " . implode(', ', array_slice($row, 0, 5)) . ")";
                        continue;
                    }
                    
                    // Get values from row using header map
                    $studentIdCol = $headerMap['student_id'];
                    $fullnameCol = $headerMap['fullname'];
                    $nicCol = $headerMap['nic'];
                    
                    $studentId = isset($row[$studentIdCol]) ? trim($row[$studentIdCol]) : '';
                    $fullname = isset($row[$fullnameCol]) ? trim($row[$fullnameCol]) : '';
                    $nic = isset($row[$nicCol]) ? trim($row[$nicCol]) : '';
                    
                    // Skip if all required fields are empty (empty row)
                    if (empty($studentId) && empty($fullname) && empty($nic)) {
                        $errors[] = "Line $lineNumber: All required fields are empty (Row: " . implode(', ', array_slice($row, 0, 3)) . ")";
                        continue;
                    }
                    
                    // Validate required fields
                    if (empty($studentId)) {
                        $errors[] = "Line $lineNumber: Student ID is required. Column index $studentIdCol was empty or missing";
                        continue;
                    }
                    
                    if (empty($fullname)) {
                        $errors[] = "Line $lineNumber: Fullname is required. Column index $fullnameCol was empty or missing";
                        continue;
                    }
                    
                    if (empty($nic)) {
                        $errors[] = "Line $lineNumber: NIC is required. Column index $nicCol was empty or missing";
                        continue;
                    }
                    
                    // Check if student_id already exists
                    if ($studentModel->exists($studentId)) {
                        $errors[] = "Line $lineNumber: Student ID '$studentId' already exists in the database";
                        continue;
                    }
                    
                    // Generate email if not provided (use student_id + domain)
                    $email = strtolower(str_replace(['/', ' ', '_'], ['', '', ''], $studentId)) . '@slgti.com';
                    
                    $students[] = [
                        'student_id' => $studentId,
                        'student_fullname' => $fullname,
                        'student_nic' => $nic,
                        'student_email' => $email
                    ];
                }
                
                fclose($handle);
                
                // Debug info if no students found
                if (empty($students)) {
                    if ($rowCount == 0) {
                        throw new Exception('No data rows found in CSV file. The file appears to only contain headers. Please ensure your file has data rows after the header row.');
                    } else {
                        // We read some rows but they were all invalid
                        $debugInfo = "Found $rowCount row(s) but none were valid. ";
                        if (!empty($errors)) {
                            $debugInfo .= "Errors: " . implode('; ', array_slice($errors, 0, 5));
                        }
                        throw new Exception($debugInfo);
                    }
                }
            } else {
                // Handle XLSX/XLS files
                // For XLSX/XLS, we'll try to read as CSV first (if saved as CSV)
                // Otherwise, instruct user to save as CSV
                // Note: Full Excel support requires PhpSpreadsheet library
                
                // Try to read as CSV (some Excel files can be read this way if they're simple)
                $handle = @fopen($fileTmpName, 'r');
                if ($handle === false) {
                    $_SESSION['error'] = 'Could not read file. Please save your Excel file as CSV format (File > Save As > CSV) and try again.';
                    $this->redirect('students/create');
                    return;
                }
                
                // For XLSX/XLS, show helpful error
                $_SESSION['error'] = 'Excel files (.xlsx/.xls) are not directly supported. Please save your Excel file as CSV format:<br>' .
                    '1. Open your Excel file<br>' .
                    '2. Go to File > Save As<br>' .
                    '3. Choose "CSV (Comma delimited) (*.csv)" format<br>' .
                    '4. Save and upload the CSV file';
                $this->redirect('students/create');
                return;
            }
            
            if (empty($students)) {
                $errorMsg = 'No valid student records found in the file.';
                if (!empty($errors)) {
                    $errorMsg .= '<br><br><strong>Parsing Errors:</strong><br>' . implode('<br>', array_slice($errors, 0, 20));
                    if (count($errors) > 20) {
                        $errorMsg .= '<br><em>(and ' . (count($errors) - 20) . ' more errors)</em>';
                    }
                } else {
                    $errorMsg .= '<br>Please check that:<br>';
                    $errorMsg .= '1. Your CSV file has headers: student_id, fullname, NIC<br>';
                    $errorMsg .= '2. Your CSV file has data rows after the header row<br>';
                    $errorMsg .= '3. The data rows are not empty<br>';
                    $errorMsg .= '4. The file format is correct (comma-separated values)';
                }
                $_SESSION['error'] = $errorMsg;
                $this->redirect('students/create');
                return;
            }
            
            // Import students
            $successCount = 0;
            $failCount = 0;
            $importErrors = [];
            
            // Get database connection once
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            foreach ($students as $index => $studentData) {
                try {
                    // Validate student_id length (max 20 characters)
                    if (strlen($studentData['student_id']) > 20) {
                        throw new Exception('Student ID exceeds maximum length of 20 characters');
                    }
                    
                    // Validate email format and ensure it's unique
                    $generatedEmail = $studentData['student_email'];
                    // Check if email already exists
                    $emailCheck = $conn->prepare("SELECT `student_id` FROM `student` WHERE `student_email` = ?");
                    $emailCheck->bind_param("s", $generatedEmail);
                    $emailCheck->execute();
                    $emailResult = $emailCheck->get_result();
                    if ($emailResult->num_rows > 0) {
                        // Email exists, generate a unique one
                        $counter = 1;
                        do {
                            $generatedEmail = strtolower(str_replace(['/', ' ', '_'], ['', '', ''], $studentData['student_id'])) . $counter . '@slgti.com';
                            $emailCheck->close();
                            $emailCheck = $conn->prepare("SELECT `student_id` FROM `student` WHERE `student_email` = ?");
                            $emailCheck->bind_param("s", $generatedEmail);
                            $emailCheck->execute();
                            $emailResult = $emailCheck->get_result();
                            $counter++;
                        } while ($emailResult->num_rows > 0 && $counter < 100);
                    }
                    $emailCheck->close();
                    
                    // Create student record with all required fields
                    // Extract initials from fullname if needed
                    $fullname = $studentData['student_fullname'];
                    $initials = '';
                    if (!empty($fullname)) {
                        $nameParts = explode(' ', trim($fullname));
                        if (count($nameParts) > 0) {
                            $initials = strtoupper(substr($nameParts[0], 0, 1));
                            if (count($nameParts) > 1) {
                                $initials .= '.' . strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));
                            }
                        }
                    }
                    
                    $studentRecord = [
                        'student_id' => substr($studentData['student_id'], 0, 20), // Ensure max length
                        'student_title' => '', // Required field - empty string
                        'student_fullname' => $fullname,
                        'student_ininame' => $initials ?: substr($fullname, 0, 255), // Required field - use initials or fullname (max 255)
                        'student_gender' => '', // Optional
                        'student_civil' => 'Single', // Required field - default value
                        'student_email' => substr($generatedEmail, 0, 254), // Required field - max 254 chars
                        'student_nic' => substr($studentData['student_nic'], 0, 12), // Required field - max 12 chars
                        'student_dob' => '', // Optional
                        'student_phone' => 0, // Required field (int) - default 0
                        'student_address' => '', // Required field - empty string
                        'student_zip' => 0, // Required field (int) - default 0
                        'student_district' => '', // Required field - empty string
                        'student_divisions' => '', // Required field - empty string
                        'student_provice' => '', // Required field - empty string
                        'student_blood' => '', // Required field - empty string
                        'student_em_name' => '', // Required field - empty string
                        'student_em_address' => '', // Required field - empty string
                        'student_em_phone' => 0, // Required field (int) - default 0
                        'student_em_relation' => '', // Required field - empty string
                        'student_status' => 'Active', // Required field
                        'allowance_eligible' => 0 // Required field - default 0
                    ];
                    
                    $columns = implode('`, `', array_keys($studentRecord));
                    $placeholders = implode(', ', array_fill(0, count($studentRecord), '?'));
                    
                    $sql = "INSERT INTO `student` (`$columns`) VALUES ($placeholders)";
                    $stmt = $conn->prepare($sql);
                    
                    if (!$stmt) {
                        throw new Exception('Failed to prepare statement: ' . $conn->error);
                    }
                    
                    // Build types string and values - handle integers correctly
                    $types = '';
                    $params = [];
                    foreach ($studentRecord as $key => $value) {
                        if (in_array($key, ['student_phone', 'student_zip', 'student_em_phone', 'allowance_eligible'])) {
                            $types .= 'i'; // Integer
                            $params[] = (int)$value;
                        } else {
                            $types .= 's'; // String
                            $params[] = (string)$value;
                        }
                    }
                    
                    // Bind parameters with proper references (bind_param requires references)
                    $refs = [];
                    $refs[] = &$types; // First parameter is the type string
                    foreach ($params as $key => $value) {
                        $refs[] = &$params[$key]; // Each subsequent parameter needs to be a reference
                    }
                    
                    call_user_func_array([$stmt, 'bind_param'], $refs);
                    
                    $studentCreated = $stmt->execute();
                    
                    if (!$studentCreated) {
                        $errorMsg = $stmt->error ?: $conn->error;
                        throw new Exception('Database error: ' . $errorMsg);
                    }
                    
                    $stmt->close();
                    
                    if ($studentCreated) {
                        // Create enrollment
                        // Convert course_mode to match enum: 'Full Time' -> 'Full', 'Part Time' -> 'Part'
                        $enrollmentCourseMode = ($courseMode === 'Full Time') ? 'Full' : (($courseMode === 'Part Time') ? 'Part' : $courseMode);
                        
                        $enrollmentData = [
                            'student_id' => $studentData['student_id'],
                            'course_id' => $courseId,
                            'academic_year' => $academicYear,
                            'course_mode' => $enrollmentCourseMode,
                            'student_enroll_status' => $enrollStatus,
                            'student_enroll_date' => date('Y-m-d'),
                            'student_enroll_exit_date' => date('Y-m-d', strtotime('+1 year'))
                        ];
                        
                        $enrollmentCreated = $enrollmentModel->createEnrollment($enrollmentData);
                        
                        if ($enrollmentCreated) {
                            $successCount++;
                        } else {
                            $failCount++;
                            // Get enrollment error if available
                            $enrollError = '';
                            if ($conn && $conn->error) {
                                $enrollError = " (DB: " . $conn->error . ")";
                            }
                            $importErrors[] = "Student '{$studentData['student_fullname']}' (ID: {$studentData['student_id']}) created but enrollment failed" . $enrollError;
                        }
                    } else {
                        $failCount++;
                        $importErrors[] = "Failed to create student '{$studentData['student_fullname']}'";
                    }
                } catch (Exception $e) {
                    $failCount++;
                    $errorMessage = $e->getMessage();
                    // Include more detailed error info if available
                    if ($conn && $conn->error) {
                        $errorMessage .= " (DB Error: " . $conn->error . ")";
                    }
                    $importErrors[] = "Error importing '{$studentData['student_fullname']}' (ID: {$studentData['student_id']}): " . $errorMessage;
                }
            }
            
            // Prepare result message
            $message = "Import completed. Successfully imported: $successCount student(s)";
            if ($failCount > 0) {
                $message .= ", Failed: $failCount student(s)";
            }
            if (!empty($errors)) {
                $message .= "<br>Errors: " . implode('; ', array_slice($errors, 0, 10));
                if (count($errors) > 10) {
                    $message .= ' (and ' . (count($errors) - 10) . ' more)';
                }
            }
            if (!empty($importErrors)) {
                $message .= "<br>Import Errors: " . implode('; ', array_slice($importErrors, 0, 10));
                if (count($importErrors) > 10) {
                    $message .= ' (and ' . (count($importErrors) - 10) . ' more)';
                }
            }
            
            if ($successCount > 0) {
                $_SESSION['message'] = $message;
            } else {
                $_SESSION['error'] = $message;
            }
            
            $this->redirect('students');
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error processing file: ' . $e->getMessage();
            $this->redirect('students/create');
        }
    }
    
    /**
     * Import student profile images from directory
     * Shows existing images and allows selection
     */
    public function importImages() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict student users - they should use the student portal
        if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $_SESSION['error'] = 'Access denied. Students cannot access this page.';
            $this->redirect('student/dashboard');
            return;
        }
        
        // Check if user is ADM or Admin
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isAdminUser = $userModel->isAdmin($_SESSION['user_id']);
        $role = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($role === 'ADM');
        
        // Allow ADM and Admin users only (restrict HOD and others)
        if (!$isAdminUser && !$isADM) {
            if ($this->isHOD()) {
                $_SESSION['error'] = 'Access denied. Head of Department cannot import images.';
            } else {
                $_SESSION['error'] = 'Access denied. Only Administrators (ADM) and Admin users can import images.';
            }
            $this->redirect('students');
            return;
        }
        
        // Handle POST request - process selected images
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processSelectedImages();
            return;
        }
        
        // GET request - show image selection interface
        $studentModel = $this->model('StudentModel');
        
        // Standardized directory: assets/img/Student_profile
        $imageDirectory = BASE_PATH . '/assets/img/Student_profile';
        
        // Create directory if it doesn't exist
        if (!is_dir($imageDirectory)) {
            @mkdir($imageDirectory, 0755, true);
        }
        
        $targetDir = $imageDirectory;
        $imagePathPrefix = 'img/Studnet_profile';
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $existingImages = [];
        
        if ($targetDir && is_dir($targetDir)) {
            $files = scandir($targetDir);
            
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                
                $filePath = $targetDir . '/' . $file;
                
                if (!is_file($filePath)) {
                    continue;
                }
                
                $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                
                if (!in_array($fileExtension, $allowedExtensions)) {
                    continue;
                }
                
                // Try to match student ID
                $filenameWithoutExt = pathinfo($file, PATHINFO_FILENAME);
                $studentId = null;
                $studentName = null;
                $matchStatus = 'not_found';
                
                $potentialStudentIds = [
                    $filenameWithoutExt,
                    str_replace('_', '/', $filenameWithoutExt),
                    str_replace(['_', ' '], '', $filenameWithoutExt),
                ];
                
                foreach ($potentialStudentIds as $potentialId) {
                    $cleanId = trim($potentialId);
                    if ($studentModel->exists($cleanId)) {
                        $student = $studentModel->find($cleanId);
                        $studentId = $cleanId;
                        $studentName = $student['student_fullname'] ?? null;
                        $matchStatus = 'matched';
                        break;
                    }
                }
                
                if (!$studentId) {
                    $parts = explode('_', $filenameWithoutExt);
                    foreach ($parts as $part) {
                        if ($studentModel->exists(trim($part))) {
                            $student = $studentModel->find(trim($part));
                            $studentId = trim($part);
                            $studentName = $student['student_fullname'] ?? null;
                            $matchStatus = 'matched';
                            break;
                        }
                    }
                }
                
                // Check if already imported
                $isImported = false;
                if ($studentId) {
                    $student = $studentModel->find($studentId);
                    if (!empty($student['student_profile_img']) || !empty($student['file_path'])) {
                        $isImported = true;
                        $matchStatus = 'already_imported';
                    }
                }
                
                $imageUrl = APP_URL . '/assets/' . $imagePathPrefix . '/' . $file;
                $fileSize = filesize($filePath);
                
                $existingImages[] = [
                    'filename' => $file,
                    'path' => $filePath,
                    'url' => $imageUrl,
                    'size' => $fileSize,
                    'student_id' => $studentId,
                    'student_name' => $studentName,
                    'match_status' => $matchStatus,
                    'is_imported' => $isImported
                ];
            }
        }
        
        $data = [
            'title' => 'Import Existing Student Images',
            'page' => 'admin-import-images',
            'targetDir' => $targetDir,
            'imagePathPrefix' => $imagePathPrefix,
            'existingImages' => $existingImages,
            'error' => $_SESSION['error'] ?? null,
            'message' => $_SESSION['message'] ?? null
        ];
        
        unset($_SESSION['error'], $_SESSION['message']);
        return $this->view('students/import-images', $data);
    }
    
    /**
     * Process selected images for import
     */
    private function processSelectedImages() {
        $selectedFiles = $this->post('selected_files', []);
        
        if (empty($selectedFiles) || !is_array($selectedFiles)) {
            $_SESSION['error'] = 'Please select at least one image to import.';
            $this->redirect('students/import-images');
            return;
        }
        
        $studentModel = $this->model('StudentModel');
        
        // Standardized directory: assets/img/Student_profile
        $imageDirectory = BASE_PATH . '/assets/img/Student_profile';
        
        // Create directory if it doesn't exist
        if (!is_dir($imageDirectory)) {
            if (!mkdir($imageDirectory, 0755, true)) {
                $_SESSION['error'] = 'Image directory does not exist and could not be created.';
                $this->redirect('students/import-images');
                return;
            }
        }
        
        $targetDir = $imageDirectory;
        $imagePathPrefix = 'img/Studnet_profile';
        
        $processed = 0;
        $skipped = 0;
        $errors = [];
        
        foreach ($selectedFiles as $filename) {
            $filename = basename($filename); // Security: only get filename
            $filePath = $targetDir . '/' . $filename;
            
            if (!file_exists($filePath) || !is_file($filePath)) {
                $errors[] = "File not found: $filename";
                $skipped++;
                continue;
            }
            
            // Try to match student ID
            $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
            $studentId = null;
            
            $potentialStudentIds = [
                $filenameWithoutExt,
                str_replace('_', '/', $filenameWithoutExt),
                str_replace(['_', ' '], '', $filenameWithoutExt),
            ];
            
            foreach ($potentialStudentIds as $potentialId) {
                $cleanId = trim($potentialId);
                if ($studentModel->exists($cleanId)) {
                    $studentId = $cleanId;
                    break;
                }
            }
            
            if (!$studentId) {
                $parts = explode('_', $filenameWithoutExt);
                foreach ($parts as $part) {
                    if ($studentModel->exists(trim($part))) {
                        $studentId = trim($part);
                        break;
                    }
                }
            }
            
            if (!$studentId) {
                $errors[] = "Could not match student ID for: $filename";
                $skipped++;
                continue;
            }
            
            // Build image path
            $imagePath = $imagePathPrefix . '/' . $filename;
            
            // Update student record
            $updateResult = $studentModel->updateStudentImage($studentId, $imagePath);
            
            if ($updateResult) {
                $processed++;
            } else {
                $errors[] = "Failed to update student record for: $studentId (file: $filename)";
                $skipped++;
            }
        }
        
        // Prepare result message
        $message = "Image import completed. Successfully processed: $processed image(s)";
        if ($skipped > 0) {
            $message .= ", Skipped: $skipped file(s)";
        }
        if (!empty($errors)) {
            $message .= "<br><br><strong>Details:</strong><br>" . implode('<br>', array_slice($errors, 0, 20));
            if (count($errors) > 20) {
                $message .= '<br><em>(and ' . (count($errors) - 20) . ' more errors)</em>';
            }
        }
        
        if ($processed > 0) {
            $_SESSION['message'] = $message;
        } else {
            $_SESSION['error'] = $message;
        }
        
        $this->redirect('students/import-images');
    }
    
    /**
     * Show image upload interface
     */
    public function uploadImages() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict student users - they should use the student portal
        if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $_SESSION['error'] = 'Access denied. Students cannot access this page.';
            $this->redirect('student/dashboard');
            return;
        }
        
        // Check if user is ADM or Admin
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isAdminUser = $userModel->isAdmin($_SESSION['user_id']);
        $role = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($role === 'ADM');
        
        // Allow ADM and Admin users only
        if (!$isAdminUser && !$isADM) {
            $_SESSION['error'] = 'Access denied. Only Administrators (ADM) and Admin users can upload images.';
            $this->redirect('students');
            return;
        }
        
        // Handle POST request for file upload
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processImageUploads();
            return;
        }
        
        // GET request - show upload form
        $studentModel = $this->model('StudentModel');
        
        // Get directory info - use Studnet_profile as standard
        $imageDirectory = BASE_PATH . '/assets/img/Studnet_profile';
        $imageDirectoryAlt = BASE_PATH . '/assets/img/student_profile';
        $imageDirectoryAlt2 = BASE_PATH . '/assets/img/Student_profile';
        
        $targetDir = null;
        $imagePathPrefix = 'img/Studnet_profile';
        $dirExists = false;
        
        // Check for Studnet_profile first (preferred)
        if (is_dir($imageDirectory)) {
            $targetDir = $imageDirectory;
            $imagePathPrefix = 'img/Studnet_profile';
            $dirExists = true;
        } elseif (is_dir($imageDirectoryAlt2)) {
            $targetDir = $imageDirectoryAlt2;
            $imagePathPrefix = 'img/Student_profile';
            $dirExists = true;
        } elseif (is_dir($imageDirectoryAlt)) {
            $targetDir = $imageDirectoryAlt;
            $imagePathPrefix = 'img/student_profile';
            $dirExists = true;
        }
        
        // If directory doesn't exist, create it with Studnet_profile
        if (!$dirExists) {
            if (mkdir($imageDirectory, 0755, true)) {
                $targetDir = $imageDirectory;
                $imagePathPrefix = 'img/Studnet_profile';
                $dirExists = true;
            }
        }
        
        // Get PHP upload settings for display
        $phpSettings = [
            'max_file_uploads' => ini_get('max_file_uploads'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit')
        ];
        
        $data = [
            'title' => 'Upload Student Profile Images',
            'page' => 'admin-import-images',
            'targetDir' => $targetDir,
            'imagePathPrefix' => $imagePathPrefix,
            'dirExists' => $dirExists,
            'phpSettings' => $phpSettings,
            'error' => $_SESSION['error'] ?? null,
            'message' => $_SESSION['message'] ?? null
        ];
        
        unset($_SESSION['error'], $_SESSION['message']);
        return $this->view('students/upload-images', $data);
    }
    
    /**
     * Process uploaded images
     */
    private function processImageUploads() {
        // Increase PHP limits for large uploads (300+ images)
        ini_set('max_file_uploads', '500');
        ini_set('post_max_size', '512M');
        ini_set('upload_max_filesize', '10M');
        ini_set('max_execution_time', '600'); // 10 minutes
        ini_set('memory_limit', '512M');
        
        // Check if files were uploaded
        if (!isset($_FILES['images'])) {
            $_SESSION['error'] = 'No files were uploaded. Please check your PHP upload settings and try again.';
            $this->redirect('students/upload-images');
            return;
        }
        
        // Check for various upload error conditions
        $uploadedFiles = $_FILES['images'];
        
        // Handle case where files array might be structured differently
        if (!is_array($uploadedFiles['error'])) {
            // Single file upload (wrap in array)
            if ($uploadedFiles['error'] === UPLOAD_ERR_NO_FILE) {
                $_SESSION['error'] = 'Please select at least one image file to upload.';
                $this->redirect('students/upload-images');
                return;
            }
            // Convert to array format for consistency
            $uploadedFiles = [
                'name' => [$uploadedFiles['name']],
                'type' => [$uploadedFiles['type']],
                'tmp_name' => [$uploadedFiles['tmp_name']],
                'error' => [$uploadedFiles['error']],
                'size' => [$uploadedFiles['size']]
            ];
        } else {
            // Check if any files were selected
            $hasFiles = false;
            foreach ($uploadedFiles['error'] as $error) {
                if ($error !== UPLOAD_ERR_NO_FILE) {
                    $hasFiles = true;
                    break;
                }
            }
            
            if (!$hasFiles) {
                $_SESSION['error'] = 'Please select at least one image file to upload.';
                $this->redirect('students/upload-images');
                return;
            }
        }
        
        $studentModel = $this->model('StudentModel');
        
        // Standardized directory: assets/img/Student_profile
        $imageDirectory = BASE_PATH . '/assets/img/Student_profile';
        
        // Create directory if it doesn't exist
        if (!is_dir($imageDirectory)) {
            if (!mkdir($imageDirectory, 0755, true)) {
                $_SESSION['error'] = 'Could not create image directory. Please check folder permissions.';
                $this->redirect('students/upload-images');
                return;
            }
        }
        
        $targetDir = $imageDirectory;
        $imagePathPrefix = 'img/Studnet_profile';
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $maxFileSize = 10 * 1024 * 1024; // 10MB (increased for larger images)
        
        $fileCount = count($uploadedFiles['name']);
        
        $processed = 0;
        $skipped = 0;
        $errors = [];
        
        // Process each uploaded file
        for ($i = 0; $i < $fileCount; $i++) {
            // Check for upload errors
            if ($uploadedFiles['error'][$i] !== UPLOAD_ERR_OK) {
                $errorCode = $uploadedFiles['error'][$i];
                $errors[] = "File " . ($i + 1) . ": Upload error (code: $errorCode)";
                $skipped++;
                continue;
            }
            
            $tmpName = $uploadedFiles['tmp_name'][$i];
            $originalName = $uploadedFiles['name'][$i];
            $fileSize = $uploadedFiles['size'][$i];
            $fileType = $uploadedFiles['type'][$i];
            
            // Validate file size
            if ($fileSize > $maxFileSize) {
                $errors[] = "$originalName: File size exceeds 5MB limit";
                $skipped++;
                continue;
            }
            
            // Validate file extension
            $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowedExtensions)) {
                $errors[] = "$originalName: Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed";
                $skipped++;
                continue;
            }
            
            // Validate MIME type
            if (!in_array($fileType, $allowedMimeTypes)) {
                $errors[] = "$originalName: Invalid file type detected";
                $skipped++;
                continue;
            }
            
            // Validate it's actually an image
            $imageInfo = @getimagesize($tmpName);
            if ($imageInfo === false) {
                $errors[] = "$originalName: File is not a valid image";
                $skipped++;
                continue;
            }
            
            // Extract student ID from filename
            $filenameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
            $studentId = null;
            $studentFound = false;
            
            // Try to match student ID patterns
            $potentialStudentIds = [
                $filenameWithoutExt,
                str_replace('_', '/', $filenameWithoutExt),
                str_replace(['_', ' '], '', $filenameWithoutExt),
            ];
            
            foreach ($potentialStudentIds as $potentialId) {
                $cleanId = trim($potentialId);
                if ($studentModel->exists($cleanId)) {
                    $studentId = $cleanId;
                    $studentFound = true;
                    break;
                }
            }
            
            if (!$studentFound) {
                // Try partial matching
                $parts = explode('_', $filenameWithoutExt);
                foreach ($parts as $part) {
                    if ($studentModel->exists(trim($part))) {
                        $studentId = trim($part);
                        $studentFound = true;
                        break;
                    }
                }
            }
            
            if (!$studentFound) {
                $errors[] = "$originalName: Could not match to any student ID";
                $skipped++;
                continue;
            }
            
            // Generate safe filename (use student_id as filename to avoid conflicts)
            $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $studentId) . '.' . $fileExtension;
            $targetPath = $targetDir . '/' . $safeFilename;
            
            // If file exists, append timestamp
            if (file_exists($targetPath)) {
                $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $studentId) . '_' . time() . '.' . $fileExtension;
                $targetPath = $targetDir . '/' . $safeFilename;
            }
            
            // Move uploaded file
            if (!move_uploaded_file($tmpName, $targetPath)) {
                $errors[] = "$originalName: Failed to save file";
                $skipped++;
                continue;
            }
            
            // Update student record with image path
            $imagePath = $imagePathPrefix . '/' . $safeFilename;
            $updateResult = $studentModel->updateStudentImage($studentId, $imagePath);
            
            if ($updateResult) {
                $processed++;
            } else {
                $errors[] = "$originalName: File uploaded but failed to update student record for: $studentId";
                // Delete the uploaded file since database update failed
                @unlink($targetPath);
                $skipped++;
            }
        }
        
        // Prepare result message
        $message = "Image upload completed. Successfully processed: $processed image(s)";
        if ($skipped > 0) {
            $message .= ", Skipped: $skipped file(s)";
        }
        if (!empty($errors)) {
            $message .= "<br><br><strong>Details:</strong><br>" . implode('<br>', array_slice($errors, 0, 20));
            if (count($errors) > 20) {
                $message .= '<br><em>(and ' . (count($errors) - 20) . ' more errors)</em>';
            }
        }
        
        if ($processed > 0) {
            $_SESSION['message'] = $message;
        } else {
            $_SESSION['error'] = $message;
        }
        
        $this->redirect('students/upload-images');
    }
    
    /**
     * Export students to Excel/CSV
     * Only accessible by ADM and SAO users
     */
    public function exportExcel() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Check if user is ADM, SAO, or HOD
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isSAO = $userModel->isSAO($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM');
        $isHOD = $this->isHOD();
        
        if (!$isADM && !$isSAO && !$isHOD) {
            $_SESSION['error'] = 'Access denied. Only ADM, SAO, and HOD users can export students.';
            $this->redirect('students');
            return;
        }
        
        $studentModel = $this->model('StudentModel');
        $departmentModel = $this->model('DepartmentModel');
        $courseModel = $this->model('CourseModel');
        
        // Get HOD's department if user is HOD (but they shouldn't reach here if HOD)
        $hodDepartmentId = $this->getHODDepartment();
        $isHOD = $this->isHOD();
        
        // Get filters from GET or POST
        $filters = [
            'search' => $this->get('search', $this->post('search', '')),
            'status' => $isHOD ? 'Active' : $this->get('status', $this->post('status', '')),
            'district' => $this->get('district', $this->post('district', '')),
            'gender' => $this->get('gender', $this->post('gender', '')),
            'department_id' => $hodDepartmentId ? $hodDepartmentId : $this->get('department_id', $this->post('department_id', '')),
            'course_id' => $this->get('course_id', $this->post('course_id', '')),
            'academic_year' => $this->get('academic_year', $this->post('academic_year', '')),
            'group_id' => $this->get('group_id', $this->post('group_id', ''))
        ];
        
        // Get selected columns from POST
        $selectedColumns = $this->post('columns', []);
        if (empty($selectedColumns) && isset($_GET['columns'])) {
            $selectedColumns = explode(',', $_GET['columns']);
        }
        
        // Default columns if none selected
        if (empty($selectedColumns)) {
            $selectedColumns = ['student_id', 'student_fullname', 'student_email', 'student_nic', 'student_gender', 'student_status'];
        }
        
        // Get all students without pagination for export
        $students = $studentModel->getStudents(1, 10000, $filters); // Get up to 10000 records
        
        // Define available columns with labels
        $availableColumns = [
            'student_id' => 'Student ID',
            'student_title' => 'Title',
            'student_fullname' => 'Full Name',
            'student_ininame' => 'Name with Initials',
            'student_gender' => 'Gender',
            'student_civil' => 'Civil Status',
            'student_email' => 'Email',
            'student_nic' => 'NIC',
            'student_dob' => 'Date of Birth',
            'student_phone' => 'Phone',
            'student_address' => 'Address',
            'student_zip' => 'ZIP Code',
            'student_district' => 'District',
            'student_divisions' => 'Divisions',
            'student_provice' => 'Province',
            'student_blood' => 'Blood Group',
            'student_em_name' => 'Emergency Contact Name',
            'student_em_address' => 'Emergency Contact Address',
            'student_em_phone' => 'Emergency Contact Phone',
            'student_em_relation' => 'Emergency Contact Relation',
            'student_status' => 'Status',
            'allowance_eligible' => 'Allowance Eligible',
            'course_name' => 'Course',
            'department_name' => 'Department',
            'academic_year' => 'Academic Year',
            'enrollment_status' => 'Enrollment Status'
        ];
        
        // Get enrollment data for students
        $enrollmentModel = $this->model('StudentEnrollmentModel');
        $studentsWithEnrollment = [];
        
        foreach ($students as $student) {
            $enrollment = $enrollmentModel->getCurrentEnrollment($student['student_id']);
            $student['course_name'] = $enrollment['course_name'] ?? '';
            $student['department_name'] = $enrollment['department_name'] ?? '';
            $student['academic_year'] = $enrollment['academic_year'] ?? '';
            $student['enrollment_status'] = $enrollment['student_enroll_status'] ?? '';
            $studentsWithEnrollment[] = $student;
        }
        
        // Set headers for Excel download (CSV format for Excel compatibility)
        $filename = 'students_export_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        // Add BOM for UTF-8 Excel compatibility
        echo "\xEF\xBB\xBF";
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Write header row
        $headers = [];
        foreach ($selectedColumns as $col) {
            if (isset($availableColumns[$col])) {
                $headers[] = $availableColumns[$col];
            }
        }
        fputcsv($output, $headers);
        
        // Write data rows
        foreach ($studentsWithEnrollment as $student) {
            $row = [];
            foreach ($selectedColumns as $col) {
                if (isset($availableColumns[$col])) {
                    $value = $student[$col] ?? '';
                    // Format boolean values
                    if ($col === 'allowance_eligible') {
                        $value = $value ? 'Yes' : 'No';
                    }
                    // Format date values
                    if ($col === 'student_dob' && !empty($value)) {
                        $value = date('Y-m-d', strtotime($value));
                    }
                    $row[] = $value;
                }
            }
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}

