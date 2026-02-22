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
                        <p class="text-muted">Manage and discover your upcoming event milestones.</p>
                    </div>
                    <button class="btn btn-primary" onclick="openModal('modal-event')" style="width: auto;">+ New
                        Event</button>
                </div>

                <!-- Advanced Filter Bar -->
                <div class="glass-container filter-bar">
                    <div class="filter-group filter-search">
                        <label class="user-label">Search Title</label>
                        <input type="text" id="filter-q" class="form-control" placeholder="Search events...">
                    </div>
                    <div class="filter-group filter-location">
                        <label class="user-label">Location</label>
                        <input type="text" id="filter-location" class="form-control" placeholder="Anywhere">
                    </div>
                    <div class="filter-group filter-date">
                        <label class="user-label">Date Range</label>
                        <div class="filter-row">
                            <input type="date" id="filter-date-start" class="form-control">
                            <input type="date" id="filter-date-end" class="form-control">
                        </div>
                    </div>
                    <div class="filter-group filter-status">
                        <label class="user-label">Status</label>
                        <select id="filter-status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="invited">Invited</option>
                            <option value="attending">Attending</option>
                            <option value="maybe">Maybe</option>
                            <option value="declined">Decline</option>
                        </select>
                    </div>
                </div>

                <?php if (AI_ENABLED): ?>
                    <div class="ai-builder-section">
                        <h3 style="display: flex; align-items: center; gap: 8px;">🚀 AI Event Builder</h3>
                        <p
                            style="font-size: 0.85rem; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center;">
                            <span>Describe your event and let AI set it up for you!</span>
                            <span class="ai-limit-badge">Limit: <span id="ai-builder-remaining">...</span> left</span>
                        </p>
                        <div class="ai-builder-input">
                            <div style="flex: 1; position: relative;">
                                <input type="text" id="ai-builder-prompt" class="form-control" maxlength="250"
                                    placeholder="e.g. A networking dinner for developers next Friday at 7pm in Downtown...">
                                <small id="prompt-counter"
                                    style="position: absolute; right: 10px; bottom: -18px; font-size: 0.7rem; color: var(--text-muted);">0
                                    / 250</small>
                            </div>
                            <button class="btn ai-magic-btn" id="btn-ai-build"
                                style="width: auto; white-space: nowrap;">Build Event</button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="ai-builder-section" style="opacity: 0.7; pointer-events: none; border-style: dashed;">
                        <h3 style="display: flex; align-items: center; gap: 8px;">🚀 AI Event Builder <span class="badge"
                                style="background: var(--danger); font-size: 0.6rem;">DISABLED</span></h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted);">AI features are currently disabled by
                            administrator.</p>
                    </div>
                <?php endif; ?>

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
                <div style="display: flex; align-items: center; gap: 10px;">
                    <?php if (AI_ENABLED): ?>
                        <span class="ai-limit-badge" style="font-size: 0.75rem;">Limit: <span
                                id="ai-magic-remaining">...</span> left</span>
                        <button class="btn ai-magic-btn" id="btn-ai-magic">✨ AI Magic</button>
                    <?php else: ?>
                        <span class="badge" style="background: var(--danger); font-size: 0.75rem;">AI Magic Disabled</span>
                    <?php endif; ?>
                </div>
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

    <!-- Invite Share Modal -->
    <div class="modal" id="modal-invite-share">
        <div class="glass-container modal-content">
            <h2 style="margin-bottom: 8px;" id="invite-share-title">Invitation Link Ready</h2>
            <p id="invite-share-note" style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 16px;">
                Share this response link with the invitee.
            </p>
            <div class="form-group">
                <label>Invite Response Link</label>
                <div class="share-link-row">
                    <input type="text" id="invite-share-link" class="form-control" readonly>
                    <button type="button" class="btn btn-sm" style="width: auto;" onclick="copyInviteLink('invite-share-link')">Copy</button>
                </div>
            </div>
            <div class="social-share-group" id="invite-share-buttons"></div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-danger" onclick="closeModal('modal-invite-share')">Close</button>
            </div>
        </div>
    </div>

    <!-- Invite Link Response Modal -->
    <div class="modal" id="modal-invite-response">
        <div class="glass-container modal-content">
            <h2 style="margin-bottom: 8px;">You Have an Event Invitation</h2>
            <p id="invite-response-meta" style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 14px;"></p>
            <h3 id="invite-response-title" class="event-title" style="font-size: 1.3rem; margin-bottom: 8px;"></h3>
            <p id="invite-response-description" style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 18px;"></p>
            <div class="form-group">
                <label>Share This Invite Link</label>
                <div class="share-link-row">
                    <input type="text" id="invite-response-link" class="form-control" readonly>
                    <button type="button" class="btn btn-sm" style="width: auto;" onclick="copyInviteLink('invite-response-link')">Copy</button>
                </div>
            </div>
            <div class="social-share-group" id="invite-response-share-buttons"></div>
            <div style="display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap;">
                <button type="button" class="btn" style="width: auto;" onclick="respondFromInviteLink('attending')">Accept</button>
                <button type="button" class="btn" style="width: auto;" onclick="respondFromInviteLink('maybe')">Maybe</button>
                <button type="button" class="btn" style="width: auto; background: #ef4444;" onclick="respondFromInviteLink('declined')">Decline</button>
                <button type="button" class="btn btn-danger" style="margin-left: auto; width: auto;" onclick="closeModal('modal-invite-response')">Close</button>
            </div>
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
        const currentUser = {
            id: <?php echo $_SESSION['user_id']; ?>,
            role: '<?php echo $_SESSION['role'] ?? 'user'; ?>'
        };
        const AI_ENABLED = <?php echo AI_ENABLED ? 'true' : 'false'; ?>;
        const inviteTokenFromUrl = new URLSearchParams(window.location.search).get('invite_token');
        let activeInviteFromLink = null;

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
        function openModal(id) {
            if (id === 'modal-event') {
                document.getElementById('form-create-event').reset();
                document.getElementById('event-id').value = '';
                document.getElementById('modal-title').innerText = 'Create New Event';
            }
            document.getElementById(id).classList.add('active');
        }

        function formatEventDate(dateValue) {
            if (!dateValue) return '';
            const date = new Date(dateValue);
            return Number.isNaN(date.getTime()) ? dateValue : date.toLocaleString();
        }

        function buildSocialLinks(url, text) {
            const encodedUrl = encodeURIComponent(url);
            const encodedText = encodeURIComponent(text);
            return [
                { label: 'Share on X', url: `https://twitter.com/intent/tweet?text=${encodedText}%20${encodedUrl}` },
                { label: 'Share on Facebook', url: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}` },
                { label: 'Share on LinkedIn', url: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}` },
                { label: 'Share on WhatsApp', url: `https://wa.me/?text=${encodedText}%20${encodedUrl}` }
            ];
        }

        function renderSocialButtons(containerId, url, text) {
            const container = document.getElementById(containerId);
            const links = buildSocialLinks(url, text);
            container.innerHTML = links.map((item) => `
                <a class="btn btn-sm share-btn" href="${item.url}" target="_blank" rel="noopener noreferrer">${item.label}</a>
            `).join('');
        }

        async function copyInviteLink(inputId) {
            const input = document.getElementById(inputId);
            if (!input || !input.value) return;

            try {
                await navigator.clipboard.writeText(input.value);
                alert('Invite link copied.');
            } catch (err) {
                input.select();
                document.execCommand('copy');
                alert('Invite link copied.');
            }
        }

        function clearInviteTokenFromUrl() {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.delete('invite_token');
            window.history.replaceState({}, document.title, currentUrl.toString());
        }

        function openInviteShareModal(payload) {
            const titleText = payload.event_title ? `Invitation Link for "${payload.event_title}"` : 'Invitation Link Ready';
            document.getElementById('invite-share-title').innerText = titleText;
            document.getElementById('invite-share-note').innerText = payload.invitee_signup_required
                ? 'User is not registered yet. Ask them to sign up (or log in) using that username/email, then open this link to accept.'
                : 'Share this response link with the invitee.';
            document.getElementById('invite-share-link').value = payload.invite_url;
            renderSocialButtons('invite-share-buttons', payload.invite_url, payload.share_text || 'You are invited to an event. Respond here:');
            document.getElementById('modal-invite-share').classList.add('active');
        }

        function openInviteResponseModal(invite) {
            activeInviteFromLink = invite;

            document.getElementById('invite-response-title').innerText = invite.title || 'Event Invitation';
            document.getElementById('invite-response-description').innerText = invite.description || 'No description provided.';
            document.getElementById('invite-response-meta').innerText = `Invited by ${invite.inviter_name} - ${formatEventDate(invite.event_date)} - ${invite.location || 'Online'}`;
            document.getElementById('invite-response-link').value = invite.invite_url;

            const shareText = `${invite.inviter_name} invited you to "${invite.title}" on EventFlow AI.`;
            renderSocialButtons('invite-response-share-buttons', invite.invite_url, shareText);
            document.getElementById('modal-invite-response').classList.add('active');
        }

        async function tryOpenInviteFromToken() {
            if (!inviteTokenFromUrl) return;
            try {
                const params = new URLSearchParams({
                    action: 'get_invite_by_token',
                    token: inviteTokenFromUrl
                });

                const resp = await fetch(`api/invitations.php?${params.toString()}`);
                const data = await resp.json();

                if (!data.success) {
                    alert(data.error || 'Unable to open this invite link.');
                    clearInviteTokenFromUrl();
                    return;
                }

                openInviteResponseModal(data.invite);
            } catch (err) {
                alert('Unable to open invite link right now.');
                clearInviteTokenFromUrl();
            }
        }

        // Fetch Events
        async function fetchEvents() {
            const q = document.getElementById('filter-q').value;
            const location = document.getElementById('filter-location').value;
            const start = document.getElementById('filter-date-start').value;
            const end = document.getElementById('filter-date-end').value;
            const status = document.getElementById('filter-status').value;

            const params = new URLSearchParams({
                action: 'list',
                q: q,
                location: location,
                date_start: start,
                date_end: end,
                status: status
            });

            const resp = await fetch(`api/events.php?${params.toString()}`);
            const data = await resp.json();
            renderEvents(data);
        }

        function renderEvents(events) {
            if (events.length === 0) {
                eventList.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 100px; color: var(--text-muted);">No events found matching your filters.</div>';
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

                    <div class="btn-action-group">
                        <button class="btn btn-sm btn-invite" onclick="openInvite(${e.id})">Invite</button>
                        <button class="btn btn-sm btn-edit ${e.created_by == currentUser.id || currentUser.role === 'admin' ? '' : 'btn-disabled'}" 
                                ${e.created_by == currentUser.id || currentUser.role === 'admin' ? '' : 'disabled'} 
                                onclick="editEvent(${e.id})">Edit</button>
                        <button class="btn btn-sm btn-delete ${e.created_by == currentUser.id || currentUser.role === 'admin' ? '' : 'btn-disabled'}" 
                                ${e.created_by == currentUser.id || currentUser.role === 'admin' ? '' : 'disabled'} 
                                onclick="deleteEvent(${e.id})">Delete</button>
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

        // AI usage limit helper
        async function updateRemainingLimit() {
            if (!AI_ENABLED) return;
            try {
                const resp = await fetch('api/ai.php?action=get_remaining_limit');
                const data = await resp.json();
                if (data.success) {
                    document.getElementById('ai-builder-remaining').innerText = data.remaining;
                    document.getElementById('ai-magic-remaining').innerText = data.remaining;

                    // Disable buttons if limit reached
                    const buildBtn = document.getElementById('btn-ai-build');
                    const magicBtn = document.getElementById('btn-ai-magic');
                    if (data.remaining <= 0) {
                        buildBtn.disabled = true;
                        buildBtn.style.opacity = '0.5';
                        buildBtn.style.cursor = 'not-allowed';
                        magicBtn.disabled = true;
                        magicBtn.style.opacity = '0.5';
                        magicBtn.style.cursor = 'not-allowed';
                    } else {
                        buildBtn.disabled = false;
                        buildBtn.style.opacity = '1';
                        buildBtn.style.cursor = 'pointer';
                        magicBtn.disabled = false;
                        magicBtn.style.opacity = '1';
                        magicBtn.style.cursor = 'pointer';
                    }
                }
            } catch (err) { console.error('Limit fetch failed', err); }
        }

        // Character counter
        if (AI_ENABLED) {
            document.getElementById('ai-builder-prompt').oninput = function () {
                const len = this.value.length;
                document.getElementById('prompt-counter').innerText = `${len} / 250`;
            };
        }

        // AI Builder
        if (AI_ENABLED) {
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
                        updateRemainingLimit();
                    } else {
                        alert(data.error);
                    }
                } catch (err) {
                    alert('Connection error. Please check your network.');
                }

                btn.innerText = 'Build Event';
                btn.classList.remove('ai-loading');
            };
        }

        // AI Magic
        if (AI_ENABLED) {
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
                    updateRemainingLimit();
                } else {
                    alert(data.error || 'AI Magic failed.');
                }
                btn.innerText = '🪄 AI Magic';
                btn.classList.remove('ai-loading');
            };
        }

        // Invitations
        async function fetchInvitations() {
            let data = [];
            try {
                const resp = await fetch('api/invitations.php?action=list_invites');
                data = await resp.json();
            } catch (err) {
                inviteList.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 30px; color: #ef4444;">Unable to load invitations.</div>';
                return;
            }

            if (!Array.isArray(data)) {
                inviteList.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 30px; color: #ef4444;">Unable to load invitations.</div>';
                return;
            }

            if (data.length === 0) {
                inviteList.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 30px; color: var(--text-muted);">No pending invitations.</div>';
                return;
            }

            inviteList.innerHTML = data.map(e => `
                <div class="glass-container event-card">
                    <h3 class="event-title">${e.title}</h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">From: ${e.inviter}</p>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">${formatEventDate(e.event_date)} - ${e.location || 'Online'}</p>
                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                        <button class="btn" onclick="respondInvite(${e.id}, 'attending')">Accept</button>
                        <button class="btn" onclick="respondInvite(${e.id}, 'maybe')">Maybe</button>
                        <button class="btn" style="background: #ef4444;" onclick="respondInvite(${e.id}, 'declined')">Decline</button>
                    </div>
                </div>
            `).join('');
        }

        async function respondInvite(id, status, silent = false) {
            const fd = new FormData();
            fd.append('event_id', id);
            fd.append('status', status);

            try {
                const resp = await fetch('api/invitations.php?action=respond', { method: 'POST', body: fd });
                const data = await resp.json();

                if (!data.success) {
                    if (!silent) alert(data.error || 'Unable to save invitation response.');
                    return false;
                }

                fetchInvitations();
                fetchEvents();
                return true;
            } catch (err) {
                if (!silent) alert('Unable to save invitation response right now.');
                return false;
            }
        }

        async function respondFromInviteLink(status) {
            if (!activeInviteFromLink) return;
            const success = await respondInvite(activeInviteFromLink.event_id, status, true);
            if (!success) return;

            closeModal('modal-invite-response');
            clearInviteTokenFromUrl();
            activeInviteFromLink = null;
            alert('Your invitation response has been saved.');
        }

        function openInvite(id) {
            const inviteForm = document.getElementById('form-invite-user');
            inviteForm.reset();
            document.getElementById('invite-event-id').value = id;
            document.getElementById('modal-invite').classList.add('active');
        }

        document.getElementById('form-invite-user').onsubmit = async (e) => {
            e.preventDefault();
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerText = 'Sending...';

            try {
                const resp = await fetch('api/invitations.php?action=invite', {
                    method: 'POST',
                    body: new FormData(e.target)
                });
                const data = await resp.json();

                if (!data.success) {
                    alert(data.error || 'Unable to send invitation.');
                    return;
                }

                closeModal('modal-invite');
                e.target.reset();
                openInviteShareModal(data);
                fetchInvitations();
            } catch (err) {
                alert('Unable to send invitation right now.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerText = 'Send Invitation';
            }
        };

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

            if (e.created_by != currentUser.id && currentUser.role !== 'admin') {
                return alert('Unauthorized: You do not have permission to edit this event.');
            }

            document.getElementById('event-id').value = e.id;
            document.getElementById('ev-title').value = e.title;
            document.getElementById('ev-date').value = e.event_date.replace(' ', 'T');
            document.getElementById('ev-location').value = e.location;
            document.getElementById('ev-desc').value = e.description;
            document.getElementById('modal-title').innerText = 'Edit Event';
            document.getElementById('modal-event').classList.add('active');
        }

        async function deleteEvent(id) {
            // Pre-check permission
            const resp = await fetch(`api/events.php?action=get&id=${id}`);
            const e = await resp.json();
            if (e.created_by != currentUser.id && currentUser.role !== 'admin') {
                return alert('Unauthorized: You do not have permission to delete this event.');
            }

            if (!confirm('Delete this event?')) return;
            const fd = new FormData(); fd.append('id', id);
            await fetch('api/events.php?action=delete', { method: 'POST', body: fd });
            fetchEvents();
        }

        document.querySelectorAll('.filter-bar input, .filter-bar select').forEach(el => {
            el.oninput = () => fetchEvents();
        });
        fetchEvents();
        updateRemainingLimit();
        tryOpenInviteFromToken();
    </script>
</body>

</html>
