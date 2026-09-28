<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
requireAuth();

$pageTitle = 'Announcement Bulletin · Demo';
$activeModule = '';
$activePage = 'communication-announcements';
$breadcrumbs = [['label' => 'Communication Demo', 'url' => null], ['label' => 'Announcements', 'url' => null]];
require_once ROOT_PATH . '/includes/breadcrumbs.php';
?>
<?php require_once ROOT_PATH . '/includes/layout-start.php'; ?>
<?php require_once __DIR__ . '/prototype-data.php'; ?>
<?php
$announcements = smsCommunicationDemoAnnouncements();
$announcementCategoryCount = count(array_unique(array_column($announcements, 'category')));
?>
<?php renderBreadcrumbs($breadcrumbs); ?>

<main class="communication-prototype" data-communication-page="announcements" aria-busy="true">
    <header class="communication-page-header">
        <div>
            <span class="communication-kicker">Communication prototype</span>
            <h1>Announcement bulletin</h1>
            <p>Scan research notices, defense updates, and upcoming deadlines in one place.</p>
        </div>
        <span class="communication-demo-flag"><?= smsIcon('flask', ['aria-hidden' => 'true']) ?> Demo data · fictional sample records</span>
    </header>

    <section class="communication-summary-strip" aria-label="Announcement summary">
        <div><span>Published</span><strong><?= count(array_filter($announcements, static fn(array $a): bool => $a['status'] === 'Published')) ?></strong></div>
        <div><span>Important</span><strong><?= count(array_filter($announcements, static fn(array $a): bool => $a['priority'] !== 'Normal')) ?></strong></div>
        <div><span>Categories</span><strong><?= $announcementCategoryCount ?></strong></div>
        <div class="communication-summary-note"><?= smsIcon('info-circle', ['aria-hidden' => 'true']) ?> Prototype records are not sent to users or stored in the production database.</div>
    </section>

    <section class="communication-surface" aria-labelledby="announcementFiltersTitle">
        <div class="communication-surface-heading">
            <div>
                <h2 id="announcementFiltersTitle">Filter announcements</h2>
                <p>Refine by category, audience, status, or published date.</p>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-ann-reset>Reset filters</button>
        </div>
        <div class="communication-filters">
            <label>Category
                <select class="form-select" data-ann-filter="category">
                    <option value="">All categories</option>
                    <option>Research</option><option>Defense</option><option>Deadline</option><option>General</option>
                </select>
            </label>
            <label>Audience
                <select class="form-select" data-ann-filter="audience">
                    <option value="">All audiences</option>
                    <?php foreach (array_unique(array_column($announcements, 'audience')) as $audience): ?>
                        <option><?= e($audience) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Status
                <select class="form-select" data-ann-filter="status">
                    <option value="">All statuses</option>
                    <option>Published</option><option>Draft</option><option>Unpublished</option><option>Archived</option>
                </select>
            </label>
            <label>Published from
                <input class="form-control" type="date" data-ann-filter="from" aria-label="Published from">
            </label>
            <label>Published to
                <input class="form-control" type="date" data-ann-filter="to" aria-label="Published to">
            </label>
            <label class="communication-filter-search">Search
                <input class="form-control" type="search" data-ann-filter="search" placeholder="Title, message, audience">
            </label>
        </div>
    </section>

    <div class="communication-results-heading">
        <div><h2>Notices</h2><span data-ann-count aria-live="polite"></span></div>
        <span class="communication-results-caption">Newest first · demo content</span>
    </div>
    <div class="communication-announcement-list" data-ann-list>
        <?php foreach ($announcements as $announcement): ?>
            <article class="communication-announcement-card"
                     data-announcement
                     data-category="<?= e($announcement['category']) ?>"
                     data-audience="<?= e($announcement['audience']) ?>"
                     data-status="<?= e($announcement['status']) ?>"
                     data-priority="<?= e($announcement['priority']) ?>"
                     data-date="<?= e($announcement['published_at']) ?>"
                     data-search="<?= e(strtolower(implode(' ', [$announcement['title'], $announcement['description'], $announcement['audience'], $announcement['author']]))) ?>">
                <span class="communication-announcement-accent <?= e(strtolower($announcement['category'])) ?>" aria-hidden="true"></span>
                <div class="communication-announcement-content">
                    <div class="communication-announcement-topline">
                        <div class="communication-announcement-tags">
                            <span class="communication-type-pill <?= e(strtolower($announcement['category'])) ?>"><?= e($announcement['category']) ?></span>
                            <?php if ($announcement['priority'] !== 'Normal'): ?>
                                <span class="communication-priority <?= e(strtolower($announcement['priority'])) ?>"><?= e($announcement['priority']) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="communication-status <?= e(strtolower($announcement['status'])) ?>"><?= e($announcement['status']) ?></span>
                    </div>
                    <h3><?= e($announcement['title']) ?></h3>
                    <p><?= e($announcement['description']) ?></p>
                    <footer>
                        <span><?= smsIcon('users', ['aria-hidden' => 'true']) ?> <?= e($announcement['audience']) ?></span>
                        <span><?= smsIcon('calendar', ['aria-hidden' => 'true']) ?> <?= e(smsCommunicationDemoDateLabel($announcement['published_at'])) ?></span>
                        <span><?= smsIcon('user', ['aria-hidden' => 'true']) ?> <?= e($announcement['author']) ?></span>
                    </footer>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="communication-empty-state" data-ann-empty hidden>
        <span><?= smsIcon('search', ['aria-hidden' => 'true']) ?></span>
        <strong>No announcements match these filters</strong>
        <p>Try another category, status, date range, or search phrase.</p>
        <button type="button" class="btn btn-sm btn-outline-primary" data-ann-reset>Clear filters</button>
    </div>
    <div class="communication-live-status" data-ann-status role="status" aria-live="polite"></div>
</main>
<script src="<?= e(BASE_URL . '/assets/js/communication-prototype.js?v=1') ?>" defer></script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
