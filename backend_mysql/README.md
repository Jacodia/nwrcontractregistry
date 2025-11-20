# backend_mysql

Minimal PHP backend configured for MySQL (XAMPP) for the nwrcontractregistry project.

Drop this folder into your XAMPP htdocs (already located here in the repo) and configure the DB settings in `config/db.php` if needed.

Default DB settings (XAMPP):
- host: 127.0.0.1
- dbname: nwrcontractregistry
- user: root
- password: (empty)

Endpoints
- `login.php` (POST JSON) {"username":"...","password":"..."} — returns session cookie and JSON success
- `get_contracts.php` (GET) — returns JSON list of contracts
- `add_contract.php` (POST JSON) {"title":"...","party":"...","start_date":"YYYY-MM-DD","end_date":"YYYY-MM-DD","notes":"..."}

Notes
- The project assumes there exists a `users` table and a `contracts` table. If your schema differs, either update the SQL or adjust model queries in `models/`.
- Authentication is session-based for simplicity. For API use, prefer token-based auth; you can extend `config/auth.php` to add tokens.
- CORS headers are permissive (for local dev). Tighten them for production.

Quick test (using PowerShell / curl):

curl -X GET "http://localhost/nwrcontractregistry/backend_mysql/get_contracts.php"

Or (login)
curl -X POST -H "Content-Type: application/json" -d '{"username":"admin","password":"secret"}' "http://localhost/nwrcontractregistry/backend_mysql/login.php" -c cookies.txt

Then use cookies.txt for subsequent requests.
