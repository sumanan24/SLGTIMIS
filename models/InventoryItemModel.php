<?php
/**
 * Inventory Item Model
 */

class InventoryItemModel extends Model {
    protected $table = 'inventory_item';

    protected function getPrimaryKey() {
        return 'item_id';
    }

    /**
     * Get all inventory items
     */
    public function getAllItems() {
        return $this->all('item_id ASC');
    }

    /**
     * Get item by ID
     */
    public function getById($itemId) {
        return $this->find($itemId);
    }

    /**
     * Create new inventory item
     */
    public function createItem($data, &$sqlError = null) {
        return $this->create($data, $sqlError);
    }

    /**
     * Update inventory item
     */
    public function updateItem($itemId, $data, &$sqlError = null) {
        return $this->update($itemId, $data, $sqlError);
    }

    /**
     * Delete inventory item
     */
    public function deleteItem($itemId) {
        return $this->delete($itemId);
    }

    /**
     * Check if item exists
     */
    public function exists($itemId) {
        $item = $this->find($itemId);
        return $item !== null;
    }
}

