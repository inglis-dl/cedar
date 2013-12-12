-- -----------------------------------------------------
-- Dictionaries
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

INSERT IGNORE INTO dictionary( name, description ) 
VALUES( "variants", "variant words for all tests" );
INSERT IGNORE INTO dictionary( name, description ) 
VALUES( "intrusions", "intrusion words for all tests" );
INSERT IGNORE INTO dictionary( name, description ) 
VALUES( "confirmation", "yes and no response words for MAT confirmation type tests" );

INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='confirmation' ), 'en', 'yes' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='confirmation' ), 'en', 'no' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='confirmation' ), 'fr', 'oui' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='confirmation' ), 'fr', 'non' );

COMMIT;
