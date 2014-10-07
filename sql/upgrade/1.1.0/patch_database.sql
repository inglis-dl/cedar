-- Patch to upgrade database to version 1.1.0

SET AUTOCOMMIT=0;

SOURCE test_entry.sql;

-- after all SOURCES, update service version
SOURCE update_version_number.sql;

COMMIT;
