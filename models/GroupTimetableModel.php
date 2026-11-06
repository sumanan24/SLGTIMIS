<?php
/**
 * Weekly class timetable for one student group.
 * Uses the existing group_timetable table: weekday, period, lecturer, room, and dates.
 */
class GroupTimetableModel extends Model {
    protected $table = 'group_timetable';

    protected function getPrimaryKey() {
        return 'timetable_id';
    }

    public static function dayNames() {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];
    }

    public static function periods() {
        return [
            'P1' => 'Period 1',
            'P2' => 'Period 2',
            'P3' => 'Period 3',
            'P4' => 'Period 4',
        ];
    }

    public function ensureTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `group_timetable` (
            `timetable_id` INT NOT NULL AUTO_INCREMENT,
            `group_id` INT NOT NULL,
            `module_id` VARCHAR(64) NULL DEFAULT NULL,
            `subject` VARCHAR(120) NULL DEFAULT NULL,
            `staff_id` VARCHAR(64) NOT NULL,
            `weekday` TINYINT(1) NOT NULL,
            `period` ENUM('P1','P2','P3','P4') NOT NULL,
            `classroom` VARCHAR(64) NULL DEFAULT NULL,
            `start_date` DATE NOT NULL,
            `end_date` DATE NOT NULL,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`timetable_id`),
            KEY `idx_group_slot` (`group_id`, `weekday`, `period`),
            KEY `idx_staff_slot` (`staff_id`, `weekday`, `period`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
        $conn = $this->db->getConnection();
        $subject = $conn->query("SHOW COLUMNS FROM `group_timetable` LIKE 'subject'");
        if (!$subject || $subject->num_rows === 0) {
            $conn->query("ALTER TABLE `group_timetable` ADD COLUMN `subject` VARCHAR(120) NULL DEFAULT NULL AFTER `module_id`");
        }
        $module = $conn->query("SHOW COLUMNS FROM `group_timetable` LIKE 'module_id'");
        $moduleRow = $module ? $module->fetch_assoc() : null;
        if ($moduleRow && strtoupper((string) $moduleRow['Null']) === 'NO') {
            $conn->query("ALTER TABLE `group_timetable` MODIFY `module_id` VARCHAR(64) NULL DEFAULT NULL");
        }
    }

    public function listByGroup($groupId) {
        $this->ensureTable();
        $sql = "SELECT t.*, s.staff_name,
                    (SELECT m.module_name FROM `module` m WHERE m.module_id = t.module_id ORDER BY m.course_id LIMIT 1) AS module_name
                FROM `group_timetable` t
                LEFT JOIN `staff` s ON s.staff_id = t.staff_id
                WHERE t.group_id = ? AND t.active = 1
                ORDER BY t.weekday ASC, t.period ASC, t.start_date ASC";
        $stmt = $this->db->prepare($sql);
        $groupId = (int) $groupId;
        $stmt->bind_param('i', $groupId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function findSlot($id, $groupId) {
        $this->ensureTable();
        $sql = "SELECT * FROM `group_timetable` WHERE `timetable_id` = ? AND `group_id` = ? AND `active` = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $id = (int) $id;
        $groupId = (int) $groupId;
        $stmt->bind_param('ii', $id, $groupId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public function listStaff($departmentId) {
        $sql = "SELECT `staff_id`, `staff_name` FROM `staff`";
        $departmentId = trim((string) $departmentId);
        if ($departmentId !== '') {
            $sql .= " WHERE `department_id` = ?";
        }
        $sql .= " ORDER BY `staff_name` ASC, `staff_id` ASC";
        if ($departmentId !== '') {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $departmentId);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function groupOverlaps($groupId, $weekday, $period, $startDate, $endDate, $excludeId) {
        $sql = "SELECT `timetable_id` FROM `group_timetable`
                WHERE `group_id` = ? AND `weekday` = ? AND `period` = ? AND `active` = 1 AND `timetable_id` <> ?
                  AND `start_date` <= ? AND `end_date` >= ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $groupId = (int) $groupId;
        $weekday = (int) $weekday;
        $excludeId = (int) $excludeId;
        $stmt->bind_param('iisiss', $groupId, $weekday, $period, $excludeId, $endDate, $startDate);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? true : false;
    }

    public function staffOverlap($staffId, $weekday, $period, $startDate, $endDate, $excludeId) {
        $sql = "SELECT g.name AS group_name, t.period, t.weekday
                FROM `group_timetable` t
                INNER JOIN `groups` g ON g.id = t.group_id
                WHERE t.staff_id = ? AND t.weekday = ? AND t.period = ? AND t.active = 1 AND t.timetable_id <> ?
                  AND t.start_date <= ? AND t.end_date >= ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $weekday = (int) $weekday;
        $excludeId = (int) $excludeId;
        $stmt->bind_param('sisiss', $staffId, $weekday, $period, $excludeId, $endDate, $startDate);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public function saveSlot(array $data, $slotId = 0) {
        $this->ensureTable();
        $slotId = (int) $slotId;
        if ($slotId > 0) {
            $sql = "UPDATE `group_timetable`
                    SET `weekday` = ?, `period` = ?, `module_id` = ?, `subject` = ?, `staff_id` = ?,
                        `classroom` = ?, `start_date` = ?, `end_date` = ?, `active` = 1
                    WHERE `timetable_id` = ? AND `group_id` = ?";
            $stmt = $this->db->prepare($sql);
            $weekday = (int) $data['weekday'];
            $groupId = (int) $data['group_id'];
            $stmt->bind_param(
                'isssssssii',
                $weekday,
                $data['period'],
                $data['module_id'],
                $data['subject'],
                $data['staff_id'],
                $data['classroom'],
                $data['start_date'],
                $data['end_date'],
                $slotId,
                $groupId
            );
            return $stmt->execute();
        }
        $sql = "INSERT INTO `group_timetable`
                (`group_id`, `weekday`, `period`, `module_id`, `subject`, `staff_id`, `classroom`, `start_date`, `end_date`, `active`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $this->db->prepare($sql);
        $groupId = (int) $data['group_id'];
        $weekday = (int) $data['weekday'];
        $stmt->bind_param(
            'iisssssss',
            $groupId,
            $weekday,
            $data['period'],
            $data['module_id'],
            $data['subject'],
            $data['staff_id'],
            $data['classroom'],
            $data['start_date'],
            $data['end_date']
        );
        if (!$stmt->execute()) {
            return false;
        }
        return (int) $this->db->lastInsertId();
    }

    public function deleteSlot($id, $groupId) {
        $this->ensureTable();
        $sql = "UPDATE `group_timetable` SET `active` = 0 WHERE `timetable_id` = ? AND `group_id` = ? AND `active` = 1";
        $stmt = $this->db->prepare($sql);
        $id = (int) $id;
        $groupId = (int) $groupId;
        $stmt->bind_param('ii', $id, $groupId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
}
