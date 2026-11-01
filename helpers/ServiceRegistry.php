<?php
/**
 * Service provider module: areas, tables, and form fields.
 * Column names here are the only columns the save path may write.
 */
class ServiceRegistry {
    public static function areas() {
        return [
            'security' => ['title' => 'Security', 'icon' => 'fa-shield-alt'],
            'ac' => ['title' => 'Air Conditioning', 'icon' => 'fa-snowflake'],
            'cleaning' => ['title' => 'Cleaning', 'icon' => 'fa-broom'],
            'canteen' => ['title' => 'Canteen', 'icon' => 'fa-utensils'],
            'contracts' => ['title' => 'Contracts', 'icon' => 'fa-file-contract'],
        ];
    }

    public static function areaResources() {
        return [
            'security' => ['staff_security', 'security_attendance', 'security_duty', 'security_incidents', 'security_passes', 'security_irb', 'security_items', 'invoices', 'payments', 'eval_security'],
            'ac' => ['staff_ac', 'ac_units', 'ac_maintenance', 'ac_breakdowns', 'invoices', 'payments', 'contracts', 'eval_ac'],
            'cleaning' => ['staff_cleaning', 'cleaning_attendance', 'cleaning_duty', 'cleaning_inventory', 'cleaning_issues', 'cleaning_purchases', 'cleaning_inspections', 'invoices', 'contracts', 'eval_cleaning'],
            'canteen' => ['staff_canteen', 'contracts', 'canteen_inspections', 'canteen_complaints', 'eval_canteen'],
            'contracts' => ['providers', 'contracts', 'invoices', 'payments', 'documents'],
        ];
    }

    public static function resource($key) {
        $all = self::resources();
        return isset($all[$key]) ? $all[$key] : null;
    }

