# Debugging

Want to validate that the setup is working as expected? Or not sure which data is sent to Facebook? There are different ways to see what is happening behind the scenes.


## Test Events

The first place to look when validating Events is on the data source overview in [Facebook Events Manager](https://facebook.com/events_manager). But Facebook does not show the Events in real-time on the overview tab, which makes it hard to validate the installation.

Therefore the Test Events tab was implemented. Copy the Test Event Code to the Plugin Settings page and see the Events in real-time.

![Screenshot](../assets/facebook-events-manager-test-code.jpg)

::: warning
Do not forget to remove the Test Event Code of the Plugin Settings afterward.
:::


## Log

When nothing is shown in the Test Events tab it means Facebook isn't processing the Events. The problem can have various causes:

- The Pixel ID or Access Token aren't configured in the Plugin Settings.
- Facebook doesn't have enough user info. When the Event is completely anonymous it will not be processed by Facebook. Make sure that the email address is known (e.g. the user is logged in or doing a purchase) or that the user clicked a link in Facebook to your website.
- The Server could not reach Facebook.

To see if the Plugin sends an Event to Facebook the logs can be checked. The preferred Craft log is used, which by default is in `storage/logs/web.log`. Search for `https://graph.facebook.com` in the log file, the curl variant of the request should be logged.

The Curl request contains the event name, all data, time, and access token. For security reasons, some data is hashed (as required by Facebook) and only the first five characters of the Access Token are logged. The Curl request can also be used to manually do the request via a terminal (do not forget to replace the filtered Access Token).
