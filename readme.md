
edited => faq.title change from varchar 20 to text
SET time_zone = '+03:00'; SELECT @@system_time_zone;
added => create_time colmn => expense table => type datetime, default datetime
added => image culumn => apartment_category table => type varchar 100, default null

edited => orders.apartment_category column referencing apartment_category.category_id ON DELETE RESTRICT ON UPDATE CASCADE
edited => orders.order_customer column referencing user.user_id ON DELETE CASCADE ON UPDATE CASCADE
edited => orders.check_schedule column referencing check_scheduling.check_id ON DELETE RESTRICT ON UPDATE RESTRICT
edited => orders.special_requests column can be null
//edited => orders.order_response column enum('pending', 'incomplete', 'paid')

edited => check_scheduling. change from text to int
edited => check_scheduling.apartment_reference column referencing apartments.apartment_id ON DELETE RESTRICT ON UPDATE CASCADE
added => check_scheduling.user_ref column referencing user.user_id ON DELETE CASCADE ON UPDATE CASCADE

edited => staff.user_reference column referencing user.user_id ON DELETE CASCADE ON UPDATE CASCADE
edited => staff.designation column referencing designation.designation_id ON DELETE RESTRICT ON UPDATE CASCADE
edited => staff.staff_date_employed default datetime

edited => user.system_role column referencing role.role_id ON DELETE RESTRICT ON UPDATE CASCADE
edited => user.created_by column referencing user.user_id ON DELETE SET NULL ON UPDATE CASCADE

edited => requests.house_details to requests.house referencing apartments.apartment_id ON DELETE CASCADE ON UPDATE CASCADE
edited => requests.requester referencing apartments.apartment_id ON DELETE CASCADE ON UPDATE CASCADE

ALTER TABLE `requests` CHANGE `submitted_date` `submitted_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `apartment_category` CHANGE `asserts` `assets` JSON NOT NULL;