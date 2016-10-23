Please run the following command for the configuration for making the work for UserManager and Notification Manager.
a) php composer.phar update
b) php app/console doctrine:schema:update --force

we have put the database name "momosy"

Register link: hostname/register

Login link: hostname/login

REST API
a) For Login: hostname/api/login?reqObj={"username":"","password":""}

OAuth Login:

I have setUp the FOSAuthServer Bundle.

There is following step for auth Login
a) Register the client in server. 
    Currently client is registering on the server by the command
    command: php app/console acme:oauth-server:client:create --redirect-uri="http://clinet.local/" --grant-type="authorization_code" --grant-type="password" --grant-type="refresh_token" --grant-type="token" --grant-type="client_credentials"

b) Client will pass client_id, client_secret, user_password and user_name through the url
     hostname/oauth/v2/token? client_id=5_ebg354gknv48kc88o8oogwokckco0o40sc000cowc8soosw0k&client_secret=5ub5upfxih0k8g44w00ogwc4swog4088o8444sssos8k888o8g&grant_type=client_credentials

c) It will return the response 
 {"access_token":"YTk0YTVjZDY0YWI2ZmE0NjRiODQ4OWIyNjZkNjZlMTdiZGZlNmI3MDNjZGQwYTZkMDNiMjliNDg3NWYwZWI0MQ","expires_in":3600,"token_type":"bearer","scope":"user","refresh_token":"ZDU1MDY1OTc4NGNlNzQ5NWFiYTEzZTE1OGY5MWNjMmViYTBiNmRjOTNlY2ExNzAxNWRmZTM1NjI3ZDkwNDdjNQ"}

d) This access_token will be used in every call to check user login.

Example: 
REST login url
hostname/api/login?reqObj={"username":"sk","password":"123456"}&access_token=MTNmYWY3MzUwODI1Y2Y0ZWU0ODVjZGEyZWY0NjMzMDcxMjYzYTdhZmM5MTIzYWE5MDJjZWJjYTAzYzIwZmZmZQ
access_token will require  in the every request url