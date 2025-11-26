# Intent
The intent for the new page (Attendance.php) is to provide a simple interface for boy scout patrol leaders to take daily attendance for meetings and events.  The page will display the members of the partrol leader's patrol in a table and will allow them to check or uncheck a checkbox beside the name of their patrol member which will store that member's attendance status for the day.

# Approach
1. Create a plan where each step executes the steps below on this one. Please scane the codebase and validate that this plan is feasible as-described.
2. Create the files for the page which will load static content only, and is ok to be shown to everyone, not just patrol leaders.
3. Add the functionality to only present the page to the webmaster (me), scoutmaster, and patrol leaders.
4. Add the ability to select a patrol from a dropdown, which is populated from the database patrols table.  In the patrol dropdown, there should be options for all patrols including "Staff" and an option for "none".
5. Add the feature to display the members of the patrol selected in the dropdown in a table, updating the table when the dropdown is changed.
6. Add the feature for the dropdown to default to the patrol in which the scout is a member, if they are in a patrol.  Otherwise for adults or scouts without patrol, default to "staff"
7. Add the feature to display a clickable checkbox beside each scouts name in a separate column on the leftmost column of the table.
8. Add the feature that when a checkbox is clicked, the page makes an idempotent ajax call to update the scout's attendance for the current day.  The current day is defined by the current date when the request reaches the database in Pacific Time Zone.  When the checkbox is checked, an entry for the scout and date should be updated or created with was_present=true. When the checkbox is unchecked, the entry for the scout and date should be updated with was_present=false.  The AJAX and API calls will not include a date in this case.
9. Add a feature to display the date which will be updated by the page in a date selector widget near the top of the page. The selector should select date only, not time.
10. Add a feature only available to scoutmasters and webmasters to use the date selector to update different days, sending a date parameter in the AJAX call and the api call to the database which specifies the date for which the the database entry is to be created or updated.
11. Add hover-over instructions for scouts, including instructions to ask their webmaster to update attendance for days not on the present day.


# Expectations for each feature we Create
1. Every feature will be tested in a unit and/or integration test.
2. You will pause for my review after each change for me to test and potentially iterate with you.
3. The page content should be structured consistently with all the other pages on the site with PHP server side page, html template, and php API.
4. You can use pages like Event.php and ListEvents.php as templates.
5. I will create a table named "attendance_daily" with the following columns: id, user_id, date, was_present.
6. Database column types will be integer, integer, date, boolean
7. id, user_id, and date will be indices
8. was_present = true indicates attendance
9. was_present = false or if a particular user and date combination is not present in the table indicates non attendance
10. The page should be sized such that it works optimally for mobile, but may also be used on a desktop browser.
