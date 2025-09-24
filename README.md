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
│   ├── tests                       # Backend tests  
│   ├── uploads/                    # Stores uploaded contracts (PDFs, docs)  
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
| ├── tests/                         | Unit/integration tests for backend |
| ├── uploads/                       | Stores uploaded contracts (PDFs, docs) |
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
- **models/**: Defines PHP classes for database entities (e.g., Cotract, User)


## Frontend
- **index.html**: Landing page.
- **css/style.css**: Styles for the frontend application, defining the visual appearance.
- **js/app.js**: JavaScript code for handling user interactions and dynamic content updates.
- **pages/**: Extra views like dashboard and contract forms.



## Setup Instructions
1. Clone the repository to your local machine.

### Option 1: Run in XAMPP
1. Install [XAMPP][def]
2. Copy `nwrcontractregistry/`folder into your `htdocs/` directory (e.g., `C:/xampp/htdocs/TrackingSys`).
3. Start Apache (and MySQL (not neccessary for now))
4. Access the project in a browser:
``http://localhost/nwrcontractregistry/frontend/index.html
http://localhost/nwrcontractregistry/backend/index.php``


## Usage
- Frontend: Navigates through pages.
- Backend: Can be tested separately at `http://localhost/nwrcontractregistry/backend/index.php`

## Contributing
Feel free to submit issues or pull requests for improvements or bug fixes.

[def]: https://www.apachefriends.org/