USE foundation97;
INSERT INTO donation_accounts(title,bank_name,account_name,account_number,iban,swift_code,branch_name,currency,instructions,contact_name,contact_position,contact_phone,contact_email,is_active,sort_order)
VALUES ('Primary Donation Account','YOUR BANK NAME','97 FOUNDATION','REPLACE-WITH-ACCOUNT-NUMBER','REPLACE-WITH-IBAN','REPLACE-WITH-SWIFT','Polonnaruwa Main Branch','LKR','Replace these placeholders from Admin > Donation Settings before going live.','Donation Coordinator','Accounts & Donations','+94 27 000 0000','donations@example.org',1,1)
ON DUPLICATE KEY UPDATE title=VALUES(title);

INSERT INTO campaigns(title,slug,short_description,target_amount,currency,category,location,status,start_date,end_date)
VALUES
('Help 100 Families','help-100-families','Demo campaign — replace from the admin panel before launch.',50000,'LKR','Community Support','Thambala, Polonnaruwa, Sri Lanka','active',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 90 DAY)),
('Education for All','education-for-all','Demo campaign — replace from the admin panel before launch.',50000,'LKR','Education','Thambala, Polonnaruwa, Sri Lanka','active',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 120 DAY))
ON DUPLICATE KEY UPDATE title=VALUES(title);
