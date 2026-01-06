<?php
/**
 * Notification Preference Types
 *
 * This file defines all available notification preferences for the system.
 * Each preference has:
 * - key: 4-letter database key for JSON storage
 * - display_name: User-friendly display name
 * - tooltip: Explanatory text shown to users
 */

$notification_types = array(
    array(
        'key' => 'scsu',  // SCout SignUp
        'display_name' => 'Scout Signup Emails',
        'tooltip' => 'Check this box to receive an email notification when your scout signs up for an event.'
    ),
    array(
        'key' => 'rost',  // ROSTer
        'display_name' => 'Roster Emails',
        'tooltip' => 'Check this box to allow broadcast emails from people clicking the buttons on the adult roster page.'
    ),
    array(
        'key' => 'evnt',  // EVeNT
        'display_name' => 'Event Emails',
        'tooltip' => 'Check this box to receive emails from organizers of events you have signed up for.'
    )
);

?>
