<?php
class StockInModel extends Model {
    protected $table = 'stock_in';

    protected function getPrimaryKey() {
        return 'stock_in_id';
    }

    public function createRecord($data, &$sqlError = null) {
        return $this->create($data, $sqlError);
    }
}
