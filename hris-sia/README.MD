# HRIS SIA
## Overview
Project - HRIS - Subsystem

## Features
- Employee management (CRUD)
- Attendance tracking
- Leave requests and approvals
- Basic reporting and exports

## Credits
- Front-End by Justin Rivera
- Back-End by Augoste Flores

## Installation (local)
1. Copy project to XAMPP htdocs:
    - Place repository in `C:\xampp\htdocs\hris-sia`
2. Start XAMPP (Apache + MySQL).
3. Create a database:
    - Use phpMyAdmin or CLI to create `hris_sia` (or name of your choice).
4. Import schema:
    - Import provided SQL dump (e.g., `database/dump.sql`) into the database.
5. Configure database connection:
    - Edit config file (e.g., `config.php`, `.env` or `app/config/database.php`) with DB host, name, user, password.
6. Install PHP dependencies (if applicable):
    - `composer install`
7. Open in browser:
    - http://localhost/hris-sia

## Usage
- Default entry point: `index.php` or `public/`
- Admin and user roles managed via the database — create users directly or through registration if available.

## Development
- Follow PSR standards where applicable.
- Run PHP built-in server for quick testing:
  - `php -S localhost:8000 -t public` (if project uses `public` folder)

## Tests
- Add test instructions here if test suite exists (PHPUnit, etc.).

## Project structure (example)
- /app — application code
- /public — web root (assets, index)
- /config — configuration files
- /database — migrations / SQL dumps
- /tests — automated tests
- README.MD — this file

## Contributing
- Fork → branch → PR
- Keep changes small and documented
- Write tests for new features
