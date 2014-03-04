-- Patch to upgrade database to version 0.1.3

SET AUTOCOMMIT=0;

SOURCE dictionary.sql;
SOURCE test.sql;

COMMIT;
