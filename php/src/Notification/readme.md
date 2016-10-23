Notification manager link for Email: 

host-name/api/notification?reqObj={"notification_type":"Email","message_type":"Friend request","from":"abhishek.gupta@daffodilsw.com","to":"yogendra.singh@daffodilsw.com","mail_subject":"New Message","message":"Coming on Iphone device","sender_userid":2,"receiver_userid":3,"email_flag":1,"sms_flag":1,"push_type":"iphone","device_token":"APA91bE_sABk6RMMLC49jufp3v8FKwmDWUvZ8xX2tzaV3wGhctg5F52raajsPDhIB8Gr0vm0Iur1V8o4oeoEnl661A4lT1IoRTprSO6Ji1JJIh-VqNYWkMcD0Sd4DVjTd3JVoIXDGDxePb5GfWyoKTnRRUOBh2cALw","msg_id":1}&access_token=NWED

Notification manager link for Push notification:

Iphone
host-name/api/notification?reqObj={"notification_type":"PushNotification","message_type":"Friend request","from":"abhishek.gupta@daffodilsw.com","to":"yogendra.singh@daffodilsw.com","mail_subject":"New Message","message":"Coming on Iphone device","sender_userid":2,"receiver_userid":3,"email_flag":1,"sms_flag":1,"push_type":"iphone","device_token":"APA91bE_sABk6RMMLC49jufp3v8FKwmDWUvZ8xX2tzaV3wGhctg5F52raajsPDhIB8Gr0vm0Iur1V8o4oeoEnl661A4lT1IoRTprSO6Ji1JJIh-VqNYWkMcD0Sd4DVjTd3JVoIXDGDxePb5GfWyoKTnRRUOBh2cALw","msg_id":1}&access_token=NWED

Andriod
host-name/api/notification?reqObj={"notification_type":"PushNotification","message_type":"Friend request","from":"abhishek.gupta@daffodilsw.com","to":"yogendra.singh@daffodilsw.com","mail_subject":"New Message","message":"Coming on Android device","sender_userid":2,"receiver_userid":3,"email_flag":1,"sms_flag":1,"push_type":"android","device_token":"APA91bE_sABk6RMMLC49jufp3v8FKwmDWUvZ8xX2tzaV3wGhctg5F52raajsPDhIB8Gr0vm0Iur1V8o4oeoEnl661A4lT1IoRTprSO6Ji1JJIh-VqNYWkMcD0Sd4DVjTd3JVoIXDGDxePb5GfWyoKTnRRUOBh2cALw","msg_id":1}&access_token=NWED

Email:
1. Change the from/to for receive the email.
location: @Notification\NotificationBundle\Controller\Manager.php

2. Android Notification:
a) Android config file
@NManagerNotificationBundle/DependencyInjection/AndroidConfig.php

b) change this(androidApiKey) according to the api key will be provided by Andriod team.

3. Iphone config file
@NManagerNotificationBundle/DependencyInjection/IphoneConfig.php

a) change this(gateway_url) parameter according to the enviornment(sandbox/production)
b) upload the pem file for iphone to @NManagerNotificationBundle/DependencyInjection/ location.
c) pem_file, pem file should be same according to @NManagerNotificationBundle/DependencyInjection/Certificates.pem

4. Mongo DB configuration
a) ext-mongo

php composer.phar require ext-mongo
1.4.5

this above version will according to php mongo extension.

b) mongodb-odm
php composer.phar require doctrine/mongodb-odm
1.0.*@alpha

c) mongodb-odm-bundle
php composer.phar require doctrine/mongodb-odm-bundle
3.0.*@alpha