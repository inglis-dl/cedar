-- -----------------------------------------------------
-- Tests
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

INSERT IGNORE INTO test( name ) VALUES( "REY" );
INSERT IGNORE INTO test( name ) VALUES( "REY II" );
INSERT IGNORE INTO test( name ) VALUES( "AFT" );
INSERT IGNORE INTO test( name ) VALUES( "FAS (f words)" );
INSERT IGNORE INTO test( name ) VALUES( "FAS (a words)" );
INSERT IGNORE INTO test( name ) VALUES( "FAS (s words)" );

UPDATE test SET intrusion_dictionary_id = (
SELECT id FROM dictionary WHERE name='intrusions' );

UPDATE test SET variant_dictionary_id = (
SELECT id FROM dictionary WHERE name='variants' );

INSERT IGNORE INTO test( name, strict ) VALUES( "MAT (alphabet)", true );
INSERT IGNORE INTO test( name, strict ) VALUES( "MAT (counting)", true );
INSERT IGNORE INTO test( name, strict ) VALUES( "MAT (alternation)", true );

COMMIT;
