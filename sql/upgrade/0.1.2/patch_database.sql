-- Patch to upgrade database to version 0.1.2

SET AUTOCOMMIT=0;

SOURCE test_entry_note.sql;
SOURCE operation.sql;
SOURCE role_has_operation.sql;
SOURCE activity.sql;
SOURCE operation2.sql;
SOURCE test_entry_alpha_numeric.sql;
SOURCE test_entry_classification.sql;
SOURCE assignment.sql;

-- after all SOURCES, update service version
SOURCE update_version_number.sql;

COMMIT;
