# EventFlow AI - Event Scheduler Application

A modern, glassy Event Management application with AI-powered features. Built with native PHP and MySQL, designed for premium performance and aesthetics.

## Key Features

- **AI Event Builder**: Full event generation from simple natural language prompts (e.g., "A tech meetup next Friday").
- **AI-Powered Magic**: Generate engaging descriptions and catchy titles automatically using OpenAI GPT models.
- **AI Usage Control**: Smart request logging with configurable daily limits per user to manage API costs effectively.
- **Advanced Event Filtering**: Powerful multi-criteria search system filtering by title, location, date range, and RSVP status.
- **Role-Based Security**: Strict permission system where Admins can manage all content, while Users manage their own events.
- **Interactive Attendance**: Real-time RSVP system (Attending, Interested, etc.) with instant dashboard updates.
- **Invitations System**: Invite other users to your events with a dedicated notifications/invitations hub.
- **Management Dashboard**: Comprehensive CRUD operations for all your events in a sleek interface.
- **Glassmorphism UI**: Premium aesthetic with backdrop blurs, vibrant gradients, and smooth interactive micro-animations.
- **User Profiles**: Personal profile management with custom bios and account details.
- **.env Configuration**: Enterprise-grade security managing all sensitive database and API credentials via environment variables.

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
