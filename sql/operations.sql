-- -----------------------------------------------------
-- Operations
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- assignment

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "assignment", "add", true, "View a form for creating a new assignment." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "assignment", "edit", true, "Edits an assignment's details." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "assignment", "list", true, "Lists assignments." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "assignment", "new", true, "Creates a new assignment." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "assignment", "view", true, "View the details of an assignment." );

-- dictionary

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "dictionary", "add", true, "View a form for creating a new dictionary." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "dictionary", "delete", true, "Removes a dictionary from the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "dictionary", "edit", true, "Edits a dictionary's details." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "dictionary", "list", true, "List dictionarys in the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "dictionary", "new", true, "Add a new dictionary to the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "dictionary", "view", true, "View a dictionary's details." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "dictionary", "add_word", true, "A form to add a new word to a dictionary." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "dictionary", "delete_word", true, "Remove words from a dictionary." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "dictionary", "report", true, "Download a dictionary report." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "dictionary", "report", true, "View a form to select a dictionary report." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "dictionary", "import", true, "View a form to import words from a CSV file." );

-- dictionary_import

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "dictionary_import", "new", true, "Add a new dictionary import to the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "dictionary_import", "delete", true, "Removes a dictionay import from the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "dictionary_import", "process", true, "Adds unique words to a dictionary from a CSV file." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "dictionary_import", "process", true, "Processes words from a CSV file." );

-- ranked_word_set

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "ranked_word_set", "add", true, "View a form for creating a new ranked word set." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "ranked_word_set", "delete", true, "Removes a ranked word set from the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "ranked_word_set", "edit", true, "Edits a ranked word set's details." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "ranked_word_set", "list", true, "List ranked word sets in the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "ranked_word_set", "new", true, "Add a new ranked word set to the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "ranked_word_set", "view", true, "View a ranked word set's details." );

-- test

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test", "edit", true, "Edits a test's details." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test", "list", true, "List tests in the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test", "view", true, "View a test's details." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test", "add_dictionary", true, "A form to add a dictionary to a test." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test", "delete_dictionary", true, "Remove a dictionary from a test." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test", "add_ranked_word_set", true, "A form to create a ranked word set to add to a test." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test", "delete_ranked_word_set", true, "A form to create a ranked word set to add to a test." );

-- test_entry

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry", "delete", true, "Removes a test_entry from the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry", "edit", true, "Edits a test_entry's details." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry", "reset", true, "Resets the sub entries in a test_entry." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test_entry", "list", true, "Lists an assignment's test_entries." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry", "new", true, "Creates a new assignment test_entry." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test_entry", "view", true, "View the details of an assignment's test_entries." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test_entry", "transcribe", true, "View a form for transcribing recordings into test entries." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test_entry", "adjudicate", true, "View and edit paired test_entry details." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry", "submit", true, "Create a new test_entry from an ajudication." );

-- test_entry_alpha_numeric

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry_alpha_numeric", "edit", true, "Edits an alpha numeric test entry." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry_alpha_numeric", "new", true, "Creates a new alpha numeric test entry." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry_alpha_numeric", "delete", true, "Deletes an alpha numeric test entry." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test_entry_alpha_numeric", "transcribe", true, "Transcribe an alpha numeric test entry." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test_entry_alpha_numeric", "adjudicate", true, "View and edit paired test_entry details." );

-- test_entry_classification

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry_classification", "edit", true, "Edits a classification test entry." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry_classification", "new", true, "Creates a new classification test entry." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry_classification", "delete", true, "Deletes a classification test entry." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test_entry_classification", "transcribe", true, "Transcribe a classification test entry." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test_entry_classification", "adjudicate", true, "View and edit paired test_entry details." );

-- test_entry_confirmation

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry_confirmation", "edit", true, "Edits a confirmation test entry." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry_confirmation", "new", true, "Creates a new confirmation test entry." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry_confirmation", "delete", true, "Deletes a confirmation test entry." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test_entry_confirmation", "transcribe", true, "Transcribe a confirmation test entry." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test_entry_confirmation", "adjudicate", true, "View and edit paired test_entry details." );

-- test_entry_ranked_word

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry_ranked_word", "edit", true, "Edits an entry for an ranked word test type." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry_ranked_word", "new", true, "Creates a new entry for an ranked word test type." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry_ranked_word", "deletes", true, "Deletes an entry for an ranked word test type." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test_entry_ranked_word", "transcribe", true, "Transcribe an entry for an ranked word test type." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test_entry_ranked_word", "adjudicate", true, "View and edit paired test_entry details." );

-- user

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "user", "add_cohort", true, "A form to add a cohort to a user." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "user", "new_cohort", true, "Add a cohort to a user." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "user", "delete_cohort", true, "Remove a user's cohort." );

-- word

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "word", "add", true, "View a form for creating a new word." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "word", "delete", true, "Removes a word from the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "word", "edit", true, "Edits a word's details." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "word", "list", true, "Lists a dictionary's words." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "word", "new", true, "Creates a new dictionary word." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "word", "view", true, "View the details of a dictionary's words." );

COMMIT;
