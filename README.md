# EventFlow AI - Event Scheduler Application

A modern, glassy Event Management application with AI-powered features. Built with native PHP and MySQL, designed for premium performance and aesthetics.

## Key Features

- **.env Configuration**: Security first. All sensitive data (database, OpenAI keys) is now managed through environment variables.
- **Enhanced Authentication**: Fully database-backed login system with `password_hash` and `password_verify` for maximum security.
- **AI-Powered descriptions**: Integrated OpenAI support to generate catchy event descriptions automatically.
- **Glassmorphism UI**: High-end aesthetic with backdrop blurs, vibrant gradients, and smooth interactions.
- **Event Management**: Complete CRUD operations for events.
- **Status & Invitations**: Real-time attendance tracking and user-to-user invitation system.
- **Responsive Design**: Works beautifully across desktop and mobile devices.

## Demo Credentials

> [!NOTE]
> Run `php database/seed.php` to populate your database with these demo accounts.

| Role      | Username     | Password          |
| :-------- | :----------- | :---------------- |
| **Admin** | `admin_demo` | `admin_pass_2026` |
| **User**  | `user_demo`  | `user_pass_2026`  |

---

## Local Deployment (XAMPP/WAMP)

1. **Clone the Repository**:
   ```bash
   git clone <repository-url>
   ```
2. **Setup Environment**:
   - Copy `.env.example` to `.env`.
   - Update `DB_NAME`, `DB_USER`, and `DB_PASS` in `.env`.
   - `BASE_URL` is auto-detected, but can be overridden in `.env` if needed.
   - Add your `OPENAI_API_KEY` to enable AI features.
3. **Database Setup**:
   - Create a database named `event_scheduler`.
   - Import `database/schema.sql` into your database.
4. **Seed Data**:
   - Run `php database/seed.php` from your terminal or visit the file in your browser to create demo users.
5. **Access App**:
   - Open your application's URL in your browser.

---

## Live Deployment (cPanel)

### Option 1: Manual Upload (FTP/File Manager)

1. **Prepare Files**: Zip the project folder (excluding `.git` and `vendor` if any).
2. **Upload**: Use cPanel File Manager to upload the zip to `public_html`.
3. **Extract**: Extract the files in the target directory.
4. **Database**:
   - Create a MySQL Database and User in cPanel.
   - Assign the user to the database with all privileges.
   - Import `database/schema.sql` via phpMyAdmin.
5. **Configure**:
   - Create/Edit the `.env` file in the root folder with your production database credentials.

### Option 2: Git Deployment (Preferred)

1. **GitHub/GitLab**: Push your code to a private or public repository.
2. **cPanel Git™ Version Control**:
   - Open the "Git™ Version Control" tool in cPanel.
   - Click "Create" and enter your Repository URL.
   - Set the Deployment Path (e.g., `public_html/event-app`).
3. **Deploy**:
   - Click "Manage" > "Deploy Head Revision" to pull the latest changes.
4. **Environment**:
   - Ensure you create the `.env` file manually on the server as it is gitignored for security.

---

## Technical Details

- **Backend**: Native PHP 8.x
- **Frontend**: Vanilla CSS (Glassmorphism), JavaScript
- **AI Integration**: OpenAI GPT-4o-mini
- **Configuration**: custom `.env` loader in `includes/config.php`
