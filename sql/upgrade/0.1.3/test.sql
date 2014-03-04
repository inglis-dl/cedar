SELECT "Updating tests" AS "";

UPDATE test SET intrusion_dictionary_id=(SELECT id FROM dictionary WHERE name='REY_Intrusion')
WHERE name='REY';
