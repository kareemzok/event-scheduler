<?php
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Only keep tab switching and unique dashboard animations here */
        .ai-loading {
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

        /* Ensure specific overrides for dashboard grid if needed */
    </style>
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar glass-container">
            <div class="sidebar-brand">EventFlow AI</div>
            <nav id="main-nav">
                <a class="nav-link active" onclick="switchTab('dashboard')">Dashboard</a>
                <a class="nav-link" onclick="switchTab('invitations')">Invitations</a>
                <a class="nav-link" onclick="openProfile()">Profile Settings</a>
            </nav>
            <div class="user-profile-section">
                <div class="user-info-card">
                    <small class="user-label">Logged in as</small>
                    <strong id="display-username"><?php echo $_SESSION['username']; ?></strong>
                    <span class="role-badge">
                        <?php echo strtoupper($_SESSION['role'] ?? 'user'); ?>
                    </span>
                </div>
                <a href="api/auth.php?action=logout" class="logout-link">Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <div id="tab-dashboard">
                <div class="header-content">
                    <div>
                        <h1>Events Hub</h1>
                        <p style="color: var(--text-muted);">Discover and manage your event schedule.</p>
                    </div>
                    <div class="search-wrapper">
                        <input type="text" id="event-search" class="search-input"
                            placeholder="Search title, location...">
                    </div>
                    <button class="btn" style="width: auto; padding: 12px 24px;" id="btn-add-event">+ Create
                        Event</button>
                </div>

                <div class="ai-builder-section">
                    <h3 style="display: flex; align-items: center; gap: 8px;">🚀 AI Event Builder</h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">Describe your event and let AI set it up
                        for you!</p>
                    <div class="ai-builder-input">
                        <input type="text" id="ai-builder-prompt" class="form-control"
                            placeholder="e.g. A networking dinner for developers next Friday at 7pm in Downtown...">
                        <button class="btn ai-magic-btn" id="btn-ai-build"
                            style="width: auto; white-space: nowrap;">Build Event</button>
                    </div>
                </div>

                <div class="event-grid" id="event-list">
                    <!-- Events load here -->
                </div>
            </div>

            <div id="tab-invitations" style="display: none;">
                <h1 style="margin-bottom: 20px;">Pending Invitations</h1>
                <div class="event-grid" id="invite-list">
                    <!-- Invites load here -->
                </div>
            </div>
        </main>
    </div>

    <!-- Create Event Modal -->
    <div class="modal" id="modal-event">
        <div class="glass-container modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 id="modal-title">Create New Event</h2>
                <button class="btn ai-magic-btn" id="btn-ai-magic">🪄 AI Magic</button>
            </div>

            <div class="form-group">
                <label>Choose a Template</label>
                <div class="template-selector">
                    <div class="template-btn"
                        onclick="applyTemplate('Meeting', 'Office HQ', 'Project sync and strategy session.')">
                        <span>💼</span> <label>Meeting</label>
                    </div>
                    <div class="template-btn"
                        onclick="applyTemplate('Workshop', 'Tech Hub', 'Hands-on learning and skill building.')">
                        <span>🛠️</span> <label>Workshop</label>
                    </div>
                    <div class="template-btn"
                        onclick="applyTemplate('Party', 'The Lounge', 'Celebration and social gathering!')">
                        <span>🎉</span> <label>Party</label>
                    </div>
                    <div class="template-btn"
                        onclick="applyTemplate('Conference', 'Grand Hall', 'Keynote speeches and networking.')">
                        <span>🏛️</span> <label>Conference</label>
                    </div>
                </div>
            </div>

            <form id="form-create-event">
                <input type="hidden" name="id" id="event-id">
                <div class="form-group">
                    <label>Event Title</label>
                    <input type="text" id="ev-title" name="title" class="form-control" required
                        placeholder="e.g. AI Innovation Summit">
                </div>
                <div class="form-group">
                    <label>Date & Time</label>
                    <input type="datetime-local" id="ev-date" name="event_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" id="ev-location" name="location" class="form-control"
                        placeholder="e.g. Metaverse / Office">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="ev-desc" name="description" class="form-control" rows="3"
                        placeholder="Tell us about the event..."></textarea>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-danger" onclick="closeModal('modal-event')">Cancel</button>
                    <button type="submit" class="btn">Save Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Invite Modal -->
    <div class="modal" id="modal-invite">
        <div class="glass-container modal-content">
            <h2 style="margin-bottom: 20px;">Invite Someone</h2>
            <form id="form-invite-user">
                <input type="hidden" name="event_id" id="invite-event-id">
                <div class="form-group">
                    <label>Username or Email</label>
                    <input type="text" name="username" class="form-control" required
                        placeholder="Who do you want to invite?">
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-danger" onclick="closeModal('modal-invite')">Cancel</button>
                    <button type="submit" class="btn">Send Invitation</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Profile Modal -->
    <div class="modal" id="modal-profile">
        <div class="glass-container modal-content">
            <h2 style="margin-bottom: 20px;">My Profile</h2>
            <form id="form-profile">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" id="prof-email" class="form-control">
                </div>
                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio" id="prof-bio" class="form-control" rows="3"></textarea>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-danger" onclick="closeModal('modal-profile')">Close</button>
                    <button type="submit" class="btn">Update Profile</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const eventList = document.getElementById('event-list');
        const inviteList = document.getElementById('invite-list');
        const eventSearch = document.getElementById('event-search');

        // Navigation
        function switchTab(tab) {
            document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
            event.target.classList.add('active');

            document.getElementById('tab-dashboard').style.display = tab === 'dashboard' ? 'block' : 'none';
            document.getElementById('tab-invitations').style.display = tab === 'invitations' ? 'block' : 'none';

            if (tab === 'invitations') fetchInvitations();
            else fetchEvents();
        }

        // Modals
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        document.getElementById('btn-add-event').onclick = () => {
            document.getElementById('form-create-event').reset();
            document.getElementById('event-id').value = '';
            document.getElementById('modal-title').innerText = 'Create New Event';
            document.getElementById('modal-event').classList.add('active');
        };

        // Fetch Events
        async function fetchEvents(query = '') {
            const resp = await fetch(`api/events.php?action=list&q=${query}`);
            const data = await resp.json();
            renderEvents(data);
        }

        function renderEvents(events) {
            if (events.length === 0) {
                eventList.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 100px; color: var(--text-muted);">No events found.</div>';
                return;
            }
            eventList.innerHTML = events.map(e => `
                <div class="glass-container event-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                        <div class="event-tag">${e.location || 'Online'}</div>
                        <span class="status-badge status-${e.user_status || 'invited'}">${e.user_status || 'Invited'}</span>
                    </div>
                    <h3 class="event-title">${e.title}</h3>
                    <div class="event-info">
                        <span>📅 ${new Date(e.event_date).toLocaleString()}</span>
                        <span>👤 Created by: ${e.creator_name}</span>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 15px;">${e.description || 'No description.'}</p>
                    
                    <div class="status-actions">
                        <button class="btn-status ${e.user_status === 'attending' ? 'active' : ''}" onclick="updateStatus(${e.id}, 'attending')">Attending</button>
                        <button class="btn-status ${e.user_status === 'maybe' ? 'active' : ''}" onclick="updateStatus(${e.id}, 'maybe')">Maybe</button>
                        <button class="btn-status ${e.user_status === 'declined' ? 'active' : ''}" onclick="updateStatus(${e.id}, 'declined')">Decline</button>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 5px; margin-top: 20px; border-top: 1px solid var(--glass-border); padding-top: 15px;">
                        <button class="btn" style="width: auto; padding: 5px 10px; font-size: 0.7rem;" onclick="openInvite(${e.id})">Invite</button>
                        <button class="btn" style="width: auto; padding: 5px 10px; font-size: 0.7rem; background: rgba(255,255,255,0.1);" onclick="editEvent(${e.id})">Edit</button>
                        <button class="btn" style="width: auto; padding: 5px 10px; font-size: 0.7rem; background: #ef4444;" onclick="deleteEvent(${e.id})">Del</button>
                    </div>
                </div>
            `).join('');
        }

        async function updateStatus(eventId, status) {
            const fd = new FormData();
            fd.append('event_id', eventId);
            fd.append('status', status);
            const resp = await fetch('api/events.php?action=update_status', { method: 'POST', body: fd });
            const data = await resp.json();
            if (data.success) fetchEvents();
        }

        function applyTemplate(title, loc, desc) {
            document.getElementById('ev-title').value = title;
            document.getElementById('ev-location').value = loc;
            document.getElementById('ev-desc').value = desc;
        }

        // AI Builder
        document.getElementById('btn-ai-build').onclick = async () => {
            const prompt = document.getElementById('ai-builder-prompt').value;
            if (!prompt) return alert('Tell me what event you want to build!');

            const btn = document.getElementById('btn-ai-build');
            btn.innerText = '✨ Building...';
            btn.classList.add('ai-loading');

            const fd = new FormData();
            fd.append('prompt', prompt);

            try {
                const resp = await fetch('api/ai.php?action=generate_event', { method: 'POST', body: fd });
                const data = await resp.json();

                if (data.success) {
                    const e = data.event;
                    document.getElementById('ev-title').value = e.title;
                    document.getElementById('ev-location').value = e.location;
                    document.getElementById('ev-desc').value = e.description;
                    if (e.event_date) document.getElementById('ev-date').value = e.event_date.replace(' ', 'T');
                    
                    document.getElementById('modal-event').classList.add('active');
                } else {
                    alert(data.error);
                }
            } catch (err) {
                alert('Connection error. Please check your network.');
            }
            
            btn.innerText = 'Build Event';
            btn.classList.remove('ai-loading');
        };

        // AI Magic
        document.getElementById('btn-ai-magic').onclick = async () => {
            const title = document.getElementById('ev-title').value;
            const location = document.getElementById('ev-location').value;
            if (!title) return alert('Enter a title first!');

            const btn = document.getElementById('btn-ai-magic');
            btn.innerText = '✨ Thinking...';
            btn.classList.add('ai-loading');

            const formData = new FormData();
            formData.append('title', title);
            formData.append('location', location);

            const resp = await fetch('api/ai.php?action=suggest_description', { method: 'POST', body: formData });
            const data = await resp.json();

            if (data.success) {
                document.getElementById('ev-desc').value = data.description;
            }
            btn.innerText = '🪄 AI Magic';
            btn.classList.remove('ai-loading');
        };

        // Invitations
        async function fetchInvitations() {
            const resp = await fetch('api/invitations.php?action=list_invites');
            const data = await resp.json();
            inviteList.innerHTML = data.map(e => `
                <div class="glass-container event-card">
                    <h3 class="event-title">${e.title}</h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">From: ${e.inviter}</p>
                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                        <button class="btn" onclick="respondInvite(${e.id}, 'attending')">Accept</button>
                        <button class="btn" style="background: #ef4444;" onclick="respondInvite(${e.id}, 'declined')">Decline</button>
                    </div>
                </div>
            `).join('');
        }

        async function respondInvite(id, status) {
            const fd = new FormData(); fd.append('event_id', id); fd.append('status', status);
            await fetch('api/invitations.php?action=respond', { method: 'POST', body: fd });
            fetchInvitations();
        }

        function openInvite(id) {
            document.getElementById('invite-event-id').value = id;
            document.getElementById('modal-invite').classList.add('active');
        }

        // Profile
        async function openProfile() {
            const resp = await fetch('api/profile.php?action=get_profile');
            const data = await resp.json();
            document.getElementById('prof-email').value = data.email;
            document.getElementById('prof-bio').value = data.bio;
            document.getElementById('modal-profile').classList.add('active');
        }

        document.getElementById('form-profile').onsubmit = async (e) => {
            e.preventDefault();
            await fetch('api/profile.php?action=update_profile', { method: 'POST', body: new FormData(e.target) });
            alert('Profile updated!');
            closeModal('modal-profile');
        };

        // Event CRUD
        document.getElementById('form-create-event').onsubmit = async (e) => {
            e.preventDefault();
            const id = document.getElementById('event-id').value;
            const action = id ? 'update' : 'create';
            await fetch(`api/events.php?action=${action}`, { method: 'POST', body: new FormData(e.target) });
            closeModal('modal-event');
            fetchEvents();
        };

        async function editEvent(id) {
            const resp = await fetch(`api/events.php?action=get&id=${id}`);
            const e = await resp.json();
            document.getElementById('event-id').value = e.id;
            document.getElementById('ev-title').value = e.title;
            document.getElementById('ev-date').value = e.event_date.replace(' ', 'T');
            document.getElementById('ev-location').value = e.location;
            document.getElementById('ev-desc').value = e.description;
            document.getElementById('modal-title').innerText = 'Edit Event';
            document.getElementById('modal-event').classList.add('active');
        }

        async function deleteEvent(id) {
            if (!confirm('Delete this event?')) return;
            const fd = new FormData(); fd.append('id', id);
            await fetch('api/events.php?action=delete', { method: 'POST', body: fd });
            fetchEvents();
        }

        eventSearch.oninput = (e) => fetchEvents(e.target.value);
        fetchEvents();
    </script>
</body>

</html>