    public static function resources() {
        $status = ['Active' => 'Active', 'Inactive' => 'Inactive'];
        $employment = ['Working' => 'Working', 'Left' => 'Left', 'Suspended' => 'Suspended'];
        $shifts = ['Morning' => 'Morning', 'Evening' => 'Evening', 'Night' => 'Night'];
        $attendance = ['Present' => 'Present', 'Absent' => 'Absent', 'Leave' => 'Leave', 'Off' => 'Off'];
        $contractStatus = ['Active' => 'Active', 'Expired' => 'Expired', 'Pending Renewal' => 'Pending Renewal', 'Terminated' => 'Terminated'];
        $invoiceStatus = ['Pending' => 'Pending', 'Paid' => 'Paid', 'Overdue' => 'Overdue', 'Cancelled' => 'Cancelled'];
        $score = [];
        for ($i = 1; $i <= 5; $i++) {
            $score[(string) $i] = (string) $i;
        }
        return [
            'providers' => self::def('Service providers', 'all', 'service_providers', 'provider_code', 'SP', 'created_at', [
                'category_id' => self::field('Service category', 'category', true),
                'provider_name' => self::field('Service provider name', 'text', true),
                'contact_person' => self::field('Contact person', 'text'),
                'phone' => self::field('Contact number', 'text'),
                'email' => self::field('Email', 'text'),
                'address' => self::field('Address', 'textarea'),
                'status' => self::field('Status', 'select', true, $status),
                'remarks' => self::field('Remarks', 'textarea'),
            ], ['provider_code', 'provider_name', 'category_id', 'phone', 'status'], ['provider_name', 'provider_code', 'phone']),
            'staff_security' => self::staff('security', 'SEC', true),
            'staff_ac' => self::staff('ac', 'ACS', false),
            'staff_cleaning' => self::staff('cleaning', 'CLN', false),
            'staff_canteen' => self::staff('canteen', 'CAN', false),
            'contracts' => self::def('Contracts and agreements', 'all', 'service_contracts', 'contract_code', 'CON', 'expiry_date', [
                'category_id' => self::field('Service category', 'category', true),
                'provider_id' => self::field('Service provider', 'provider'),
                'reference_no' => self::field('Agreement reference number', 'text'),
                'start_date' => self::field('Commencement date', 'date'),
                'expiry_date' => self::field('Expiry date', 'date'),
                'contract_value' => self::field('Contract value', 'number'),
                'monthly_amount' => self::field('Monthly payment or rental', 'number'),
                'document_path' => self::field('Agreement document', 'file'),
                'responsible_officer' => self::field('Responsible officer', 'text'),
                'status' => self::field('Agreement status', 'select', true, $contractStatus),
                'renewal_of_id' => self::field('Renewed from contract ID', 'number'),
                'renewal_remarks' => self::field('Renewal remarks', 'textarea'),
                'terms_text' => self::field('Terms and conditions', 'textarea'),
            ], ['contract_code', 'category_id', 'provider_id', 'reference_no', 'start_date', 'expiry_date', 'status'], ['contract_code', 'reference_no', 'responsible_officer']),
            'invoices' => self::def('Invoices', 'all', 'service_invoices', 'invoice_no', 'INV', 'invoice_date', [
                'category_id' => self::field('Service category', 'category', true),
                'provider_id' => self::field('Service provider', 'provider'),
                'contract_id' => self::field('Contract', 'contract'),
                'invoice_date' => self::field('Invoice date', 'date'),
                'amount' => self::field('Invoice amount', 'number', true),
                'attendance_days' => self::field('Attendance days used for verification', 'number'),
                'status' => self::field('Payment status', 'select', true, $invoiceStatus),
                'payment_date' => self::field('Payment date', 'date'),
                'document_path' => self::field('Invoice file', 'file'),
                'remarks' => self::field('Remarks', 'textarea'),
            ], ['invoice_no', 'category_id', 'invoice_date', 'amount', 'status', 'payment_date'], ['invoice_no']),
            'payments' => self::def('Payments', 'all', 'service_payments', 'payment_code', 'PAY', 'paid_on', [
                'category_id' => self::field('Service category', 'category', true),
                'invoice_id' => self::field('Invoice ID', 'number'),
                'amount' => self::field('Amount', 'number', true),
                'paid_on' => self::field('Payment date', 'date'),
                'method' => self::field('Method', 'select', false, ['Bank' => 'Bank', 'Cash' => 'Cash', 'Cheque' => 'Cheque']),
                'reference_no' => self::field('Reference number', 'text'),
                'remarks' => self::field('Remarks', 'textarea'),
            ], ['payment_code', 'category_id', 'amount', 'paid_on', 'method', 'reference_no'], ['payment_code', 'reference_no']),
            'documents' => self::def('Documents', 'all', 'service_documents', null, null, 'created_at', [
                'category_id' => self::field('Service category', 'category'),
                'resource_key' => self::field('Related section', 'text', true),
                'record_id' => self::field('Related record ID', 'number'),
                'title' => self::field('Document title', 'text', true),
                'file_path' => self::field('File', 'file', true),
            ], ['title', 'resource_key', 'record_id', 'category_id', 'created_at'], ['title', 'resource_key']),
            'security_attendance' => self::def('Security attendance', 'security', 'security_attendance', null, null, 'attendance_date', [
                'staff_id' => self::field('Security staff', 'staff', true),
                'attendance_date' => self::field('Date', 'date', true),
                'shift' => self::field('Shift', 'select', true, $shifts),
                'location' => self::field('Assigned location', 'text'),
                'status' => self::field('Attendance', 'select', true, $attendance),
                'remarks' => self::field('Remarks', 'text'),
            ], ['attendance_date', 'staff_id', 'shift', 'location', 'status'], ['location']),
            'security_duty' => self::def('Duty roster', 'security', 'security_duty_rosters', null, null, 'duty_date', [
                'staff_id' => self::field('Security staff', 'staff', true),
                'duty_date' => self::field('Duty date', 'date', true),
                'shift' => self::field('Shift', 'select', true, $shifts),
                'location' => self::field('Location', 'text'),
                'remarks' => self::field('Remarks', 'text'),
            ], ['duty_date', 'staff_id', 'shift', 'location'], ['location']),
            'security_incidents' => self::def('Incidents and complaints', 'security', 'security_incidents', 'incident_code', 'INC', 'incident_at', [
                'incident_at' => self::field('Date and time', 'datetime'),
                'category' => self::field('Incident category', 'text'),
                'description' => self::field('Description', 'textarea', true),
                'staff_id' => self::field('Related staff', 'staff'),
                'location' => self::field('Location', 'text'),
                'document_path' => self::field('Supporting document or photo', 'file'),
                'investigation_status' => self::field('Investigation status', 'select', true, ['Open' => 'Open', 'Investigating' => 'Investigating', 'Closed' => 'Closed']),
                'disciplinary_action' => self::field('Disciplinary action', 'textarea'),
                'remarks' => self::field('Remarks', 'textarea'),
                'resolved_on' => self::field('Resolution date', 'date'),
            ], ['incident_code', 'incident_at', 'category', 'staff_id', 'location', 'investigation_status'], ['incident_code', 'category', 'location']),
            'security_passes' => self::def('Gate passes', 'security', 'security_gate_passes', 'pass_code', 'GP', 'issued_on', [
                'pass_type' => self::field('Pass type', 'select', true, ['Temporary' => 'Temporary', 'Permanent' => 'Permanent']),
                'person_details' => self::field('Person details', 'text'),
                'item_details' => self::field('Item details', 'text'),
                'purpose' => self::field('Purpose of entry or exit', 'text', true),
                'issued_on' => self::field('Issue date', 'date'),
                'expires_on' => self::field('Expiry date', 'date'),
                'authorized_officer' => self::field('Authorized officer', 'text'),
                'status' => self::field('Pass status', 'select', true, ['Issued' => 'Issued', 'Returned' => 'Returned', 'Expired' => 'Expired', 'Cancelled' => 'Cancelled']),
                'returned_on' => self::field('Returned on', 'date'),
                'verified_by' => self::field('Verified by', 'text'),
                'remarks' => self::field('Remarks', 'text'),
            ], ['pass_code', 'pass_type', 'person_details', 'purpose', 'issued_on', 'expires_on', 'status'], ['pass_code', 'person_details', 'purpose']),
            'security_irb' => self::def('IRB records', 'security', 'security_irb_records', null, null, 'submitted_on', [
                'reference_no' => self::field('Reference number', 'text', true),
                'title' => self::field('Title', 'text', true),
                'submitted_on' => self::field('Submission date', 'date'),
                'document_path' => self::field('Document', 'file'),
                'status' => self::field('Status', 'select', true, ['Submitted' => 'Submitted', 'Updated' => 'Updated', 'Closed' => 'Closed']),
                'remarks' => self::field('Remarks', 'textarea'),
            ], ['reference_no', 'title', 'submitted_on', 'status'], ['reference_no', 'title']),
            'security_items' => self::def('Item issue and return', 'security', 'security_item_issues', null, null, 'issued_on', [
                'item_name' => self::field('Item', 'text', true),
                'quantity' => self::field('Quantity', 'number', true),
                'staff_id' => self::field('Responsible staff', 'staff'),
                'issued_on' => self::field('Issue date', 'date'),
                'returned_on' => self::field('Return date', 'date'),
                'remarks' => self::field('Remarks', 'text'),
            ], ['item_name', 'quantity', 'staff_id', 'issued_on', 'returned_on'], ['item_name']),
            'eval_security' => self::eval('security', [
                'attendance_score' => self::field('Attendance', 'select', false, $score),
                'punctuality_score' => self::field('Punctuality', 'select', false, $score),
                'discipline_score' => self::field('Discipline and conduct', 'select', false, $score),
                'quality_score' => self::field('Service quality', 'select', false, $score),
                'response_score' => self::field('Incident response', 'select', false, $score),
            ]),
            'ac_units' => self::def('AC units', 'ac', 'ac_units', 'unit_code', 'ACU', 'installed_on', [
                'identification_no' => self::field('Identification number', 'text'),
                'brand' => self::field('Brand', 'text'),
                'model' => self::field('Model', 'text'),
                'capacity_btu' => self::field('Capacity (BTU)', 'number'),
                'building' => self::field('Building', 'text'),
                'room' => self::field('Room or location', 'text'),
                'installed_on' => self::field('Installation date', 'date'),
                'warranty_until' => self::field('Warranty until', 'date'),
                'status' => self::field('Operational status', 'select', true, ['Working' => 'Working', 'Faulty' => 'Faulty', 'Under repair' => 'Under repair', 'Removed' => 'Removed']),
                'provider_id' => self::field('Service provider', 'provider'),
                'remarks' => self::field('Remarks', 'textarea'),
            ], ['unit_code', 'identification_no', 'brand', 'building', 'room', 'status'], ['unit_code', 'identification_no', 'brand', 'room']),
            'ac_maintenance' => self::def('AC maintenance', 'ac', 'ac_maintenance_records', null, null, 'service_date', [
                'unit_id' => self::field('AC unit', 'unit', true),
                'service_date' => self::field('Service date', 'date', true),
                'maintenance_type' => self::field('Maintenance type', 'select', true, ['Preventive' => 'Preventive', 'Corrective' => 'Corrective']),
                'work_performed' => self::field('Work performed', 'textarea'),
                'technician' => self::field('Technician', 'text'),
                'parts_replaced' => self::field('Parts replaced', 'textarea'),
                'cost' => self::field('Cost', 'number'),
                'remarks' => self::field('Remarks', 'textarea'),
                'next_date' => self::field('Next maintenance date', 'date'),
            ], ['service_date', 'unit_id', 'maintenance_type', 'technician', 'cost', 'next_date'], ['technician']),
            'ac_breakdowns' => self::def('AC breakdowns', 'ac', 'ac_breakdown_complaints', 'complaint_code', 'ACB', 'reported_on', [
                'reported_on' => self::field('Reported date', 'date'),
                'unit_id' => self::field('AC unit', 'unit'),
                'location' => self::field('Location', 'text'),
                'description' => self::field('Problem description', 'textarea', true),
                'priority' => self::field('Priority', 'select', true, ['Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High', 'Critical' => 'Critical']),
                'technician' => self::field('Assigned technician', 'text'),
                'progress' => self::field('Work progress', 'select', true, ['Open' => 'Open', 'Assigned' => 'Assigned', 'In progress' => 'In progress', 'Completed' => 'Completed']),
                'completed_on' => self::field('Completion date', 'date'),
                'remarks' => self::field('Repair notes', 'textarea'),
            ], ['complaint_code', 'reported_on', 'unit_id', 'location', 'priority', 'progress'], ['complaint_code', 'location', 'technician']),
            'eval_ac' => self::eval('ac', [
                'quality_score' => self::field('Service quality', 'select', false, $score),
                'response_score' => self::field('Response', 'select', false, $score),
            ]),
            'cleaning_attendance' => self::def('Cleaning attendance', 'cleaning', 'cleaning_attendance', null, null, 'attendance_date', [
                'staff_id' => self::field('Cleaning staff', 'staff', true),
                'attendance_date' => self::field('Date', 'date', true),
                'shift' => self::field('Shift', 'select', false, $shifts),
                'area' => self::field('Cleaning area', 'text'),
                'supervisor' => self::field('Supervisor', 'text'),
                'status' => self::field('Attendance', 'select', true, $attendance),
                'remarks' => self::field('Remarks', 'text'),
            ], ['attendance_date', 'staff_id', 'shift', 'area', 'supervisor', 'status'], ['area', 'supervisor']),
            'cleaning_duty' => self::def('Duty allocation', 'cleaning', 'cleaning_duty_allocations', null, null, 'duty_date', [
                'staff_id' => self::field('Cleaning staff', 'staff', true),
                'duty_date' => self::field('Date', 'date', true),
                'building' => self::field('Building', 'text'),
                'area' => self::field('Cleaning area', 'text'),
                'shift' => self::field('Shift', 'select', false, $shifts),
                'supervisor' => self::field('Supervisor', 'text'),
                'remarks' => self::field('Remarks', 'text'),
            ], ['duty_date', 'staff_id', 'building', 'area', 'shift', 'supervisor'], ['building', 'area']),
            'cleaning_inventory' => self::def('Cleaning equipment', 'cleaning', 'cleaning_inventory', 'item_code', 'CEQ', 'created_at', [
                'item_name' => self::field('Item name', 'text', true),
                'category' => self::field('Category', 'text'),
                'quantity_on_hand' => self::field('Quantity on hand', 'number', true),
                'min_level' => self::field('Minimum stock level', 'number'),
                'location' => self::field('Location', 'text'),
                'remarks' => self::field('Remarks', 'text'),
            ], ['item_code', 'item_name', 'category', 'quantity_on_hand', 'min_level', 'location'], ['item_code', 'item_name', 'category']),
            'cleaning_issues' => self::def('Equipment issue and return', 'cleaning', 'cleaning_item_issues', null, null, 'issued_on', [
                'inventory_id' => self::field('Equipment', 'inventory', true),
                'staff_id' => self::field('Assigned staff', 'staff'),
                'location' => self::field('Location', 'text'),
                'quantity' => self::field('Quantity', 'number', true),
                'issued_on' => self::field('Issue date', 'date'),
                'returned_on' => self::field('Return date', 'date'),
                'condition_note' => self::field('Condition', 'select', false, ['Good' => 'Good', 'Damaged' => 'Damaged', 'Missing' => 'Missing']),
                'remarks' => self::field('Remarks', 'text'),
            ], ['inventory_id', 'staff_id', 'quantity', 'issued_on', 'returned_on', 'condition_note'], ['location']),
            'cleaning_purchases' => self::def('Material purchases', 'cleaning', 'cleaning_material_purchases', null, null, 'purchased_on', [
                'material_name' => self::field('Material name', 'text', true),
                'category' => self::field('Category', 'text'),
                'supplier' => self::field('Supplier', 'text'),
                'quantity' => self::field('Quantity purchased', 'number', true),
                'unit_price' => self::field('Unit price', 'number', true),
                'total_amount' => self::field('Total amount', 'number'),
                'purchased_on' => self::field('Purchase date', 'date'),
                'inventory_id' => self::field('Add to stock item', 'inventory'),
                'remarks' => self::field('Remarks', 'text'),
            ], ['purchased_on', 'material_name', 'supplier', 'quantity', 'unit_price', 'total_amount'], ['material_name', 'supplier']),
            'cleaning_inspections' => self::def('Cleaning inspections', 'cleaning', 'cleaning_inspections', null, null, 'inspected_on', [
                'inspected_on' => self::field('Inspection date', 'date'),
                'location' => self::field('Location', 'text'),
                'officer' => self::field('Inspection officer', 'text'),
                'rating' => self::field('Cleanliness rating', 'select', false, $score),
                'checklist' => self::field('Inspection checklist', 'textarea'),
                'observations' => self::field('Observations', 'textarea'),
                'complaints' => self::field('Complaints', 'textarea'),
                'corrective_action' => self::field('Corrective action', 'textarea'),
            ], ['inspected_on', 'location', 'officer', 'rating'], ['location', 'officer']),
            'eval_cleaning' => self::eval('cleaning', [
                'attendance_score' => self::field('Attendance', 'select', false, $score),
                'quality_score' => self::field('Service quality', 'select', false, $score),
                'discipline_score' => self::field('Conduct', 'select', false, $score),
            ]),
            'canteen_inspections' => self::def('Hygiene inspections', 'canteen', 'canteen_inspections', null, null, 'inspected_on', [
                'inspected_on' => self::field('Inspection date', 'date'),
                'officer' => self::field('Inspection officer', 'text'),
                'hygiene_checklist' => self::field('Food hygiene checklist', 'textarea'),
                'kitchen_cleanliness' => self::field('Kitchen cleanliness', 'select', false, ['Satisfactory' => 'Satisfactory', 'Needs improvement' => 'Needs improvement', 'Unsatisfactory' => 'Unsatisfactory']),
                'storage_conditions' => self::field('Food storage', 'select', false, ['Satisfactory' => 'Satisfactory', 'Needs improvement' => 'Needs improvement', 'Unsatisfactory' => 'Unsatisfactory']),
                'handling_practices' => self::field('Food handling', 'select', false, ['Satisfactory' => 'Satisfactory', 'Needs improvement' => 'Needs improvement', 'Unsatisfactory' => 'Unsatisfactory']),
                'staff_hygiene' => self::field('Staff hygiene', 'select', false, ['Satisfactory' => 'Satisfactory', 'Needs improvement' => 'Needs improvement', 'Unsatisfactory' => 'Unsatisfactory']),
                'water_sanitation' => self::field('Water and sanitation', 'select', false, ['Satisfactory' => 'Satisfactory', 'Needs improvement' => 'Needs improvement', 'Unsatisfactory' => 'Unsatisfactory']),
                'score' => self::field('Inspection score (1-5)', 'select', false, $score),
                'observations' => self::field('Non-compliance observations', 'textarea'),
                'corrective_action' => self::field('Corrective action', 'textarea'),
                'follow_up_on' => self::field('Follow-up date', 'date'),
                'document_path' => self::field('Inspection report', 'file'),
            ], ['inspected_on', 'officer', 'score', 'follow_up_on'], ['officer']),
            'canteen_complaints' => self::def('Canteen complaints', 'canteen', 'canteen_complaints', 'complaint_code', 'CC', 'complained_on', [
                'complained_on' => self::field('Complaint date', 'date'),
                'category' => self::field('Category', 'text'),
                'description' => self::field('Description', 'textarea', true),
                'is_anonymous' => self::field('Anonymous', 'select', true, ['No' => 'No', 'Yes' => 'Yes']),
                'complainant_name' => self::field('Complainant name', 'text'),
                'evidence_path' => self::field('Supporting evidence', 'file'),
                'investigation' => self::field('Investigation', 'textarea'),
                'status' => self::field('Resolution status', 'select', true, ['New' => 'New', 'Investigating' => 'Investigating', 'Resolved' => 'Resolved', 'Closed' => 'Closed']),
                'satisfaction' => self::field('Satisfaction (1-5)', 'select', false, $score),
            ], ['complaint_code', 'complained_on', 'category', 'is_anonymous', 'status'], ['complaint_code', 'category', 'complainant_name']),
            'eval_canteen' => self::eval('canteen', [
                'food_quality' => self::field('Food quality', 'select', false, $score),
                'hygiene_score' => self::field('Hygiene standards', 'select', false, $score),
                'customer_service' => self::field('Customer service', 'select', false, $score),
                'price_compliance' => self::field('Price compliance', 'select', false, $score),
                'agreement_compliance' => self::field('Agreement compliance', 'select', false, $score),
            ]),
        ];
    }

