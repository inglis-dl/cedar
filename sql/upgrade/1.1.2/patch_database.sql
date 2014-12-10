-- Patch to upgrade database to version 1.1.2

SET AUTOCOMMIT=0;

SOURCE test_entry_has_language.sql;
SOURCE role_has_operation.sql;
SOURCE operation.sql;
SOURCE test_entry_note.sql;
SOURCE operation2.sql;
SOURCE role_has_operation2.sql;
SOURCE test_entry.sql;

-- after all SOURCES, update service version
SOURCE update_version_number.sql;

COMMIT;
