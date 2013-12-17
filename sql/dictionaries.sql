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
INSERT IGNORE INTO dictionary( name, description ) 
VALUES( "alpha-numeric", "alpha numeric response words for the MAT (alternation) test" );

INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='confirmation' ), 'en', 'yes' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='confirmation' ), 'en', 'no' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='confirmation' ), 'fr', 'oui' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='confirmation' ), 'fr', 'non' );

INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'a' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'b' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'c' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'd' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'e' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'f' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'g' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'h' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'i' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'j' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'k' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'l' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'm' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'n' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'o' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'p' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'q' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'r' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 's' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 't' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'u' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'v' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'w' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'x' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'y' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', 'z' ); 

INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'a' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'b' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'c' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'd' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'e' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'f' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'g' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'h' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'i' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'j' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'k' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'l' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'm' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'n' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'o' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'p' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'q' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'r' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 's' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 't' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'u' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'v' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'w' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'x' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'y' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', 'z' ); 

INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '1' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '2' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '3' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '4' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '5' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '6' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '7' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '8' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '9' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '10' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '11' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '12' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '13' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '14' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '15' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '16' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '17' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '18' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '19' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'en', '20' );  

INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '1' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '2' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '3' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '4' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '5' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '6' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '7' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '8' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '9' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '10' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '11' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '12' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '13' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '14' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '15' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '16' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '17' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '18' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '19' );  
INSERT IGNORE INTO word( dictionary_id, language, word )
VALUES( ( SELECT id FROM dictionary WHERE name='alpha-numeric' ), 'fr', '20' );  

COMMIT;