    public static function reports() {
        return [
            'security_attendance' => 'Security attendance',
            'security_attendance_summary' => 'Security monthly attendance summary',
            'security_incidents' => 'Security incidents',
            'security_passes' => 'Security gate passes',
            'ac_maintenance' => 'AC maintenance history',
            'ac_breakdowns' => 'AC breakdowns',
            'cleaning_attendance' => 'Cleaning attendance',
            'cleaning_attendance_summary' => 'Cleaning monthly attendance summary',
            'cleaning_purchases' => 'Cleaning material expenditure',
            'cleaning_expenditure_summary' => 'Cleaning expenditure by category',
            'cleaning_stock' => 'Cleaning inventory balance',
            'cleaning_inspections' => 'Cleaning inspections',
            'canteen_inspections' => 'Canteen hygiene inspections',
            'canteen_complaints' => 'Canteen complaints',
            'payments' => 'Service provider payments',
            'contracts' => 'Contract expiry and renewal',
            'evaluations' => 'Service provider performance',
        ];
    }

    private static function staff($area, $prefix, $police) {
        $employment = ['Working' => 'Working', 'Left' => 'Left', 'Suspended' => 'Suspended'];
        $fields = [
            'full_name' => self::field('Full name', 'text', true),
            'photo_path' => self::field('Photograph', 'file'),
            'designation' => self::field('Designation', 'text'),
            'nic' => self::field('NIC number', 'text', $police),
            'phone' => self::field('Contact number', 'text'),
            'address' => self::field('Address', 'textarea'),
            'email' => self::field('Email', 'text'),
            'employment_status' => self::field('Employment status', 'select', true, $employment),
            'joined_on' => self::field('Joining date', 'date'),
            'left_on' => self::field('Leaving date', 'date'),
            'provider_id' => self::field('Service provider', 'provider'),
        ];
        if (in_array($area, ['security', 'cleaning'], true)) {
            $finger = ['finger_machine_no' => self::field('Finger number', 'text', true)];
            $fields = array_merge(
                array_slice($fields, 0, 3, true),
                $finger,
                array_slice($fields, 3, null, true)
            );
        }
        if ($police) {
            $fields['police_status'] = self::field('Police clearance status', 'select', false, ['Pending' => 'Pending', 'Submitted' => 'Submitted', 'Verified' => 'Verified', 'Expired' => 'Expired']);
            $fields['police_path'] = self::field('Police clearance document', 'file');
            $fields['clearance_issued_on'] = self::field('Clearance issue date', 'date');
            $fields['clearance_valid_until'] = self::field('Clearance valid until', 'date');
        }
        $titles = ['security' => 'Security staff', 'ac' => 'AC service staff', 'cleaning' => 'Cleaning staff', 'canteen' => 'Canteen staff'];
        $list = ['staff_code', 'full_name', 'designation', 'phone', 'employment_status'];
        $search = ['full_name', 'staff_code', 'nic', 'phone'];
        if (isset($fields['finger_machine_no'])) {
            array_splice($list, 2, 0, ['finger_machine_no']);
            $search[] = 'finger_machine_no';
        }
        return self::def($titles[$area], $area, 'service_staff', 'staff_code', $prefix, 'joined_on', $fields, $list, $search);
    }

