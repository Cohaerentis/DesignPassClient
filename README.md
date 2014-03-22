DesignPassClient
================

Desing Pass Client sample

Configuration
---------------

1. Rename 'config-dist.php' to 'config.php'
2. With App credentials you have received, fill 'config.php' file:
   - $key      : App ID
   - $secret   : App Secret
   - $redirect : App oAuth return for 'authorization_code' mode
 
   
Samples
---------

There are several sample to execute in console (CLI). You should have to make these file executable:
- authenticate.php
- coursetypes.php
- courses.php

To do this, you can execute this command:

```
$> chmod +x authenticate.php
```

1. authenticate.php : Authentication, just get an accesstoken
2. coursetypes.php : Get list of course types
3. courses.php : Get list of courses
