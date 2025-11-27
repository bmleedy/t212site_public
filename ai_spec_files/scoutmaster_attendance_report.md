# Intent
The intent for the new page (AttendanceReport.php) is to provide a view for the boy scouts scoutmaster, patrol leaders, and webmaster to view attendance for meetings and events.  The page will display a grid with scouts in each row, organized by patrol, and a column per meeting or event.  If a scout was present on the day of any meeting or event, a green checked checkbox emoji (✅) will appear in the cell that corresponds to that scout and that event.  For the scoutmaster and webmaster, the page will allow them to edit attendance for the displayed events.

# Approach
1. Create a plan where each step executes the steps below on this one. Please scane the codebase and validate that this plan is feasible as-described. (pause before proceeding to allow me to test)
2. Create the files for the page which will load static content only, and is ok to be shown to everyone, not just Scoutmaster, Webmaster, and patrol leaders. (pause before proceeding to allow me to test)
3. Add the functionality to only present the page to the webmaster (me), scoutmaster, and patrol leaders. (pause before proceeding to allow me to test)
5. Add the feature to show a table with the names of all users of type "Scout" on each row. (pause before proceeding to allow me to test)
6. Add a column to the left of the scout names with the patrol for each scout. (pause before proceeding to allow me to test)
7. Add date selectors above the table but below the page title for start date and end date.  Start date should default to 2 months ago.  End date should default to the present day for the user. (pause before proceeding to allow me to test)
8. Add the feature to display columns to the right of the scout name with a 2-row header. Row 1 of the header should include the date (always populated) Row2 of the header should include the first 15 characters of the name of the event, linked to the event if it exists in the events database.  The following is the criteria for including a date as a column  (pause before proceeding to allow me to test):
  a. Any Tuesday is an event called "Troop Meeting" and should be populated.
  b. Any event in the events table (the table used to populate Events.php).
  c. Events must happen on or AFTER the date in the start date selector to be included
  d. Events must happen on or BEFORE the date in the end date selector to be included
9. Add the feature to link to the Events page for a given event on the second row of the header for that date.  Team meetings will not have events or links associated with them. (pause before proceeding to allow me to test)
10. Add the feature to re-generate the table with updated start and end dates when the start or end date selectors are changed. (pause before proceeding to allow me to test)
11. Add the feature for the scoutmaster and super user to display checkbox inputs instead of emoji on the attendance page.  Checkboxes should be checked if the scout was present or unchecked if the scout was not present. (pause before proceeding to allow me to test)
12. Add the feature to update the scout attendance for a given date when a checkbox is clicked.  This feature should use the same APIs as Attendance.php (pause before proceeding to allow me to test)
13. Add the feature to download a csv of the displayed attendance table, using the same patterns as ListAdults.php (pause before proceeding to allow me to test)



# Expectations for each feature we Create
1. Every feature will be tested in a unit and/or integration test.
2. You will pause for my review after each change for me to test and potentially iterate with you.
3. The page content should be structured consistently with all the other pages on the site with PHP server side page, html template, and php API.
4. You can use pages like Event.php and ListEvents.php as templates.
5. was_present = true indicates attendance
6. was_present = false or if a particular user and date combination is not present in the table indicates non attendance
7. This page will primarily be used on desktop, but try to make it as usable as possible on mobile
