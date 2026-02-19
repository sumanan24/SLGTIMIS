<?php
/**
 * Deleted Student Model
 * Stores details of deleted students (logged by DB trigger)
 */

class DeletedStudentModel extends Model {
    protected $table = 'deleted_students';

    protected function getPrimaryKey() {
        return 'id';
    }
}

