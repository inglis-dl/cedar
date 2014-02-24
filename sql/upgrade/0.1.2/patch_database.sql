-- Patch to upgrade database to version 0.1.2

SET AUTOCOMMIT=0;

SOURCE test_entry_note.sql;
SOURCE operation.sql;
SOURCE role_has_operation.sql;

COMMIT;
