<?php
require_once __DIR__ . '/prototype-data.php';

$dashboardAnnouncementRole = function_exists('getCurrentUserRoleKey')
    ? getCurrentUserRoleKey()
    : 'guest';
if (function_exists('smsNormalizeRoleKey')) {
    $dashboardAnnouncementRole = smsNormalizeRoleKey($dashboardAnnouncementRole);
}
$dashboardAnnouncementAudiences = in_array(
    $dashboardAnnouncementRole,
    [
        'adviser',
        'panel',
        'grammarian',
        'research_director',
        'research_coordinator',
        'department_head',
        'crad_officer',
        'research_grant',
        'review_committee',
        'department_chair',
        'research_office',
        'vpaa',
    ],
    true
)
    ? ['Faculty & panel', 'Everyone']
    : ['Everyone'];
$dashboardAnnouncements = array_values(array_filter(
    smsCommunicationDemoAnnouncements(),
    static fn(array $announcement): bool => $announcement['status'] === 'Published'
        && in_array($announcement['audience'], $dashboardAnnouncementAudiences, true)
));
usort(
    $dashboardAnnouncements,
    static fn(array $a, array $b): int => strcmp($b['published_at'], $a['published_at'])
);
$dashboardAnnouncements = array_slice($dashboardAnnouncements, 0, 2);
?>
<section class="dashboard-announcements" aria-labelledby="dashboardImportantAnnouncements">
    <div class="dashboard-announcements-heading">
        <div>
            <span class="dashboard-announcements-kicker">Prototype data · not live notices</span>
            <h2 id="dashboardImportantAnnouncements">Important announcements</h2>
        </div>
        <a href="<?= e(BASE_URL . '/communication/announcements.php') ?>">View all <span aria-hidden="true">→</span></a>
    </div>
    <?php if ($dashboardAnnouncements): ?>
        <ul class="dashboard-announcements-list">
            <?php foreach ($dashboardAnnouncements as $announcement): ?>
                <li>
                    <span class="dashboard-announcement-category <?= e(strtolower($announcement['category'])) ?>">
                        <?= e($announcement['category']) ?>
                    </span>
                    <div>
                        <strong><?= e($announcement['title']) ?></strong>
                        <p><?= e($announcement['description']) ?></p>
                    </div>
                    <time datetime="<?= e($announcement['published_at']) ?>">
                        <?= e(smsCommunicationDemoDateLabel($announcement['published_at'])) ?>
                    </time>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="dashboard-announcements-empty">No relevant demo announcements right now.</p>
    <?php endif; ?>
</section>
