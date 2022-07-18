CREATE PROCEDURE `getCampaignReport`(IN `FromDate` VARCHAR(20), IN `ToDate` VARCHAR(20), IN `SelectBrands` TEXT, IN `SelectCampaignType` TEXT, IN `SelectCampaign` TEXT, IN `SelectStatus` TEXT)
    DETERMINISTIC
BEGIN

	SET @FilterQuery = 'SELECT t1.id,t2.user_id,t1.campaign_name,t1.campaign_type_name,t2.company_name,t1.start_date,t1.end_date,t1.user_target,t1.sub_total,\r\n    \tt1.cac, (CASE  WHEN (t1.end_date < CURRENT_DATE()) THEN 3 WHEN (t1.start_date > CURRENT_DATE()) THEN 1  ELSE 2\tEND) AS campaignstatus FROM `campaigns` t1 INNER JOIN companies t2 ON t1.company_id = t2.id INNER JOIN users t3 ON t2.user_id = t3.id AND t3.active=1 AND t3.confirmed = 1 WHERE t1.active = 1 AND     t1.is_start = "1" AND     t1.campaign_status = "APPROVED"     AND t1.is_approved= "APPROVED" ';
	
	IF(FromDate !='' && ToDate !='')THEN
    	SET @FilterQuery= concat(@FilterQuery," AND ( ( (start_date BETWEEN '",FromDate,"' AND '",ToDate,"') OR (end_date BETWEEN '",FromDate,"' AND '",ToDate,"') ) OR ( (start_date BETWEEN '",FromDate,"' AND '",ToDate,"') AND (end_date BETWEEN '",FromDate,"' AND '",ToDate,"') ) ) ");
    END IF;

	IF(SelectBrands != '') THEN
    	SET @FilterQuery= concat(@FilterQuery," AND t3.id IN(",SelectBrands,") ");
   	END IF;
    
    IF(SelectCampaignType != '') THEN
    	SET @FilterQuery= concat(@FilterQuery," AND FIND_IN_SET(t1.campaign_type,'",SelectCampaignType,"')");
   	END IF;
    
	IF(SelectCampaign != '') THEN
		SET @FilterQuery= concat(@FilterQuery," AND t1.id IN(",SelectCampaign,") ");
	END IF; 
    

	SET @CompletedUser = ' IFNULL(CompletedResult.total,0) ';
	SET @Allocated = concat('((',@CompletedUser,' * c.cac))');
	SET @Credit = concat('(c.sub_total - ',@Allocated,')');
	SET @EstimatedProfit = concat(' FORMAT((c.sub_total*50)/100,2) ');

	SET @Columns = concat('ca.id AS CampaignId,ca.user_id AS UserId,ca.campaign_name AS CampaignName,ca.campaign_type_name AS Type,ca.company_name AS BrandName,ca.start_date AS StartDate,ca.end_date AS EndDate,ca.campaignstatus AS CampaignStatus,ca.user_target AS TargetedUsers,ca.sub_total AS CampaignFunding, ',@CompletedUser,' AS CompletedUsers,(ca.user_target - ',@CompletedUser,') AS ShortFall, ',@Allocated,' AS AllocatedFund, ',@Credit,' AS CreditFund, ',@EstimatedProfit,' AS EstimatedProfit ');

	SET @Join = concat("INNER JOIN ( ", @FilterQuery ," ) ca ON c.id = ca.id");
	SET @Join = concat(@Join,' LEFT JOIN (SELECT cc.campaign_id,count(cc.id) as total FROM campaign_clicks cc WHERE cc.is_completed = 1 GROUP BY cc.campaign_id) CompletedResult ON ca.id = CompletedResult.campaign_id ');

	SET @sql=concat('SELECT ', @Columns,' FROM campaigns c ', @Join,' WHERE 1=1 ');
	IF(SelectStatus != '') THEN
    	SET @sql= concat(@sql," AND ca.CampaignStatus IN(",SelectStatus,") ");
   	END IF;
	 
	/*select @sql;*/
    PREPARE stmt FROM @sql;
	EXECUTE stmt;
	DEALLOCATE PREPARE stmt;
END