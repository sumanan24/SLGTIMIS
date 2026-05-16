<?php
/**
 * Home Controller
 */

class HomeController extends Controller {
    
    public function index() {
        // If user is logged in, redirect to their appropriate dashboard
        if (isset($_SESSION['user_id'])) {
            $userTable = $_SESSION['user_table'] ?? 'student';
            require_once BASE_PATH . '/models/UserModel.php';
            $userModel = new UserModel();
            
            if ($userTable === 'student') {
                $this->redirect('student/dashboard');
                return;
            } elseif ($userModel->isHOD($_SESSION['user_id'])) {
                $this->redirect('hod/dashboard');
                return;
            } else {
                $this->redirect('dashboard');
                return;
            }
        }
        
        // Get departments for NVQ career path display
        $departmentModel = $this->model('DepartmentModel');
        $departments = $departmentModel->getAll();
        
        require_once BASE_PATH . '/core/SeoHelper.php';
        $seoCfg = SeoHelper::config();

        $data = [
            'title' => 'Student Information System',
            'page' => 'home',
            'departments' => $departments ?? [],
            'seo_description' => $seoCfg['home_description'],
            'seo_keywords' => $seoCfg['home_keywords'],
            'seo_robots' => 'index, follow',
        ];
        
        return $this->view('home/index', $data);
    }
    
    public function notFound() {
        http_response_code(404);
        $data = [
            'title' => '404 - Page Not Found',
            'page' => '404'
        ];
        
        return $this->view('errors/404', $data);
    }
}

