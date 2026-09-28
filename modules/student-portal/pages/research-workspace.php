<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';

requireAuth();
if (getCurrentUserRoleKey() !== 'student') {
    http_response_code(403);
    exit('Forbidden');
}

$pageTitle = 'Research Workspace';
$activeModule = 'student_portal';
$activePage = 'research-workspace';
$pageBannerIcon = 'fa-flask';
$pageBannerDescription = 'A central place to access your research workflow, submissions, feedback, and final-stage services.';

$breadcrumbs = [
    ['label' => 'Student Portal', 'url' => BASE_URL . '/modules/student-portal/pages/dashboard.php'],
    ['label' => 'Research Workspace', 'url' => null],
];

$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$studentId = trim((string) ($_SESSION['student_id'] ?? ''));
$studentUserId = (int) ($_SESSION['user_id'] ?? 0);
$researchGroup = null;
$researchPlan = null;
$registeredGroupDetails = null;
$registeredGroupDetailsAvailable = false;
$researchLookupAvailable = false;

try {
    require_once ROOT_PATH . '/modules/crad/config/config.php';
    require_once ROOT_PATH . '/modules/crad/includes/research-progress-helpers.php';
    $crad = cradDb();
    $researchGroup = rpGetRegisteredResearchGroup($crad, $studentId, $studentUserId);
    $researchLookupAvailable = true;

    if ($researchGroup) {
        // Read an existing plan only; do not create or modify research records here.
        $researchPlan = rpGetResearchPlan($crad, (int) ($researchGroup['id'] ?? 0));
        try {
            require_once ROOT_PATH . '/modules/crad/includes/chapter-evaluation-workflow.php';
            $registeredGroupDetails = chapterRegisteredStudentGroup($crad);
            $registeredGroupDetailsAvailable = is_array($registeredGroupDetails)
                && (int) ($registeredGroupDetails['id'] ?? 0) === (int) ($researchGroup['id'] ?? 0);
            if (!$registeredGroupDetailsAvailable) {
                $registeredGroupDetails = null;
            }
        } catch (Throwable $e) {
            error_log('Student research workspace registered-group details load failed: ' . $e->getMessage());
        }
    }
} catch (Throwable $e) {
    error_log('Student research workspace summary load failed: ' . $e->getMessage());
}

$groupSummary = $registeredGroupDetailsAvailable ? $registeredGroupDetails : $researchGroup;
$groupFields = [
    ['Group name', 'group_name'],
    ['Group number', 'group_number'],
    ['Research title', 'research_title'],
    ['College / department', 'college_dept'],
    ['Academic year', 'academic_year'],
    ['Recorded member count', 'member_count'],
    ['Adviser', 'adviser_name'],
    ['Coordinator', 'coordinator_name'],
    ['Group status', 'status'],
    ['Group workflow status', 'flow_status'],
];
$groupLastActivity = '';
foreach (['updated_at', 'submitted_at', 'date_assigned', 'created_at'] as $dateField) {
    if (isset($groupSummary[$dateField]) && trim((string) $groupSummary[$dateField]) !== '') {
        $groupLastActivity = trim((string) $groupSummary[$dateField]);
        break;
    }
}
$groupAdviser = trim((string) ($groupSummary['adviser_name'] ?? $groupSummary['adviser'] ?? ''));

$researchBase = BASE_URL . '/modules/student-portal/pages/';
$stages = [
    [
        'title' => 'Research group',
        'href' => $researchBase . 'research-group.php',
        'description' => 'Open the group submission and member details.',
    ],
    [
        'title' => 'Title approval',
        'href' => $researchBase . 'research-proposal-submission.php',
        'description' => 'Open the proposal and title approval page.',
    ],
];

if ($researchLookupAvailable && $researchGroup) {
    $stages[] = [
        'title' => 'Research development',
        'href' => $researchBase . 'my-research.php',
        'description' => 'Open the registered group, plan, and progress overview.',
    ];
    $stages[] = [
        'title' => 'Chapter submissions',
        'href' => $researchBase . 'submit-chapters.php',
        'description' => 'Open chapter submission; chapter eligibility is checked there.',
    ];
    $stages[] = [
        'title' => 'Final-stage services',
        'href' => $researchBase . 'final-manuscript.php',
        'description' => 'Open final manuscript and clearance services.',
    ];
}

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>

