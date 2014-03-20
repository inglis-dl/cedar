-- Patch to upgrade database to version 0.1.3

SET AUTOCOMMIT=0;

SOURCE dictionary.sql;
-- call after dictionary.sql
SOURCE test.sql;
SOURCE assignment.sql;
SOURCE operation.sql;
SOURCE role_has_operation.sql;
SOURCE test_entry.sql;
SOURCE away_time.sql;
SOURCE user_time.sql;

COMMIT;
