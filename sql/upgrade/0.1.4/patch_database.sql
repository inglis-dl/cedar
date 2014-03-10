-- Patch to upgrade database to version 0.1.4

SET AUTOCOMMIT=0;

SOURCE assignment.sql;
SOURCE role_has_operation.sql;

COMMIT;
