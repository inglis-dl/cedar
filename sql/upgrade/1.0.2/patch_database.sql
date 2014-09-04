-- Patch to upgrade database to version 1.0.2

SET AUTOCOMMIT=0;

SOURCE operation.sql
SOURCE role_has_operation.sql
SOURCE assignment.sql

-- after all SOURCES, update service version
SOURCE update_version_number.sql;

COMMIT;
