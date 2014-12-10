-- Patch to upgrade database to version 1.1.0

SET AUTOCOMMIT=0;

SOURCE test_entry.sql;
SOURCE recording.sql;
SOURCE test.sql;
SOURCE test2.sql;
SOURCE ranked_word_set_has_language.sql;
SOURCE test_entry_total_deferred.sql;
SOURCE assignment_total.sql;

-- after all SOURCES, update service version
SOURCE update_version_number.sql;

COMMIT;
