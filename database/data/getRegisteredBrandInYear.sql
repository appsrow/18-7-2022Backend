CREATE PROCEDURE `getRegisteredBrandInYear`(IN `yrs` INT(10))
        DETERMINISTIC
BEGIN
    SET @v1=1;
    CREATE TEMPORARY TABLE result_table(Month varchar(20),Count int);
    
    WHILE @v1<13 DO
	    INSERT INTO result_table
            SELECT  DATE_FORMAT(CONCAT(yrs,'-',@v1 ,'-01'), '%b') AS Month, COUNT(1) as Count FROM companies t1 JOIN users t2 ON t2.id=t1.user_id WHERE t1.active=1 AND t2.confirmed=1 AND DATE_FORMAT(t1.created_at, '%m-%Y') = DATE_FORMAT(concat(yrs,'-',@v1 ,'-01'), '%m-%Y');
    
        set @v1 = @v1 + 1;
    
    END WHILE;

	SELECT * FROM result_table;

	DROP TEMPORARY TABLE result_table;

END