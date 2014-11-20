SELECT "Changing test_entry_note foreign key on delete to cascade" AS "";

ALTER TABLE test_entry_note
DROP FOREIGN KEY fk_test_entry_note_test_entry_id;

ALTER TABLE test_entry_note
ADD CONSTRAINT fk_test_entry_note_test_entry_id
FOREIGN KEY (test_entry_id)
REFERENCES test_entry ( id )
ON DELETE CASCADE
ON UPDATE NO ACTION;
