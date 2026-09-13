/**
 * Knitin Portfolio — Admin Dashboard Client Script
 */

document.addEventListener('DOMContentLoaded', () => {
    // Mobile Sidebar Drawer Toggle
    const menuToggle = document.getElementById('adminMenuToggle');
    const sidebar = document.getElementById('adminSidebar');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 &&
                !sidebar.contains(e.target) &&
                !menuToggle.contains(e.target) &&
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
    }

    // Manual IMAP Sync Trigger
    const syncBtn = document.getElementById('btnManualSync');
    if (syncBtn) {
        syncBtn.addEventListener('click', async () => {
            if (syncBtn.classList.contains('syncing')) return;

            syncBtn.classList.add('syncing');
            const originalText = syncBtn.querySelector('.btn-text')?.textContent || 'Sync';
            if (syncBtn.querySelector('.btn-text')) {
                syncBtn.querySelector('.btn-text').textContent = 'Syncing...';
            }

            try {
                const response = await fetch('ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'sync_emails',
                        csrf_token: getCsrfToken()
                    })
                });
                const data = await response.json();

                if (data.success) {
                    showToast(data.message || 'Email synchronization completed!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    showToast(data.message || 'Sync failed. Check settings.', 'error');
                }
            } catch (err) {
                showToast('Network error during sync.', 'error');
            } finally {
                syncBtn.classList.remove('syncing');
                if (syncBtn.querySelector('.btn-text')) {
                    syncBtn.querySelector('.btn-text').textContent = originalText;
                }
            }
        });
    }

    // Star Toggle in Inbox
    document.querySelectorAll('.star-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const emailId = btn.dataset.emailId;
            if (!emailId) return;

            const isStarred = btn.classList.contains('starred');
            btn.classList.toggle('starred');

            try {
                const response = await fetch('ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'toggle_star',
                        email_id: emailId,
                        csrf_token: getCsrfToken()
                    })
                });
                const data = await response.json();
                if (!data.success) {
                    btn.classList.toggle('starred'); // Revert on failure
                    showToast('Could not update star status', 'error');
                }
            } catch (err) {
                btn.classList.toggle('starred');
                showToast('Network error', 'error');
            }
        });
    });

    // Mark Read / Unread
    document.querySelectorAll('[data-action="mark_read"]').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const emailId = btn.dataset.emailId;
            const isRead = btn.dataset.isRead === '1' ? 0 : 1;

            try {
                const response = await fetch('ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'toggle_read',
                        email_id: emailId,
                        is_read: isRead,
                        csrf_token: getCsrfToken()
                    })
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (err) {
                showToast('Network error', 'error');
            }
        });
    });

    // Save Contact Notes
    const saveNotesBtn = document.getElementById('btnSaveContactNotes');
    if (saveNotesBtn) {
        saveNotesBtn.addEventListener('click', async () => {
            const contactId = saveNotesBtn.dataset.contactId;
            const notesTextarea = document.getElementById('contactNotesInput');
            if (!contactId || !notesTextarea) return;

            saveNotesBtn.disabled = true;
            saveNotesBtn.textContent = 'Saving...';

            try {
                const response = await fetch('ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'save_notes',
                        contact_id: contactId,
                        notes: notesTextarea.value,
                        csrf_token: getCsrfToken()
                    })
                });
                const data = await response.json();
                if (data.success) {
                    showToast('Notes saved successfully', 'success');
                } else {
                    showToast(data.message || 'Error saving notes', 'error');
                }
            } catch (err) {
                showToast('Network error', 'error');
            } finally {
                saveNotesBtn.disabled = false;
                saveNotesBtn.textContent = 'Save Notes';
            }
        });
    }

    // Lead Status Quick Dropdown
    document.querySelectorAll('.lead-status-select').forEach(select => {
        select.addEventListener('change', async () => {
            const leadId = select.dataset.leadId;
            const status = select.value;

            try {
                const response = await fetch('ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'update_lead_status',
                        lead_id: leadId,
                        status: status,
                        csrf_token: getCsrfToken()
                    })
                });
                const data = await response.json();
                if (data.success) {
                    showToast('Lead status updated', 'success');
                } else {
                    showToast(data.message || 'Error updating status', 'error');
                }
            } catch (err) {
                showToast('Network error', 'error');
            }
        });
    });
});

// Helper to get CSRF token
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.getAttribute('content');
    const input = document.querySelector('input[name="csrf_token"]');
    return input ? input.value : '';
}

// Toast Notification
function showToast(message, type = 'info') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}
