-- -----------------------------------------------------
-- Dictionaries
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

INSERT IGNORE INTO dictionary( name, description ) 
VALUES( "variants", "variant words for all tests" );
INSERT IGNORE INTO dictionary( name, description ) 
VALUES( "intrusions", "intrusion words for all tests" );

COMMIT;
