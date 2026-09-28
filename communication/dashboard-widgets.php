<?php
require_once __DIR__ . '/prototype-data.php';

$communicationEvents = smsCommunicationDemoEvents();
$communicationAnnouncements = smsCommunicationDemoAnnouncements();
$communicationToday = date('Y-m-d');
$communicationUpcomingEvents = array_values(array_filter(
    $communicationEvents,
    static fn(array $event): bool => $event['date'] >= $communicationToday
        && in_array($event['type'], ['Deadline', 'Defense'], true)
));
usort(
    $communicationUpcomingEvents,
    static fn(array $a, array $b): int => strcmp($a['date'], $b['date'])
);
$communicationUpcomingEvents = array_slice($communicationUpcomingEvents, 0, 2);
$communicationLatestAnnouncements = array_values(array_filter(
    $communicationAnnouncements,
    static fn(array $announcement): bool => $announcement['status'] === 'Published'
));
usort(
    $communicationLatestAnnouncements,
    static fn(array $a, array $b): int => strcmp($b['published_at'], $a['published_at'])
);
$communicationLatestAnnouncements = array_slice($communicationLatestAnnouncements, 0, 2);
?>
<section class="communication-dashboard" aria-label="Research calendar and announcement prototype data">
    <div class="communication-dashboard-heading">
        <div>
            <span class="communication-kicker">Prototype data</span>
            <h2>Upcoming &amp; important</h2>
        </div>
        <span class="communication-demo-flag">Demo · not live CRAD records</span>
    </div>
    <div class="communication-dashboard-grid">
        <section class="communication-dashboard-panel" aria-labelledby="dashboardUpcomingTitle">
            <div class="communication-panel-heading">
                <div>
                    <h3 id="dashboardUpcomingTitle">Upcoming research events</h3>
                    <p>Next deadlines and defense schedules</p>
                </div>
                <a href="<?= e(BASE_URL . '/communication/calendar.php') ?>">Calendar <span aria-hidden="true">→</span></a>
            </div>
            <?php if ($communicationUpcomingEvents): ?>
                <ul class="communication-upcoming-list">
                    <?php foreach ($communicationUpcomingEvents as $event): ?>
                        <li>
                            <time datetime="<?= e($event['date']) ?>">
                                <strong><?= e(smsCommunicationDemoDateLabel($event['date'], 'M d')) ?></strong>
                                <span><?= e(smsCommunicationDemoDateLabel($event['date'], 'D')) ?></span>
                            </time>
                            <div>
                                <strong><?= e($event['title']) ?></strong>
                                <span><?= e($event['start_time']) ?> · <?= e($event['location']) ?></span>
                            </div>
                            <span class="communication-type-pill <?= e(strtolower($event['type'])) ?>"><?= e($event['type']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="communication-empty">No upcoming demo events.</p>
            <?php endif; ?>
        </section>
        <section class="communication-dashboard-panel" aria-labelledby="dashboardAnnouncementsTitle">
            <div class="communication-panel-heading">
                <div>
                    <h3 id="dashboardAnnouncementsTitle">Important announcements</h3>
                    <p>Recent sample notices</p>
                </div>
                <a href="<?= e(BASE_URL . '/communication/announcements.php') ?>">All notices <span aria-hidden="true">→</span></a>
            </div>
            <?php if ($communicationLatestAnnouncements): ?>
                <ul class="communication-latest-list">
                    <?php foreach ($communicationLatestAnnouncements as $announcement): ?>
                        <li>
                            <span class="communication-ann-dot <?= e(strtolower($announcement['category'])) ?>" aria-hidden="true"></span>
                            <div>
                                <strong><?= e($announcement['title']) ?></strong>
                                <span><?= e($announcement['category']) ?> · <?= e(smsCommunicationDemoDateLabel($announcement['published_at'])) ?></span>
                            </div>
                            <?php if ($announcement['priority'] !== 'Normal'): ?>
                                <span class="communication-priority <?= e(strtolower($announcement['priority'])) ?>"><?= e($announcement['priority']) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="communication-empty">No published demo announcements.</p>
            <?php endif; ?>
        </section>
    </div>
</section>
