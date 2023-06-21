ON DELETE RESTRICT -> PERMISSIONS
db faq title change from varchar 20 to text
SET time_zone = '+03:00'; SELECT @@system_time_zone;
create_time colmn => expense table => type datetime, default datetime
image culumn => apartment_category table => type varchar 100, default null
orders.apartment_category column referencing apartment_category.category_id
orders.order_customer column referencing tenants.tenants_id
orders.check_schedule column referencing check_scheduling.check_id
orders.special_requests column can be null
orders.order_response column enum('pending', 'incomplete', 'paid')