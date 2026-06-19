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
        $sql = "SELECT DISTINCT payment_type FROM `{$this->table}` ORDER BY payment_type ASC";
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
        $sql = "UPDATE `{$this->table}` SET payment_reason = ?, payment_type = ? WHERE payment_reason = ? AND payment_type = ?";
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            $sqlError = $this->db->getConnection()->error ?? 'Prepare failed';
            return false;
        }

        $stmt->bind_param('ssss', $newReason, $newType, $oldReason, $oldType);

        if (!$stmt->execute()) {
            $sqlError = $stmt->error ?? 'Execute failed';
            return false;
        }

        return $stmt->affected_rows >= 0;
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
