-- Permanently store collecting finance officer name on each payment bill
ALTER TABLE `pays`
  ADD COLUMN `collected_by` varchar(100) NULL DEFAULT NULL AFTER `payment_method`;
