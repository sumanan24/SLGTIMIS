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

    /**
     * Public guideline videos hub — no login required.
     * Separate shareable links: /guidelines/sinhala and /guidelines/tamil
     */
    public function guidelines() {
        $videos = $this->guidelineVideoMap();
        $data = [
            'title' => 'Registration guideline videos',
            'page' => 'guidelines',
            'use_public_layout' => true,
            'seo_robots' => 'index, follow',
            'seo_description' => 'Watch SLGTI registration guideline videos in Sinhala or Tamil.',
            'videos' => $videos,
            'sinhala_url' => rtrim(APP_URL, '/') . '/guidelines/sinhala',
            'tamil_url' => rtrim(APP_URL, '/') . '/guidelines/tamil',
        ];
        return $this->view('home/guidelines', $data);
    }

    /**
     * Watch one language guideline video (sinhala|tamil). Public.
     */
    public function guidelinesWatch($lang = '') {
        $lang = strtolower(trim((string) $lang));
        if (!in_array($lang, ['sinhala', 'tamil'], true)) {
            $this->redirect('guidelines');
            return;
        }

        $videos = $this->guidelineVideoMap();
        $video = $videos[$lang] ?? null;
        $hubUrl = rtrim(APP_URL, '/') . '/guidelines';

        if ($video === null || empty($video['exists'])) {
            return $this->view('home/guidelines_watch', [
                'title' => ucfirst($lang) . ' guideline video',
                'page' => 'guidelines-watch',
                'use_public_layout' => true,
                'seo_robots' => 'noindex, follow',
                'lang' => $lang,
                'video' => null,
                'hub_url' => $hubUrl,
                'other_lang' => $lang === 'sinhala' ? 'tamil' : 'sinhala',
                'other_url' => $hubUrl . '/' . ($lang === 'sinhala' ? 'tamil' : 'sinhala'),
            ]);
        }

        return $this->view('home/guidelines_watch', [
            'title' => $video['title_en'],
            'page' => 'guidelines-watch',
            'use_public_layout' => true,
            'seo_robots' => 'index, follow',
            'seo_description' => $video['title_en'] . ' — SLGTI registration guidelines.',
            'lang' => $lang,
            'video' => $video,
            'hub_url' => $hubUrl,
            'other_lang' => $lang === 'sinhala' ? 'tamil' : 'sinhala',
            'other_url' => $hubUrl . '/' . ($lang === 'sinhala' ? 'tamil' : 'sinhala'),
        ]);
    }

    /**
     * Map language → video file under assets/video (public).
     */
    private function guidelineVideoMap(): array {
        $dir = BASE_PATH . '/assets/video';
        $base = rtrim(APP_URL, '/') . '/assets/video';
        $defs = [
            'sinhala' => [
                'file' => 'sinhala.mp4',
                'title' => 'සිංහල මාර්ගෝපදේශ වීඩියෝව',
                'title_en' => 'Sinhala guideline video',
                'label' => 'Sinhala',
                'label_local' => 'සිංහල',
            ],
            'tamil' => [
                'file' => 'tamil.mp4',
                'title' => 'தமிழ் வழிகாட்டி வீடியோ',
                'title_en' => 'Tamil guideline video',
                'label' => 'Tamil',
                'label_local' => 'தமிழ்',
            ],
        ];
        $out = [];
        foreach ($defs as $key => $d) {
            $path = $dir . '/' . $d['file'];
            $exists = is_file($path);
            $out[$key] = array_merge($d, [
                'exists' => $exists,
                'url' => $exists ? ($base . '/' . rawurlencode($d['file'])) : '',
                'watch_url' => rtrim(APP_URL, '/') . '/guidelines/' . $key,
                'bytes' => $exists ? (int) filesize($path) : 0,
            ]);
        }
        return $out;
    }
}

