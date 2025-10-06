# Web Project

## Overview
This project is a web application that consists of a backend built with PHP and a frontend using HTML, CSS, and JavaScript. The backend handles the server-side logic and data management, while the frontend provides the user interface.

## Directory Structure
```
nwrcontractregistry  
├── backend                         # Server-side logic and APIs  
│   ├── config                      # Database + authentication configs  
│   │   ├── db.php  
│   │   └── auth.php  
│   ├── controllers                 # Handles requests (contracts, users, etc.)  
│   │   ├── ContractController.php  
│   │   ├── ContractTypeController.php  
│   │   └── UserController.php  
│   ├── models                      # Data models (ORM / entities)  
│   │   ├── Contract.php  
│   │   └── User.php  
│   ├── logs                        # Email notification logs  
│   │   └── reminder_log.txt  
│   ├── tests/                      # Backend testing suite  
│   ├── uploads/                    # Stores uploaded contracts (PDFs, docs)  
│   ├── ContractNotifier.php        # Email notification system  
│   ├── send_30.php                 # 30-day reminder script  
│   ├── send_60.php                 # 60-day reminder script  
│   ├── send_90.php                 # 90-day reminder script  
│   ├── cron_notify.php             # Automated notification scheduler  
│   ├── auth_handler.php            # Authentication handler  
│   ├── login.php                   # Login logic  
│   ├── logout.php                  # Logout logic  
│   └── index.php                   # Backend entry point  
│  
├── frontend                        # Client-facing UI  
│   ├── css                         # Stylesheets  
│   │   ├── dashboard.css  
│   │   ├── index.css  
│   │   ├── manage_contract.css  
│   │   ├── style.css               # Main stylesheet  
│   │   └── users.css               # TBD
│   ├── js                          # JavaScript for interactivity  
│   │   ├── dashboard.js           
│   │   ├── index.js                # TBD
│   │   ├── manage_contract.js      # TBD
│   │   └── users.js                # TBD
│   ├── pages                       # Application pages (dashboard, users, etc.)  
│   │   ├── dashboard.html  
│   │   ├── manage_contract.html  
│   │   └── users.php  
│   └── index.php                   # Frontend entry (login/signup UI)  
│  
├── scripts                         # Python automation/reporting  
│   ├── contractregistry.py  
│   └── reports.py  
│  
├── diagrams                        # System & project design diagrams  
│   ├── app_flow.drawio  
│   ├── user_flow.drawio  
│   ├── erd.drawio  
│   └── use_case.drawio  
│  
├── requirements.txt                # Python dependencies (optional)  
└── README.md                       # Project documentation    
```

## Project Structure Overview

