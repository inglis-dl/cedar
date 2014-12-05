SELECT "Removing defunct audio_fault column" AS "";

ALTER TABLE test_entry DROP COLUMN audio_fault;
