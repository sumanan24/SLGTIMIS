<?php
/**
 * Payment Type / Reason lookup model (table: payment)
 */

class PaymentTypeModel extends Model {
    protected $table = 'payment';

    protected function getPrimaryKey() {
        return 'payment_reason';
    }

    /**
     * Main types can exist before any sub type is added.
     */
    public function ensureMainTypeTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `payment_main_type` (
                    `payment_type` varchar(100) NOT NULL,
                    PRIMARY KEY (`payment_type`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
        $this->db->query($sql);

        $this->db->query(
            "INSERT IGNORE INTO `payment_main_type` (`payment_type`)
             SELECT DISTINCT `payment_type` FROM `{$this->table}`
             WHERE `payment_type` IS NOT NULL AND `payment_type` <> ''"
        );
    }

    /**
     * Main types with the number of sub types under each.
     */
    public function getMainTypes() {
        $this->ensureMainTypeTable();
        $sql = "SELECT m.payment_type, COUNT(p.payment_reason) AS subtype_count
                FROM `payment_main_type` m
                LEFT JOIN `{$this->table}` p ON p.payment_type = m.payment_type
                GROUP BY m.payment_type
                ORDER BY m.payment_type ASC";
        $result = $this->db->query($sql);

        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }

        return $data;
    }

    public function mainTypeExists($type) {
        $this->ensureMainTypeTable();
        $sql = "SELECT payment_type FROM `payment_main_type` WHERE payment_type = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $type);
        $stmt->execute();
        $result = $stmt->get_result();

        return (bool) $result->fetch_assoc();
    }

    public function createMainType($type, &$sqlError = null) {
        $this->ensureMainTypeTable();
        $sql = "INSERT INTO `payment_main_type` (`payment_type`) VALUES (?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $sqlError = $this->db->getConnection()->error ?? 'Prepare failed';
            return false;
        }
        $stmt->bind_param('s', $type);
        if (!$stmt->execute()) {
            $sqlError = $stmt->error ?? 'Execute failed';
            return false;
        }

        return true;
    }

    public function renameMainType($oldType, $newType, &$sqlError = null) {
        $this->ensureMainTypeTable();
        $db = $this->db;
        if (!$db->begin_transaction()) {
            $sqlError = 'Could not start the update.';
            return false;
        }

        $main = $db->prepare("UPDATE `payment_main_type` SET payment_type = ? WHERE payment_type = ?");
        $lookup = $db->prepare("UPDATE `{$this->table}` SET payment_type = ? WHERE payment_type = ?");
        $pays = $db->prepare("UPDATE `pays` SET payment_type = ? WHERE payment_type = ?");
        if (!$main || !$lookup || !$pays) {
            $db->rollback();
            $sqlError = $db->getConnection()->error ?? 'Prepare failed';
            return false;
        }

        $main->bind_param('ss', $newType, $oldType);
        $lookup->bind_param('ss', $newType, $oldType);
        $pays->bind_param('ss', $newType, $oldType);
        if (!$main->execute() || !$lookup->execute() || !$pays->execute()) {
            $sqlError = $main->error ?: ($lookup->error ?: $pays->error);
            $db->rollback();
            return false;
        }

        $db->commit();
        return true;
    }

    public function subtypeCount($type) {
        $sql = "SELECT COUNT(*) AS total FROM `{$this->table}` WHERE payment_type = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $type);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return (int) ($row['total'] ?? 0);
    }

    public function mainTypeIsUsed($type) {
        $sql = "SELECT COUNT(*) AS total FROM `pays` WHERE payment_type = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $type);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return ((int) ($row['total'] ?? 0)) > 0;
    }

    public function deleteMainType($type) {
        $this->ensureMainTypeTable();
        $sql = "DELETE FROM `payment_main_type` WHERE payment_type = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $type);

        return $stmt->execute();
    }

    /**
     * Get all payment type / reason entries
     */
    public function getAll() {
        $sql = "SELECT payment_reason, payment_type FROM `{$this->table}` ORDER BY payment_type ASC, payment_reason ASC";
        $result = $this->db->query($sql);

        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }

        return $data;
    }

    /**
     * Get distinct payment types
     */
    public function getDistinctTypes() {
        $this->ensureMainTypeTable();
        $sql = "SELECT payment_type FROM `payment_main_type` ORDER BY payment_type ASC";
        $result = $this->db->query($sql);

        $types = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $types[] = $row['payment_type'];
            }
        }

        return $types;
    }

    /**
     * Find entry by composite key
     */
    public function findByKey($reason, $type) {
        $sql = "SELECT payment_reason, payment_type FROM `{$this->table}` WHERE payment_reason = ? AND payment_type = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $reason, $type);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    /**
     * Check if entry exists
     */
    public function exists($reason, $type) {
        return !empty($this->findByKey($reason, $type));
    }

    /**
     * Create payment type / reason entry
     */
    public function createEntry($reason, $type, &$sqlError = null) {
        return $this->create([
            'payment_reason' => $reason,
            'payment_type' => $type,
        ], $sqlError);
    }

    /**
     * Update payment type / reason entry
     */
    public function updateEntry($oldReason, $oldType, $newReason, $newType, &$sqlError = null) {
        $db = $this->db;
        if (!$db->begin_transaction()) {
            $sqlError = 'Could not start the update.';
            return false;
        }

        $lookup = $db->prepare("UPDATE `{$this->table}` SET payment_reason = ?, payment_type = ? WHERE payment_reason = ? AND payment_type = ?");
        $pays = $db->prepare("UPDATE `pays` SET payment_reason = ?, payment_type = ? WHERE payment_reason = ? AND payment_type = ?");
        if (!$lookup || !$pays) {
            $db->rollback();
            $sqlError = $db->getConnection()->error ?? 'Prepare failed';
            return false;
        }

        $lookup->bind_param('ssss', $newReason, $newType, $oldReason, $oldType);
        $pays->bind_param('ssss', $newReason, $newType, $oldReason, $oldType);
        if (!$lookup->execute() || !$pays->execute()) {
            $sqlError = $lookup->error ?: $pays->error;
            $db->rollback();
            return false;
        }

        $db->commit();
        return true;
    }

    /**
     * Delete payment type / reason entry
     */
    public function deleteEntry($reason, $type) {
        $sql = "DELETE FROM `{$this->table}` WHERE payment_reason = ? AND payment_type = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $reason, $type);

        return $stmt->execute();
    }

    /**
     * Check if entry is used in pays records
     */
    public function isUsed($reason, $type) {
        $sql = "SELECT COUNT(*) AS total FROM `pays` WHERE payment_reason = ? AND payment_type = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $reason, $type);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return ($row['total'] ?? 0) > 0;
    }
}