    private static function eval($area, array $scores) {
        $fields = [
            'staff_id' => self::field('Staff member', 'staff'),
            'period_month' => self::field('Month', 'month', true),
        ];
        foreach ($scores as $name => $field) {
            $fields[$name] = $field;
        }
        $fields['rating'] = self::field('Overall rating (1-5)', 'number');
        $fields['remarks'] = self::field('Remarks', 'textarea');
        $titles = ['security' => 'Security performance', 'ac' => 'AC service performance', 'cleaning' => 'Cleaning performance', 'canteen' => 'Canteen service quality'];
        return self::def($titles[$area], $area, 'service_performance_evaluations', 'eval_code', 'EVL', 'created_at', $fields, ['eval_code', 'period_month', 'staff_id', 'rating'], ['eval_code']);
    }

    private static function def($title, $area, $table, $codeCol, $prefix, $dateCol, array $fields, array $list, array $search) {
        return [
            'title' => $title,
            'area' => $area,
            'table' => $table,
            'code' => $codeCol,
            'prefix' => $prefix,
            'date' => $dateCol,
            'fields' => $fields,
            'list' => $list,
            'search' => $search,
        ];
    }

    private static function field($label, $type, $required = false, array $options = null) {
        return ['label' => $label, 'type' => $type, 'required' => $required, 'options' => $options];
    }
}
