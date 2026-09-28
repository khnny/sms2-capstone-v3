<?php
require_once __DIR__ . '/prototype-data.php';

$communicationEvents = smsCommunicationDemoEvents();
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
?>
<section class="communication-dashboard" aria-label="Research calendar prototype data">
    <div class="communication-dashboard-heading">
        <div>
            <span class="communication-kicker">Prototype data</span>
            <h2>Upcoming research</h2>
        </div>
        <span class="communication-demo-flag">Demo · not live CRAD records</span>
    </div>
    <div class="communication-dashboard-grid">
        <section class="communication-dashboard-panel" aria-labelledby="dashboardUpcomingTitle">
            <div class="communication-panel-heading">
                <div>
                    <h3 id="dashboardUpcomingTitle">Next deadlines and defenses</h3>
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
    </div>
</section>
