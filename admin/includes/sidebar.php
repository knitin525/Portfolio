<?php
/**
 * Knitin Portfolio — Admin Sidebar Partial
 */

declare(strict_types=1);

$activeNav = $activeNav ?? 'dashboard';
$unreadCount = $unreadCount ?? 0;
$currentUser = $currentUser ?? ['name' => 'Nitin Kumar', 'email' => 'contact@knitin525.in'];
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <a href="index.php" class="sidebar-logo">
            Knitin<span>.</span>
            <span class="sidebar-tag">CRM</span>
        </a>
    </div>

    <div class="sidebar-nav">
        <div class="nav-section-title">Overview</div>

        <a href="index.php" class="nav-item <?= $activeNav === 'dashboard' ? 'active' : '' ?>">
            <div class="nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                <span>Dashboard</span>
            </div>
        </a>

        <div class="nav-section-title">Communications</div>

        <a href="inbox.php" class="nav-item <?= $activeNav === 'inbox' ? 'active' : '' ?>">
            <div class="nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                <span>Inbox</span>
            </div>
            <?php if ($unreadCount > 0): ?>
                <span class="badge-count badge-unread"><?= $unreadCount ?></span>
            <?php endif; ?>
        </a>

        <a href="sent.php" class="nav-item <?= $activeNav === 'sent' ? 'active' : '' ?>">
            <div class="nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                <span>Sent Mail</span>
            </div>
        </a>

        <div class="nav-section-title">Client CRM</div>

        <a href="leads.php" class="nav-item <?= $activeNav === 'leads' ? 'active' : '' ?>">
            <div class="nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                <span>Website Leads</span>
            </div>
        </a>

        <a href="contacts.php" class="nav-item <?= $activeNav === 'contacts' ? 'active' : '' ?>">
            <div class="nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <span>Contacts</span>
            </div>
        </a>

        <div class="nav-section-title">Portfolio Showcase</div>

        <a href="projects.php" class="nav-item <?= in_array($activeNav, ['projects', 'project-edit'], true) ? 'active' : '' ?>">
            <div class="nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                <span>Projects</span>
            </div>
        </a>

        <a href="categories.php" class="nav-item <?= $activeNav === 'categories' ? 'active' : '' ?>">
            <div class="nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                <span>Categories</span>
            </div>
        </a>

        <div class="nav-section-title">Preferences</div>

        <a href="settings.php" class="nav-item <?= $activeNav === 'settings' ? 'active' : '' ?>">
            <div class="nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                <span>Settings &amp; Mail</span>
            </div>
        </a>

        <a href="/" target="_blank" class="nav-item">
            <div class="nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                <span>View Portfolio</span>
            </div>
        </a>
    </div>

    <div class="sidebar-footer">
        <div class="user-profile-mini">
            <div class="user-avatar">
                <?= strtoupper(substr($currentUser['name'] ?? 'N', 0, 1)) ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($currentUser['name'] ?? 'Admin') ?></div>
                <div class="user-role">Administrator</div>
            </div>
        </div>
        <a href="logout.php" title="Sign Out" style="color: #ef4444; padding: 6px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        </a>
    </div>
</aside>
