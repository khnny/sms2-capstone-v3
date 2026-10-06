<?php
declare(strict_types=1);

require_once ROOT_PATH . '/modules/crad/config/config.php';

/**
 * Return upcoming official defenses visible to the signed-in user.
 *
 * Schedule visibility follows the existing participant relationships: defense
 * scheduling managers see all official schedules; students, advisers, panelists,
 * and coordinators see only their assigned research groups.
 *
 * @return list<array<string, string>>
 */
function smsCommunicationFinalizedDefenseEvents(): array
{
    $pdo = cradDb();
    if (!$pdo instanceof PDO) {
        error_log('Communication defense calendar: CRAD database connection unavailable.');
        return [];
    }

    $userId = (int) getCurrentUserId();
    $role = function_exists('smsNormalizeRoleKey')
        ? smsNormalizeRoleKey((string) getCurrentUserRoleKey())
        : (string) getCurrentUserRoleKey();
    $visibility = '';
    $params = [':now' => date('Y-m-d H:i:s')];
    // Calendar visibility is read-only and does not grant schedule-management permission.
    $canViewAllSchedules = smsCanManageDefenseScheduling($role) || $role === 'crad_officer';
    if (!$canViewAllSchedules) {
        if ($role === 'student') {
            $studentId = strtoupper(trim((string) ($_SESSION['student_id'] ?? '')));
            $email = trim((string) ($_SESSION['user_email'] ?? ''));
            if ($studentId === '' && $email === '') {
                return [];
            }
            $visibility = "AND EXISTS (
                SELECT 1
                  FROM `crad_research_group_members` member
                 WHERE member.research_group_id = rds.research_group_id
                   AND ((:student_id <> '' AND UPPER(member.student_id) = :student_id_match)
                     OR (:email <> '' AND LOWER(member.email) = :email_match))
            )";
            $params[':student_id'] = $studentId;
            $params[':student_id_match'] = $studentId;
            $params[':email'] = $email;
            $params[':email_match'] = strtolower($email);
        } elseif ($role === 'panel') {
            if ($userId <= 0) {
                return [];
            }
            $visibility = "AND EXISTS (
                SELECT 1
                  FROM `crad_research_panel_assignments` assignment
                 WHERE assignment.research_group_id = rds.research_group_id
                   AND assignment.panel_user_id = :user_id
                   AND assignment.assignment_status IN ('Assigned', 'Confirmed')
            )";
            $params[':user_id'] = $userId;
        } elseif ($role === 'adviser') {
            if ($userId <= 0) {
                return [];
            }
            $visibility = "AND EXISTS (
                SELECT 1
                  FROM `crad_research_adviser_assignments` assignment
                 WHERE (assignment.research_group_id = rds.research_group_id
                     OR (assignment.research_group_id IS NULL
                         AND assignment.group_number = rds.group_number))
                   AND assignment.adviser_user_id = :user_id
                   AND assignment.assignment_status IN ('Assigned', 'Confirmed')
            )";
            $params[':user_id'] = $userId;
        } elseif ($role === 'research_coordinator') {
            if ($userId <= 0) {
                return [];
            }
            $visibility = "AND EXISTS (
                SELECT 1
                  FROM `crad_research_coordinator_assignments` assignment
                 WHERE (assignment.research_group_id = rds.research_group_id
                     OR (assignment.research_group_id IS NULL
                         AND assignment.group_number = rds.group_number))
                   AND assignment.coordinator_user_id = :user_id
                   AND assignment.status = 'Active'
            )";
            $params[':user_id'] = $userId;
        } else {
            return [];
        }
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT rds.id, rds.defense_type, rds.defense_datetime, rds.defense_end_datetime,
                    rds.venue, rds.group_number, rds.research_group, rds.research_title
               FROM `crad_research_defense_schedules` rds
              WHERE LOWER(rds.status) IN ('scheduled', 'finalized', 'final')
                AND rds.defense_datetime >= :now
                {$visibility}
              ORDER BY rds.defense_datetime ASC, rds.id ASC
              LIMIT 250"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('Communication defense calendar query failed: ' . $e->getMessage());
        return [];
    }

    $events = [];
    foreach ($rows as $row) {
        $start = strtotime((string) ($row['defense_datetime'] ?? ''));
        $end = strtotime((string) ($row['defense_end_datetime'] ?? ''));
        if ($start === false) {
            continue;
        }
        $group = trim((string) ($row['group_number'] ?? ''));
        $title = trim((string) ($row['research_title'] ?? ''));
        if ($title === '') {
            $title = trim((string) ($row['research_group'] ?? 'Research Group'));
        }
        $events[] = [
            'id' => 'defense-' . (string) ($row['id'] ?? ''),
            'title' => trim((string) ($row['defense_type'] ?? 'Defense')) ?: 'Defense',
            'type' => 'Defense',
            'date' => date('Y-m-d', $start),
            'start_time' => date('g:i A', $start),
            'end_time' => $end !== false ? date('g:i A', $end) : '',
            'location' => trim((string) ($row['venue'] ?? '')) ?: 'Venue to be confirmed',
            'research_group' => ($group !== '' ? 'Group ' . $group . ' · ' : '') . $title,
            'audience' => $role === 'student' ? 'Research students & panel' : 'Faculty & panel',
            'status' => 'Scheduled',
            'description' => 'Official CRAD defense schedule. This time and venue are confirmed.',
        ];
    }

    return $events;
}

/**
 * Replace prototype defense events with official CRAD schedules while retaining
 * the existing non-defense communication prototypes.
 *
 * @param list<array<string, mixed>> $events
 * @return list<array<string, string>>
 */
function smsCommunicationEventsWithOfficialDefenses(array $events): array
{
    $nonDefenseEvents = array_values(array_filter(
        $events,
        static fn(array $event): bool => strcasecmp((string) ($event['type'] ?? ''), 'Defense') !== 0
    ));

    return array_merge($nonDefenseEvents, smsCommunicationFinalizedDefenseEvents());
}
