CREATE PROCEDURE `getDashboardCounts`()

BEGIN
	SET @totalUsers = (SELECT count(id) from users t1 WHERE t1.active = 1 AND t1.user_type=2);

	SET @totalBrands = (SELECT count(*) FROM companies t1 INNER JOIN users t2 ON t2.id = t1.user_id where t1.active=1 AND t2.active=1 AND t2.confirmed= 1);

	SET @totalFund = (SELECT sum(sub_total) from campaigns c WHERE c.is_approved ='APPROVED');

	set @totalRedeemedFund = (SELECT sum(debit)/50 from user_coins WHERE reward_id is not null);

	set @profit= FORMAT(@totalFund/2,2);

	SELECT 
		@totalUsers as TotalUsers,
		@totalBrands as TotalBrands,
		@totalFund as TotalFunds,
		@totalRedeemedFund as TotalRedeems,
		(@totalFund/2) as Profits;

END