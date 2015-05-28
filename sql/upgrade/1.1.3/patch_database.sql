-- Patch to upgrade database to version 1.1.3

SET AUTOCOMMIT=0;

SOURCE activity.sql;

-- after all SOURCES, update service version
-- NOTE: patch_database.php takes care of this update

COMMIT;
