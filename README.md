pushover-queue
==============

Simple script that will queue up notifications in a MySQL database and then send them out to Pushover every 10 seconds. Comes with support for Site 24/7 Actions and SendGrid Inbound Parse API.

Cronjob
-------

```
* * * * * /path/to/sendNotifications.sh
```