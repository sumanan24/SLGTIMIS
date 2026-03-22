<?php
class StockTransferModel extends Model {
    protected $table = 'stock_transfer';

    protected function getPrimaryKey() {
        return 'transfer_id';
    }

    public function createRecord($data, &$sqlError = null) {
        return $this->create($data, $sqlError);
    }
}