| Path / File                        | Description |
|------------------------------------|-------------|
| **backend/**                       | Handles server-side logic and APIs |
| ├── config/db.php                  | Database connection setup |
| ├── config/auth.php                | Authentication logic |
| ├── controllers/ContractController.php | Contract CRUD operations |
| ├── controllers/ContractTypeController.php | Handles contract type operations |
| ├── controllers/UserController.php | User management actions |
| ├── models/Contract.php            | Contract data model |
| ├── models/User.php                | User data model |
| ├── logs/reminder_log.txt          | Email notification activity logs |
| ├── tests/                         | Comprehensive testing suite |
| ├── uploads/                       | Stores uploaded contracts (PDFs, docs) |
| ├── ContractNotifier.php           | Email notification system |
| ├── send_30.php                    | 30-day contract reminder notifications |
| ├── send_60.php                    | 60-day contract reminder notifications |
| ├── send_90.php                    | 90-day contract reminder notifications |
| ├── cron_notify.php                | Automated notification scheduler |
| ├── auth_handler.php               | Handles authentication sessions |
| ├── login.php                      | Login page (backend logic) |
| ├── logout.php                     | Logout handler |
| └── index.php                      | Backend entry point |
| **frontend/**                      | Client-facing UI |
| ├── index.php                      | Main entry point (login/signup UI) |
| ├── css/dashboard.css              | Dashboard styling |
| ├── css/index.css                  | Index (login/signup) styling |
| ├── css/manage_contract.css        | Manage contracts styling |
| ├── css/style.css                  | General/global stylesheet |
| ├── css/users.css                  | User management styling |
| ├── js/dashboard.js                | Dashboard logic |
| ├── js/index.js                    | Index/login logic |
| ├── js/manage_contract.js          | Manage contract interactivity |
| ├── js/users.js                    | User management interactivity |
| ├── pages/dashboard.html           | Dashboard UI |
| ├── pages/manage_contract.html     | Manage contracts UI |
| └── pages/users.php                | Manage users |
| **scripts/**                       | Python automation/reporting scripts |
| ├── contractregistry.py            | Automation logic for contracts |
| └── reports.py                     | Reporting script |
| **diagrams/**                      | System design documentation |
| ├── app_flow.drawio                | Application flow diagram |
| ├── user_flow.drawio               | User interaction flow |
| ├── erd.drawio                     | Entity Relationship Diagram |
| └── use_case.drawio                | Use case diagram |
| **requirements.txt**               | Python dependencies (optional) |
| **README.md**                      | Project documentation |



## Backend
- **index.php**: Handles requests and routes to the appropriate controller.
- **controllers/**: Contains request handlers for features like contract creation, update, and deletion.
- **models/**: Defines PHP classes for database entities (e.g., Contract, User)
- **ContractNotifier.php**: Email notification system for contract expiration reminders
- **Email System**: Automated notifications for contracts expiring in 30, 60, and 90 days
- **logs/**: Tracks email notification activity and system operations


## Frontend
- **index.html**: Landing page.
- **css/style.css**: Styles for the frontend application, defining the visual appearance.
- **js/app.js**: JavaScript code for handling user interactions and dynamic content updates.
- **pages/**: Extra views like dashboard and contract forms.

## Email Notification System
The system includes an automated email notification feature for contract expiration reminders:

### Features
- **30-Day Notifications**: Daily reminders for contracts expiring in 1-30 days
- **60-Day Notifications**: Twice-weekly reminders for contracts expiring in 31-60 days  
- **90-Day Notifications**: Weekly reminders for contracts expiring in 61-90 days

### Configuration
- **SMTP Server**: Gmail (smtp.gmail.com:587) with TLS encryption
- **Authentication**: App password authentication for security
- **Recipients**: Contract managers receive personalized notifications
- **Logging**: All email activities tracked in `backend/logs/reminder_log.txt`

### Email Testing
The system includes comprehensive testing capabilities:
- ✅ Basic email functionality tests
- ✅ Contract notification system verification
- ✅ Multi-category reminder testing (30/60/90 days)
- ✅ Email delivery confirmation and logging



## Setup Instructions
1. Clone the repository to your local machine.
2. Configure email settings in `.env` file for notification system

### Prerequisites
- **Composer**: PHP dependency manager required for email functionality
  - Download from [getcomposer.org](https://getcomposer.org/download/)
  - **Windows**: Download and run `Composer-Setup.exe`
  - **macOS/Linux**: Use the command line installer:
    ```bash
    php -r "copy('https://getcomposer.org/installer', 'composer-phar.php');"
    php composer-phar.php
    php -r "unlink('composer-phar.php');"
    ```
  - Verify installation: `composer --version`

### Option 1: Run in XAMPP
1. Install [XAMPP][def]
2. Copy `nwrcontractregistry/` folder into your `htdocs/` directory (e.g., `C:/xampp/htdocs/nwrcontractregistry`).
3. Start Apache and MySQL services
4. **Install PHP dependencies**:
   ```bash
   cd C:/xampp/htdocs/nwrcontractregistry
   composer install
   ```
   This will install:
   - PHPMailer (email functionality)
   - vlucas/phpdotenv (environment configuration)
   - Other required dependencies listed in `composer.json`
5. Configure database connection in `backend/config/db.php`
6. Set up email credentials in `.env` file
7. **Configure environment settings**:
   ```php
   // Add to your PHP files for environment-based error handling
   if ($_ENV['APP_ENV'] === 'development') {
       ini_set('display_errors', 1);
       ini_set('display_startup_errors', 1);
       error_reporting(E_ALL);
   } else {
       ini_set('display_errors', 0);
       error_reporting(0);
   }
   ```
8. Access the project in a browser:
``http://localhost/nwrcontractregistry/frontend/index.html
http://localhost/nwrcontractregistry/backend/index.php``


## Usage
- **Frontend**: Navigate through pages for contract management and user administration
- **Backend**: API endpoints accessible at `http://localhost/nwrcontractregistry/backend/index.php`
- **Email Notifications**: Automated contract expiration reminders sent to managers
- **File Uploads**: Support for PDF, DOC, and DOCX contract documents (5MB limit)
- **User Roles**: Admin, Manager, and User access levels with appropriate permissions

## Testing
The system includes comprehensive testing capabilities in the `backend/tests/` folder:
- Email functionality verification
- Contract notification system testing
- File upload/download validation
- Database connectivity checks
- User authentication testing

## Troubleshooting

### Composer Issues
- **"composer not found"**: Ensure Composer is installed and added to your system PATH
- **Permission errors**: Run command prompt as Administrator (Windows) or use `sudo` (macOS/Linux)
- **SSL/TLS errors**: Update Composer with `composer self-update`
- **Memory limit errors**: Increase PHP memory limit in `php.ini`: `memory_limit = 512M`

### Email Issues
- Verify SMTP credentials in `.env` file
- Check Gmail app password configuration
- Ensure ports 587 (TLS) or 465 (SSL) are not blocked by firewall
- Review `backend/logs/reminder_log.txt` for detailed error messages

### Environment Configuration
- **Development Mode**: Set `APP_ENV=development` in `.env` for debugging
- **Production Mode**: Set `APP_ENV=production` in `.env` for live deployment
- **Error Handling**: The system automatically adjusts error reporting based on environment:
  ```php
  // Development: Shows all errors and warnings
  // Production: Hides errors from end users
  if ($_ENV['APP_ENV'] === 'development') {
      ini_set('display_errors', 1);
      ini_set('display_startup_errors', 1);
      error_reporting(E_ALL);
  } else {
      ini_set('display_errors', 0);
      error_reporting(0);
  }
  ```

## Contributing
Feel free to submit issues or pull requests for improvements or bug fixes.

[def]: https://www.apachefriends.org/