SELECT "Adding new unique index to test_entry_classification" AS "";

ALTER TABLE test_entry_classification
ADD UNIQUE INDEX uq_test_entry_id_rank
(test_entry_id ASC, rank ASC);
