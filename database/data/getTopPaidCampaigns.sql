CREATE PROCEDURE `getTopPaidCampaigns`(IN `FromDate` VARCHAR(20), IN `ToDate` VARCHAR(20))
BEGIN 

	SELECT
		ca.id as id,	
		campaign_name as campaign,
		campaign_type as category,
		company_name as brand,
		sub_total as amount,
		ca.start_date as startDate
	FROM
		campaigns ca
	INNER JOIN companies c ON
		ca.company_id = c.id
	WHERE
	ca.start_date BETWEEN FromDate AND ToDate
	AND ca.is_approved = 'APPROVED'
	AND ca.campaign_status = 'APPROVED'
	ORDER BY
		ca.sub_total
	DESC
	LIMIT 10;

END