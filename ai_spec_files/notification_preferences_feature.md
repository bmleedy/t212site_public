# Intent
The intent for this new feature is to provide users of my PHP website the ability to specify whether they want to receive emails for specific types of actions which happen on the website.  Users will be able to view and edit these preferences on User.php.  The page will display the members of the partrol leader's patrol in a table and will allow them to check or uncheck a checkbox beside the name of their patrol member which will store that member's attendance status for the day.

# Requirements
0. A list of notification preference database shortnames and display names will be stored in public_html/includes/notification_types.php
1. Preferences will be stored in a JSON OBJECT string of in key-value pairs in the database 'users' table in VARCHAR(50) column named 'notif_preferences'.  The default for this column is NULL.
2. If no data for a specified setting, assume user is opted INTO the notification.
3. Users are opted into notifications by default.
4. Users can view and edit settings for all of their notification preferences on User.php, even if there's no entry in the database.
5. Users.php will present the options as a list of checkboxes at the bottom of the form on the page.
6. Settings will be presented to users as checkboxes in two columns using the display names in notification_types.php
7. Keys for the database JSON will all be 4 letters, sourced from notification_types.php, and will be readable representations of the display name.
8. These are the initial settings to implement:
  1. Display Name: "Scout Signup Emails"; Implemented at:  Event.html and Ssignups.html; Tooltip Text: "Check this box to receive an email notification when your scout signs up for an event."
  2. Display Name: "Roster Emails"; Implemented at: ListAdults.php;Tooltip Text: "Check this box to allow broadcast emails from people clicking the buttons on the adult roster page."
  3. Display Name: "Event Emails"; Implemented at: Event.php; Tooltip Text "Check this box to receive emails from organizers of evenst you have signed up for."


# Approach
1. Create a plan where each step executes the steps below on this one. Please scan the codebase and validate that this plan is feasible as-described.
2. Create the files for the page which will load the preferences for each user from the database in User.php and display them as specified.
3. Add the ability to update the user preference in the database from User.php?&edit=1

# Expectations for each feature we Create
1. Every feature will be tested in a unit and/or integration test.
2. You will pause for my review after each change for me to test and potentially iterate with you.
3. true indicates yes, I wish to receive notifications and should display a checked checkbox
4. false indicates no, I do not wish to receive notifications and should display an unchecked checkbox
