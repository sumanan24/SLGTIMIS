-- Optional: add semester to `module` if your DB was created before auto-migration.
-- The app also adds this column at runtime via ModuleModel::ensureModuleSemesterColumn().

ALTER TABLE `module`
  ADD COLUMN `semester` TINYINT UNSIGNED NULL DEFAULT NULL COMMENT 'Academic semester (e.g. 1, 2)' AFTER `credit`;
