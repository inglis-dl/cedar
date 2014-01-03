-- -----------------------------------------------------
-- Tests
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

SET @DICT_CONF_ID = ( SELECT id FROM dictionary WHERE name='confirmation' );

INSERT IGNORE INTO ranked_word_set( test_id, word_en_id, word_fr_id, rank ) 
VALUES( ( SELECT id FROM test WHERE name='MAT (alphabet)' ), 
( SELECT id FROM word WHERE word='yes' AND dictionary_id=@DICT_CONF_ID ),
( SELECT id FROM word WHERE word='oui' AND dictionary_id=@DICT_CONF_ID ), 1 );

INSERT IGNORE INTO ranked_word_set( test_id, word_en_id, word_fr_id, rank ) 
VALUES( ( SELECT id FROM test WHERE name='MAT (alphabet)' ),
( SELECT id FROM word WHERE word='no' AND dictionary_id=@DICT_CONF_ID ),
( SELECT id FROM word WHERE word='non' AND dictionary_id=@DICT_CONF_ID ), 2 );

INSERT IGNORE INTO ranked_word_set( test_id, word_en_id, word_fr_id, rank ) 
VALUES( ( SELECT id FROM test WHERE name='MAT (counting)' ), 
( SELECT id FROM word WHERE word='yes' AND dictionary_id=@DICT_CONF_ID ),
( SELECT id FROM word WHERE word='oui' AND dictionary_id=@DICT_CONF_ID ), 1 );

INSERT IGNORE INTO ranked_word_set( test_id, word_en_id, word_fr_id, rank ) 
VALUES( ( SELECT id FROM test WHERE name='MAT (counting)' ),
( SELECT id FROM word WHERE word='no' AND dictionary_id=@DICT_CONF_ID ),
( SELECT id FROM word WHERE word='non' AND dictionary_id=@DICT_CONF_ID ), 2 );

COMMIT;
