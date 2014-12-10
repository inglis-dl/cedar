SELECT "Updating test table with comprehensive recording names" AS "";

UPDATE test
SET recording_name='COG_WRDLSTREC_COM'
WHERE name='REY';

UPDATE test
SET recording_name='COG_ANMLLLIST_REC_COM'
WHERE name='AFT';

UPDATE test
SET recording_name='COG_CNTTMEREC_COM'
WHERE name='MAT (counting)';

UPDATE test
SET recording_name='COG_ALPTME_REC2_COM'
WHERE name='MAT (alphabet)';

UPDATE test
SET recording_name='COG_ALTTME_REC_COM'
WHERE name='MAT (alternation)';

UPDATE test
SET recording_name='COG_WRDLST2_REC_COM'
WHERE name='REY II';

UPDATE test
SET recording_name='FAS_FREC_DCS'
WHERE name='FAS (f words)';

UPDATE test
SET recording_name='FAS_AREC_DCS'
WHERE name='FAS (a words)';

UPDATE test
SET recording_name='FAS_SREC_DCS'
WHERE name='FAS (s words)';
