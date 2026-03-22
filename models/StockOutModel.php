<?php
class StockOutModel extends Model {
    protected $table = 'stock_out';

    protected function getPrimaryKey() {
        return 'stock_out_id';
    }

    public function createRecord($data, &$sqlError = null) {
        return $this->create($data, $sqlError);
    }
}
