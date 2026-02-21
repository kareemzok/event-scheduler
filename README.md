# EventFlow AI - Event Scheduler Application

A modern, glassy Event Management application with AI-powered features. Built with PHP and MySQL.

## Features

- **Glassmorphism UI**: High-end aesthetic with backdrop blurs and vibrant gradients.
- **AI Magic**: Generate professional event descriptions instantly using AI.
- **Event Management**: CRUD (Create, Read, Update, Delete) for events.
- **Status Tracking**: Mark your attendance as "Upcoming," "Attending," "Maybe," or "Declined."
- **Invitations**: Invite users to your events and manage incoming invites.
- **Search**: Real-time search by title, location, or description.
- **Multi-Role Auth**: Login as a normal user or Admin.
- **Profile Management**: Update your bio and email.

## Setup Instructions

1.  **Clone / Copy** the project to your local web server (e.g., XAMPP/WAMP htdocs).
2.  **Database Setup**:
    - Open your MySQL management tool (e.g., phpMyAdmin).
    - Import the SQL file located at `/database/schema.sql`.
    - This will create the `event_scheduler` database and necessary tables.
3.  **Configure**:
    - Open `/includes/config.php` and update `DB_USER` and `DB_PASS` if they differ from the defaults (root, "").
4.  **Run**:
    - Navigate to `http://localhost/event-scheduler-app/` in your browser.
5.  **Test Accounts**:
    - **Admin**: Register a user and manually change their role to 'admin' in the database (default is 'user').

## Creativity & AI

- **AI Smart Description**: Found in the "Create Event" modal. It simulates a professional AI assistant that helps you write catchy event copy.
- **Glassy Design**: Every element Uses advanced CSS `backdrop-filter` to feel premium and alive.
