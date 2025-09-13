# Web Project

## Overview
This project is a web application that consists of a backend built with PHP and a frontend using HTML, CSS, and JavaScript. The backend handles the server-side logic and data management, while the frontend provides the user interface.

## Directory Structure
```
nwrcontractregistry
├── backend
│   ├── config
│   │   └── db.php                # Database connection
│   ├── controllers
│   │   └── ContractController.php
│   ├── models
│   │   └── Contract.php
│   ├── uploads/                  # Stores uploaded contracts (PDFs, etc.)
│   └── index.php                 # Backend entry point
│
├── frontend
│   ├── index.html                 # Main UI
│   ├── css
│   │   └── style.css
│   ├── js
│   │   └── app.js
│   └── pages
│       ├── dashboard.html
│       ├── add_contract.html
│       └── edit_contract.html
│
├── scripts
│   ├── contractregistry.py
│   └── reports.py
│
├── requirements.txt               # Python dependencies (optional)
├── README.md
```


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