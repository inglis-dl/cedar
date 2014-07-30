-- Patch to upgrade database to version 0.1.1

SET AUTOCOMMIT=0;

SOURCE role_has_operation.sql;

-- after all SOURCES, update service version
SOURCE update_version_number.sql;

COMMIT;
