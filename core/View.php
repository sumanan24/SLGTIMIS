<?php
/**
 * View Class
 */

class View {
    private $data = [];
    
    /**
     * Render a view
     */
    public function render($view, $data = []) {
        $this->data = $data;
        
        // Extract data to variables
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        $viewFile = BASE_PATH . '/views/' . $view . '.php';
        
        if (!file_exists($viewFile)) {
            throw new Exception("View file not found: $view");
        }
        
        include $viewFile;
        
        // Get the content
        $content = ob_get_clean();
        
        // Determine which layout to use based on user type
        $layoutFile = null;
        
        // Check if user is a student
        if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $layoutFile = BASE_PATH . '/views/layouts/student.php';
        } else {
            $layoutFile = BASE_PATH . '/views/layouts/main.php';
        }
        
        // Include layout if it exists
        if ($layoutFile && file_exists($layoutFile)) {
            // Extract content variable for layout
            extract(['content' => $content]);
            ob_start();
            include $layoutFile;
            return ob_get_clean();
        }
        
        return $content;
    }
    
    /**
     * Get a data value
     */
    public function get($key, $default = null) {
        return $this->data[$key] ?? $default;
    }
}

