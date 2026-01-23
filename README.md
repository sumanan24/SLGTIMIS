# SLGTI SIS - Student Information System

A modern PHP MVC-based Student Information System with a beautiful blue theme.

## Features

- **MVC Architecture**: Clean separation of concerns with Models, Views, and Controllers
- **Modern UI**: Beautiful blue-themed responsive design
- **Student Management**: View and manage student records
- **Staff Management**: View and manage staff information
- **Course Management**: View available courses and modules
- **Dashboard**: Overview of system statistics
- **Authentication**: User login system

## Installation

### Step 1: Run the Installation Script

1. Open your web browser and navigate to:
   ```
   http://localhost/SLGTIMIS/install.php
   ```

2. Fill in the database credentials:
   - **Database Host**: Usually `localhost`
   - **Database User**: Usually `root`
   - **Database Password**: Your MySQL password (leave empty if no password)
   - **Database Name**: `sis` (or your preferred name)

3. Click "Install Database" to:
   - Create the database
   - Import all tables from `sis.sql`
   - Generate configuration file

### Step 2: Access the Application

After installation, navigate to:
```
http://localhost/SLGTIMIS/
```

### Step 3: Security

**Important**: After installation, delete or rename `install.php` for security:
```bash
# Option 1: Delete
rm install.php

# Option 2: Rename
mv install.php install.php.bak
```

## Project Structure

```
SLGTIMIS/
├── assets/
│   └── css/
│       └── style.css          # Blue theme styles
├── config/
│   ├── database.php           # Database configuration
│   └── routes.php             # Application routes
├── core/
│   ├── Controller.php         # Base controller class
│   ├── Database.php           # Database connection class
│   ├── Model.php              # Base model class
│   ├── Router.php             # Routing system
│   └── View.php               # View rendering class
├── controllers/
│   ├── AuthController.php     # Authentication
│   ├── CourseController.php   # Course management
│   ├── DashboardController.php # Dashboard
│   ├── HomeController.php    # Home page
│   ├── StaffController.php   # Staff management
│   └── StudentController.php  # Student management
├── models/
│   ├── CourseModel.php        # Course data access
│   ├── StaffModel.php         # Staff data access
│   └── StudentModel.php      # Student data access
├── views/
│   ├── layouts/
│   │   └── main.php           # Main layout template
│   ├── auth/
│   │   └── login.php          # Login page
│   ├── courses/
│   │   └── index.php          # Courses list
│   ├── dashboard/
│   │   └── index.php          # Dashboard
│   ├── errors/
│   │   └── 404.php            # 404 error page
│   ├── home/
│   │   └── index.php          # Home page
│   ├── staff/
│   │   └── index.php          # Staff list
│   └── students/
│       ├── index.php          # Students list
│       └── view.php           # Student details
├── index.php                  # Application entry point
├── install.php                # Installation script
├── sis.sql                    # Database SQL file
└── README.md                  # This file
```

## Usage

### Accessing Pages

- **Home**: `http://localhost/SLGTIMIS/` or `/home`
- **Dashboard**: `/dashboard`
- **Students**: `/students`
- **Staff**: `/staff`
- **Courses**: `/courses`
- **Login**: `/login`

### Adding New Features

1. **Create a Model**: Add a new file in `models/` extending the `Model` class
2. **Create a Controller**: Add a new file in `controllers/` extending the `Controller` class
3. **Create Views**: Add view files in `views/` directory
4. **Add Routes**: Update `config/routes.php` with new routes

### Example: Adding a New Page

1. Create controller: `controllers/ExampleController.php`
```php
class ExampleController extends Controller {
    public function index() {
        $data = ['title' => 'Example Page'];
        return $this->view('example/index', $data);
    }
}
```

2. Create view: `views/example/index.php`
```php
<div class="container">
    <div class="card">
        <h2>Example Page</h2>
    </div>
</div>
```

3. Add route in `config/routes.php`:
```php
'example' => 'ExampleController@index',
```

## Database

The system uses MySQL/MariaDB. The database structure includes:

- **academic**: Academic year management
- **student**: Student information
- **staff**: Staff information
- **course**: Course details
- **module**: Module/course modules
- **attendance**: Attendance records
- **assessments**: Assessment records
- **user**: User authentication

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.2 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for clean URLs)

## Theme

The application uses a modern blue theme with:
- Primary color: `#2563eb` (Blue)
- Secondary color: `#1e40af` (Dark Blue)
- Responsive design
- Modern UI components

## Support

For issues or questions, please refer to the code comments or contact your system administrator.

## License

This is a custom Student Information System for SLGTI.