<main class="glass-dashboard" aria-labelledby="research-workspace-title">
    <div class="glass-board">
        <section class="glass-panel p-4 mb-4" id="workspace-overview" aria-labelledby="research-workspace-title">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 class="h4 mb-2" id="research-workspace-title">Research Workspace</h2>
                    <p class="text-muted mb-0">Use the sections below to move between your research workflow and its existing detail pages.</p>
                </div>
                <span class="badge text-bg-light border"> <?= count($stages) ?> capability stages </span>
            </div>

            <nav class="mb-4" aria-label="Research workspace sections">
                <div class="nav nav-pills flex-wrap gap-2">
                    <a class="nav-link active" href="#group-title">Group &amp; title</a>
                    <a class="nav-link" href="#progress-feedback">Progress &amp; feedback</a>
                    <a class="nav-link" href="#submissions-history">Submissions &amp; history</a>
                    <a class="nav-link" href="#final-services">Final-stage services</a>
                </div>
            </nav>

            <?php if (!$researchLookupAvailable): ?>
                <div class="alert alert-info" role="status">
                    Research summary details could not be loaded right now. The links below still open the existing live pages, where access and eligibility are checked.
                </div>
            <?php elseif (!$researchGroup): ?>
                <div class="alert alert-info" role="status">
                    No registered research group is available in the current research progress record. Open the group and title approval pages for the live workflow details.
                </div>
            <?php endif; ?>

            <div class="mb-2">
                <h3 class="h6 mb-1">Workflow capabilities</h3>
                <p class="small text-muted mb-3">This is a navigation guide, not a completion indicator. Open a live page for current status and eligibility.</p>
            </div>
            <ol class="list-unstyled d-flex flex-column flex-md-row flex-wrap gap-3 mb-0" aria-label="Available research workflow stages">
                <?php foreach ($stages as $index => $stage): ?>
                    <li class="border rounded p-3 flex-fill" style="min-width: 190px;">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge rounded-pill text-bg-secondary" aria-hidden="true"><?= $index + 1 ?></span>
                            <span class="fw-semibold"><?= $escape($stage['title']) ?></span>
                        </div>
                        <p class="small text-muted mb-2"><?= $escape($stage['description']) ?></p>
                        <a href="<?= $escape($stage['href']) ?>">Open live page<span class="visually-hidden">: <?= $escape($stage['title']) ?></span></a>
                    </li>
                <?php endforeach; ?>
            </ol>
            <?php if (!$researchLookupAvailable): ?>
                <p class="small text-muted mt-3 mb-0">Additional registered-group stages are omitted from this stepper until availability can be confirmed; all related pages remain accessible below.</p>
            <?php endif; ?>
        </section>

        <section class="glass-panel p-4 mb-4" id="group-title" aria-labelledby="group-title-heading">
            <h2 class="h5 mb-2" id="group-title-heading">Group &amp; title</h2>
            <p class="small text-muted">Registered group details are shown only when supplied by the existing group record. Open the protected destination pages for title approval details and actions.</p>
            <?php if ($researchGroup): ?>
                <div class="row g-3 mb-4">
                    <?php foreach ($groupFields as [$label, $key]):
                        $value = $groupSummary[$key] ?? null;
                        if (!is_scalar($value) || trim((string) $value) === '') {
                            if ($key === 'adviser_name' && $groupAdviser !== '') {
                                $value = $groupAdviser;
                            } else {
                                continue;
                            }
                        }
                    ?>
                        <div class="col-sm-6 col-xl-4">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-muted mb-1"><?= $escape($label) ?></div>
                                <div class="fw-semibold"><?= $escape($value) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($groupLastActivity !== ''): ?>
                        <div class="col-sm-6 col-xl-4">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-muted mb-1">Last recorded group activity</div>
                                <div class="fw-semibold"><?= $escape($groupLastActivity) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-light border" role="status">No registered group summary is available here. Group and title details remain available on their live pages.</div>
            <?php endif; ?>
            <div class="row g-3">
                <?php
                $groupTitleLinks = [
                    ['Research Group', 'research-group.php', 'Open the group submission and member details.', 'users'],
                    ['Proposal / Title Approval', 'research-proposal-submission.php', 'Open the existing proposal and title approval workflow, including any returned-title follow-up.', 'file-alt'],
                ];
                foreach ($groupTitleLinks as [$label, $file, $description, $icon]):
                ?>
                    <div class="col-md-6">
                        <article class="border rounded p-3 h-100">
                            <h3 class="h6"><?= smsIcon($icon, ['class' => 'me-2 text-primary']) ?><?= $escape($label) ?></h3>
                            <p class="small text-muted"><?= $escape($description) ?></p>
                            <a href="<?= $escape($researchBase . $file) ?>">Open <?= $escape($label) ?></a>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="small text-muted mt-3 mb-0">
                If your title was returned, use the Proposal / Title Approval destination above. Its existing workflow handles returned-title routing; this workspace does not look up a separate approval ID or create a duplicate notification link.
            </p>
        </section>

        <section class="glass-panel p-4 mb-4" id="progress-feedback" aria-labelledby="progress-feedback-title">
            <h2 class="h5 mb-2" id="progress-feedback-title">Progress &amp; feedback</h2>
            <?php
            $recordedProgress = $researchPlan['overall_progress'] ?? null;
            $hasRecordedProgress = is_numeric($recordedProgress)
                && is_finite((float) $recordedProgress)
                && (float) $recordedProgress >= 0
                && (float) $recordedProgress <= 100;
            ?>
            <?php if ($researchPlan && $hasRecordedProgress): ?>
                <?php $progressValue = (int) round((float) $recordedProgress); ?>
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold">Recorded research plan progress</span>
                        <span><?= $progressValue ?>%</span>
                    </div>
                    <div class="progress" role="progressbar" aria-label="Recorded research plan progress"
                         aria-valuenow="<?= $progressValue ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar" style="width: <?= $progressValue ?>%"></div>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-muted">Progress and feedback details/status are available on the linked live feature pages.</p>
            <?php endif; ?>
            <?php if ($researchPlan && trim((string) ($researchPlan['current_stage'] ?? '')) !== ''): ?>
                <p><span class="fw-semibold">Recorded plan stage:</span> <?= $escape($researchPlan['current_stage']) ?></p>
            <?php endif; ?>
            <?php if ($researchPlan && trim((string) ($researchPlan['status'] ?? '')) !== ''): ?>
                <p><span class="fw-semibold">Recorded plan status:</span> <?= $escape($researchPlan['status']) ?></p>
            <?php endif; ?>
            <p class="small text-muted">Feedback entries and update review details are not summarized here; check their live pages for current records.</p>
            <div class="row g-3">
                <?php
                $progressLinks = [
                    ['My Research', 'my-research.php', 'View the registered group, research summary, and progress.', 'flask'],
                    ['Research Plan', 'research-plan.php', 'Open your plan and its current milestone details.', 'project-diagram'],
                    ['Milestones', 'milestones.php', 'Review milestone details and linked progress updates.', 'tasks'],
                    ['Progress Updates', 'progress-updates.php', 'Open the progress update page for the registered group.', 'chart-line'],
                    ['Adviser Feedback', 'adviser-feedback.php', 'Review adviser feedback on research progress.', 'comments'],
                ];
                foreach ($progressLinks as [$label, $file, $description, $icon]):
                ?>
                    <div class="col-md-6 col-xl-4">
                        <article class="border rounded p-3 h-100">
                            <h3 class="h6"><?= smsIcon($icon, ['class' => 'me-2 text-primary']) ?><?= $escape($label) ?></h3>
                            <p class="small text-muted"><?= $escape($description) ?></p>
                            <a href="<?= $escape($researchBase . $file) ?>">Open <?= $escape($label) ?></a>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="glass-panel p-4 mb-4" id="submissions-history" aria-labelledby="submissions-history-title">
            <h2 class="h5 mb-2" id="submissions-history-title">Submissions &amp; history</h2>
            <p class="small text-muted">Chapter status and event history are not loaded into this summary; view current records and available actions on the protected pages.</p>
            <div class="row g-3">
                <?php
                $submissionLinks = [
                    ['Submit Chapters 1–3', 'submit-chapters.php', 'Submit eligible chapters; existing adviser approval gates remain in effect.', 'file-upload'],
                    ['My Submissions', 'my-submissions.php', 'Review your current chapter submissions and available actions.', 'folder-open'],
                    ['Submission Status', 'submission-status.php', 'Check the current review status of submitted chapters.', 'chart-line'],
                    ['Submission History', 'submission-history.php', 'Review the chapter submission and revision event history.', 'history'],
                ];
                foreach ($submissionLinks as [$label, $file, $description, $icon]):
                ?>
                    <div class="col-md-6 col-xl-3">
                        <article class="border rounded p-3 h-100">
                            <h3 class="h6"><?= smsIcon($icon, ['class' => 'me-2 text-primary']) ?><?= $escape($label) ?></h3>
                            <p class="small text-muted"><?= $escape($description) ?></p>
                            <a href="<?= $escape($researchBase . $file) ?>">Open <?= $escape($label) ?></a>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="glass-panel p-4" id="final-services" aria-labelledby="final-services-title">
            <h2 class="h5 mb-2" id="final-services-title">Final manuscript &amp; clearance</h2>
            <p class="small text-muted">These pages may be conditional. Open the destination to see live eligibility and available actions; this workspace does not bypass those checks.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <article class="border rounded p-3 h-100">
                        <h3 class="h6"><?= smsIcon('file-alt', ['class' => 'me-2 text-primary']) ?>Final Manuscript</h3>
                        <p class="small text-muted">Open the final manuscript page for current recommendation requirements and submission history.</p>
                        <a href="<?= $escape($researchBase . 'final-manuscript.php') ?>">Open Final Manuscript</a>
                    </article>
                </div>
                <div class="col-md-6">
                    <article class="border rounded p-3 h-100">
                        <h3 class="h6"><?= smsIcon('stamp', ['class' => 'me-2 text-primary']) ?>Research Clearance</h3>
                        <p class="small text-muted">Open the clearance page to view the live inbox and any currently available actions.</p>
                        <a href="<?= $escape($researchBase . 'research-clearance.php') ?>">Open Research Clearance</a>
                    </article>
                </div>
            </div>
        </section>
    </div>
</main>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
