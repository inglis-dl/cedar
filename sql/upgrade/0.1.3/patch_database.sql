-- Patch to upgrade database to version 0.1.3

SET AUTOCOMMIT=0;

SOURCE dictionary.sql;
SOURCE test.sql;
SOURCE assignment.sql;
SOURCE operation.sql;
SOURCE role_has_operation.sql;
SOURCE test_entry.sql;
SOURCE away_time.sql;

COMMIT;
