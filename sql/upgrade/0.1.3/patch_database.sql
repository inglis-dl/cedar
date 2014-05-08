-- Patch to upgrade database to version 0.1.3

SET AUTOCOMMIT=0;

SOURCE assignment.sql;
SOURCE role_has_operation.sql;
SOURCE operation.sql;
SOURCE role_has_operation2.sql;
SOURCE test_entry.sql;
SOURCE away_time.sql;
SOURCE user_time.sql;
SOURCE test_entry_ranked_word.sql
SOURCE test_entry_classification.sql
SOURCE sabretooth_recording.sql
SOURCE test_entry_total.sql
SOURCE test_entry_total_deferred.sql
SOURCE test_entry_total_completed.sql
SOURCE test_entry_total_adjudicate.sql
SOURCE assignment_total.sql
SOURCE test.sql

-- after all SOURCES, update service version
SOURCE update_version_number.sql;

COMMIT;
