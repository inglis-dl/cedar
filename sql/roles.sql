-- -----------------------------------------------------
-- Roles
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- make sure all roles exist
INSERT IGNORE INTO cenozo.role( name, tier, all_sites ) VALUES
( "administrator", 3, true ),
( "supervisor", 2, false ),
( "typist", 1, true );

-- access

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "access" AND operation.name = "delete"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "access" AND operation.name = "list"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "access" AND operation.name = "primary"
AND role.name IN ( "administrator" );

-- activity

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "activity" AND operation.name = "list"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "activity" AND operation.name = "primary"
AND role.name IN ( "administrator" );

-- assignment

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "assignment" AND operation.name = "edit"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "assignment" AND operation.name = "list"
AND role.name IN ( "administrator", "supervisor", "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "assignment" AND operation.name = "new"
AND role.name IN ( "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "assignment" AND operation.name = "delete"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "assignment" AND operation.name = "view"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "assignment" AND operation.name = "report"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "assignment" AND operation.name = "report"
AND role.name IN ( "administrator", "supervisor" );

-- away_time

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "away_time" AND operation.name = "add"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "away_time" AND operation.name = "delete"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "away_time" AND operation.name = "edit"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "away_time" AND operation.name = "list"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "away_time" AND operation.name = "new"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "away_time" AND operation.name = "view"
AND role.name IN ( "administrator", "supervisor" );

-- cohort

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "cohort" AND operation.name = "list"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "cohort" AND operation.name = "primary"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "cohort" AND operation.name = "view"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "cohort" AND operation.name = "add"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "cohort" AND operation.name = "delete"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "cohort" AND operation.name = "new"
AND role.name IN ( "administrator" );

-- collection

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "collection" AND operation.name = "add"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "collection" AND operation.name = "add_participant"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "collection" AND operation.name = "add_user"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "collection" AND operation.name = "delete"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "collection" AND operation.name = "delete_participant"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "collection" AND operation.name = "delete_user"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "collection" AND operation.name = "edit"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "collection" AND operation.name = "list"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "collection" AND operation.name = "new"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "collection" AND operation.name = "new_participant"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "collection" AND operation.name = "new_user"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "collection" AND operation.name = "view"
AND role.name IN ( "administrator", "supervisor" );

-- dictionary

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "dictionary" AND operation.name = "add"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "dictionary" AND operation.name = "delete"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "dictionary" AND operation.name = "add_word"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "dictionary" AND operation.name = "delete_word"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "dictionary" AND operation.name = "edit"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "dictionary" AND operation.name = "list"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "dictionary" AND operation.name = "new"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "dictionary" AND operation.name = "view"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "dictionary" AND operation.name = "report"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "dictionary" AND operation.name = "report"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "dictionary" AND operation.name = "import"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "dictionary" AND operation.name = "transfer_word"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "dictionary" AND operation.name = "transfer_word"
AND role.name IN ( "administrator", "supervisor" );

-- dictionary_import

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "dictionary_import" AND operation.name = "delete"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "dictionary_import" AND operation.name = "new"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "dictionary_import" AND operation.name = "process"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "dictionary_import" AND operation.name = "process"
AND role.name IN ( "administrator", "supervisor" );

-- event

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "event" AND operation.name = "list"
AND role.name IN ( "administrator", "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "event" AND operation.name = "primary"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "event" AND operation.name = "view"
AND role.name IN ( "administrator" );

-- event_type

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "event_type" AND operation.name = "list"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "event_type" AND operation.name = "primary"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "event_type" AND operation.name = "view"
AND role.name IN ( "administrator" );

-- language

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "language" AND operation.name = "edit"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "language" AND operation.name = "list"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "language" AND operation.name = "view"
AND role.name IN ( "administrator" );

-- note

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "note" AND operation.name = "delete"
AND role.name IN ( "administrator", "supervisor", "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "note" AND operation.name = "edit"
AND role.name IN ( "administrator", "supervisor", "typist" );

-- participant

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "participant" AND operation.name = "list"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "list"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "participant" AND operation.name = "primary"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "view"
AND role.name IN ( "administrator" );

-- productivity

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "productivity" AND operation.name = "report"
AND role.name IN( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "productivity" AND operation.name = "report"
AND role.name IN( "administrator", "supervisor" );

-- ranked_word_set

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "ranked_word_set" AND operation.name = "list"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "ranked_word_set" AND operation.name = "view"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "ranked_word_set" AND operation.name = "add"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "ranked_word_set" AND operation.name = "delete"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "ranked_word_set" AND operation.name = "new"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "ranked_word_set" AND operation.name = "edit"
AND role.name IN ( "administrator" );

-- region_site

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "region_site" AND operation.name = "add"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "region_site" AND operation.name = "delete"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "region_site" AND operation.name = "edit"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "region_site" AND operation.name = "list"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "region_site" AND operation.name = "new"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "region_site" AND operation.name = "view"
AND role.name IN ( "administrator" );

-- jurisdiction

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "jurisdiction" AND operation.name = "add"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "jurisdiction" AND operation.name = "delete"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "jurisdiction" AND operation.name = "edit"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "jurisdiction" AND operation.name = "list"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "jurisdiction" AND operation.name = "new"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "jurisdiction" AND operation.name = "view"
AND role.name IN ( "administrator" );

-- role

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "role" AND operation.name = "list"
AND role.name IN ( "administrator" );

-- service

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "service" AND operation.name = "list"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "service" AND operation.name = "primary"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "service" AND operation.name = "view"
AND role.name IN ( "administrator" );

-- setting

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "setting" AND operation.name = "edit"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "setting" AND operation.name = "list"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "setting" AND operation.name = "primary"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "setting" AND operation.name = "view"
AND role.name IN ( "administrator" );

-- site

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "site" AND operation.name = "add"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "site" AND operation.name = "add_access"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "site" AND operation.name = "delete_access"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "site" AND operation.name = "edit"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "site" AND operation.name = "list"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "site" AND operation.name = "new"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "site" AND operation.name = "new_access"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "site" AND operation.name = "primary"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "site" AND operation.name = "view"
AND role.name IN ( "administrator" );

-- system_message

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "system_message" AND operation.name = "add"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "system_message" AND operation.name = "delete"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "system_message" AND operation.name = "edit"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "system_message" AND operation.name = "list"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "system_message" AND operation.name = "new"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "system_message" AND operation.name = "primary"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "system_message" AND operation.name = "view"
AND role.name IN ( "administrator", "supervisor" );

-- test

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test" AND operation.name = "edit"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "test" AND operation.name = "list"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "test" AND operation.name = "view"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "test" AND operation.name = "add_dictionary"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test" AND operation.name = "delete_dictionary"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "test" AND operation.name = "add_ranked_word_set"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test" AND operation.name = "delete_ranked_word_set"
AND role.name IN ( "administrator" );

-- test_entry

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test_entry" AND operation.name = "edit"
AND role.name IN ( "administrator", "supervisor", "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test_entry" AND operation.name = "reset"
AND role.name IN ( "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test_entry" AND operation.name = "submit"
AND role.name IN ( "administrator", "supervisor", "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test_entry" AND operation.name = "return"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "test_entry" AND operation.name = "list"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "test_entry" AND operation.name = "view"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "test_entry" AND operation.name = "transcribe"
AND role.name IN ( "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "test_entry" AND operation.name = "adjudicate"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "test_entry" AND operation.name = "add_language"
AND role.name IN( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test_entry" AND operation.name = "delete_language"
AND role.name IN( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test_entry" AND operation.name = "new_language"
AND role.name IN( "administrator", "supervisor" );

-- test_entry_alpha_numeric

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test_entry_alpha_numeric" AND operation.name = "edit"
AND role.name IN ( "administrator", "supervisor", "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test_entry_alpha_numeric" AND operation.name = "new"
AND role.name IN ( "administrator", "supervisor", "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test_entry_alpha_numeric" AND operation.name = "delete"
AND role.name IN ( "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "test_entry_alpha_numeric" AND operation.name = "transcribe"
AND role.name IN ( "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "test_entry_alpha_numeric" AND operation.name = "adjudicate"
AND role.name IN ( "administrator", "supervisor" );

-- test_entry_classification

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test_entry_classification" AND operation.name = "edit"
AND role.name IN ( "administrator", "supervisor", "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test_entry_classification" AND operation.name = "new"
AND role.name IN ( "administrator", "supervisor", "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test_entry_classification" AND operation.name = "delete"
AND role.name IN ( "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "test_entry_classification" AND operation.name = "transcribe"
AND role.name IN ( "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "test_entry_classification" AND operation.name = "adjudicate"
AND role.name IN ( "administrator", "supervisor" );

-- test_entry_confirmation

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test_entry_confirmation" AND operation.name = "edit"
AND role.name IN ( "administrator", "supervisor", "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test_entry_confirmation" AND operation.name = "new"
AND role.name IN ( "administrator", "supervisor", "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "test_entry_confirmation" AND operation.name = "transcribe"
AND role.name IN ( "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "test_entry_confirmation" AND operation.name = "adjudicate"
AND role.name IN ( "administrator", "supervisor" );

-- test_entry_ranked_word

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test_entry_ranked_word" AND operation.name = "edit"
AND role.name IN ( "administrator", "supervisor", "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test_entry_ranked_word" AND operation.name = "new"
AND role.name IN ( "administrator", "supervisor", "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "test_entry_ranked_word" AND operation.name = "delete"
AND role.name IN ( "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "test_entry_ranked_word" AND operation.name = "transcribe"
AND role.name IN ( "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "test_entry_ranked_word" AND operation.name = "adjudicate"
AND role.name IN ( "administrator", "supervisor" );

-- typist

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "typist" AND operation.name = "begin_break"
AND role.name IN ( "typist" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "typist" AND operation.name = "end_break"
AND role.name IN ( "typist" );

-- user

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "user" AND operation.name = "add"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "user" AND operation.name = "add_access"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "user" AND operation.name = "add_language"
AND role.name IN( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "delete"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "delete_access"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "delete_language"
AND role.name IN( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "edit"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "user" AND operation.name = "list"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "user" AND operation.name = "list"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "new"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "new_access"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "new_language"
AND role.name IN( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "user" AND operation.name = "primary"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "reset_password"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "set_password"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "user" AND operation.name = "view"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "user" AND operation.name = "add_cohort"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "delete_cohort"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "new_cohort"
AND role.name IN ( "administrator" );

-- word

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "word" AND operation.name = "add"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "word" AND operation.name = "delete"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "word" AND operation.name = "edit"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "word" AND operation.name = "list"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "word" AND operation.name = "new"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "word" AND operation.name = "view"
AND role.name IN ( "administrator", "supervisor" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "word" AND operation.name = "list"
AND role.name IN ( "administrator", "supervisor", "typist" );

COMMIT;
