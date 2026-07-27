# Report all Technical ICT issues by Submitting a Ticket

Web-based ticket and issue tracking solution for a single institution.

## Tech Stack
- Frontend: HTML, CSS, JavaScript
- Backend: PHP (procedural)
- Database: MySQL

## Modules
- Public (Employees, no login):
  - Employee registration using employee badge/number and profile details
  - Report issue via multi-step form with employee verification
  - Track issue by tracking code
- Admin (login required):
  - Dashboard metrics
  - Departments management
  - Users management (Admin, ICT Staff, Employees)
  - Ticket oversight and reassignment
  - Reports and analytics
- ICT Staff (login required):
  - Dashboard and workload view
  - View assigned tickets
  - Update status, comments, and resolution notes

## Setup
1. Create/import database schema:
   - Import `database/schema.sql` into MySQL.
2. Configure database credentials in `config/db.php`:
   - host, user, password, database, port.
3. Serve with PHP:
   - Place project in web root or run a local PHP server.
4. Ensure `uploads/` is writable by PHP.
5. Open app:
   - `index.php`

## Demo Login
- Admin: admin@institution.edu / pass123
- ICT Staff: ict1@institution.edu / pass123

## Important Notes
- Employees can register with their employee badge/number and login before using the support workflow.
- Email notifications use PHP `mail()` and require SMTP/server mail configuration.
- New passwords are hashed. Seeded demo passwords are still accepted by the development fallback.
