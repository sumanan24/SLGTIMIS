<?php
/**
 * Home Controller
 */

class HomeController extends Controller {
    
    public function index() {
        // Get departments for NVQ career path display
        $departmentModel = $this->model('DepartmentModel');
        $departments = $departmentModel->getAll();
        
        $data = [
            'title' => 'Welcome to SLGTI SIS',
            'page' => 'home',
            'departments' => $departments ?? []
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

