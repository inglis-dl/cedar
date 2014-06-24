-- Patch to upgrade database to version 1.0.1

SET AUTOCOMMIT=0;

SOURCE recording.sql
SOURCE word.sql
SOURCE ranked_word_set_has_language.sql
SOURCE ranked_word_set.sql
SOURCE ranked_word_word_total.sql
SOURCE operation.sql
SOURCE role_has_operation.sql

-- after all SOURCES, update service version
SOURCE update_version_number.sql;

COMMIT;
