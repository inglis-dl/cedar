-- -----------------------------------------------------
-- Tests
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

INSERT IGNORE INTO test( name, rank_words, rank ) VALUES( "REY", true, 1 );
INSERT IGNORE INTO test( name, rank_words, rank ) VALUES( "REY II", true, 6 );
INSERT IGNORE INTO test( name, rank ) VALUES( "AFT", 2 );
INSERT IGNORE INTO test( name, rank ) VALUES( "FAS (f words)", 7 );
INSERT IGNORE INTO test( name, rank ) VALUES( "FAS (a words)", 8 );
INSERT IGNORE INTO test( name, rank ) VALUES( "FAS (s words)", 9 );

UPDATE test SET intrusion_dictionary_id = (
SELECT id FROM dictionary WHERE name='intrusions' );

UPDATE test SET variant_dictionary_id = (
SELECT id FROM dictionary WHERE name='variants' );

INSERT IGNORE INTO test( name, strict, rank ) VALUES( "MAT (alphabet)", true, 3 );
INSERT IGNORE INTO test( name, strict, rank ) VALUES( "MAT (counting)", true, 4 );
INSERT IGNORE INTO test( name, strict, rank ) VALUES( "MAT (alternation)", true, 5 );

COMMIT;
