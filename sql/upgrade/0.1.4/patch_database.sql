-- Patch to upgrade database to version 0.1.4

SET AUTOCOMMIT=0;

-- after all SOURCES, update service version
SOURCE update_version_number.sql;

COMMIT;