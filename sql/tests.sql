-- -----------------------------------------------------
-- Tests
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

INSERT IGNORE INTO test_type( id, name ) 
VALUES( 1, "confirmation" ), ( 2, "alpha_numeric"), ( 3, "ranked_word"), ( 4, "classification");

INSERT IGNORE INTO test( id, name, rank_words, rank, test_type_id,
 variant_dictionary_id, intrusion_dictionary_id, dictionary_id ) 
VALUES( 1, "REY", true, 1, 3, 1, 2, 5 );

INSERT IGNORE INTO test( id, name, rank, test_type_id,
 variant_dictionary_id, intrusion_dictionary_id ) 
VALUES( 2, "AFT", 2, 4, 1, 2 );

INSERT IGNORE INTO test( id, name, strict, rank, test_type_id, dictionary_id )
VALUES( 3, "MAT (alphabet)", true, 3, 1, 3 );

INSERT IGNORE INTO test( id, name, strict, rank, test_type_id, dictionary_id )
VALUES( 4, "MAT (counting)", true, 4, 1, 3 );

INSERT IGNORE INTO test( id, name, strict, rank, test_type_id, dictionary_id )
VALUES( 5, "MAT (alternation)", true, 5, 2, 4 );

INSERT IGNORE INTO test( id, name, rank_words, rank, test_type_id,
 variant_dictionary_id, intrusion_dictionary_id, dictionary_id ) 
VALUES( 6, "REY II", true, 6, 3, 1, 2, 5 );

INSERT IGNORE INTO test( id, name, rank, test_type_id, 
 variant_dictionary_id, intrusion_dictionary_id ) 
VALUES( 7, "FAS (f words)", 7, 4, 1, 2 );

INSERT IGNORE INTO test( id, name, rank, test_type_id, 
 variant_dictionary_id, intrusion_dictionary_id ) 
VALUES( 8, "FAS (a words)", 8, 4, 1, 2 );

INSERT IGNORE INTO test( id, name, rank, test_type_id, 
 variant_dictionary_id, intrusion_dictionary_id ) 
VALUES( 9, "FAS (s words)", 9, 4, 1, 2 );

COMMIT;
