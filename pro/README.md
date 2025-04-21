# Centralized Rescue Coordination Platform (CRCP)

A production-grade SAAS web application for coordinating rescue and relief agencies during disasters.

## Features

- Agency Registration & Authentication
- Interactive Real-Time Map
- Live Location Sharing
- Resource Tracking
- Emergency Alerts
- Agency Dashboard
- Admin Panel
- Secure Communication
- Mobile-Responsive UI
- Audit Logging

## Tech Stack

- Frontend: HTML, Tailwind CSS, JavaScript, jQuery
- Backend: Pure PHP
- Database: MySQL

## Setup Instructions

1. Clone the repository
2. Set up a local web server (Apache recommended)
3. Create a MySQL database named `crcp_db`
4. Import the database schema from `database/schema.sql`
5. Configure database connection in `config/database.php`
6. Install dependencies:
   ```bash
   npm install
   ```
7. Build Tailwind CSS:
   ```bash
   npm run build
   ```
8. Access the application through your web server

## Directory Structure

```
crcp/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── config/
├── database/
├── includes/
├── public/
└── src/
```

## Security Notes

- Always use HTTPS in production
- Keep database credentials secure
- Regularly backup the database
- Monitor access logs 