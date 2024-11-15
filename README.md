# freeswitch-anti-spam
Robo Call Blocker for FreeSWITCH

This sample dialplan will answer the call and then ask the web service how to handle the call.  The web service will play a specific file depending on day of the week.  If the user presses the correct number, it will add them to a cache and the caller will be allowed for 90(default) days.  

```
   <!-- Testing Toll Free DID -->
   <extension name="public_did">
     <condition field="destination_number" expression="^123456789$" require-nested="true">
         <action application="answer" />
         <action application="httapi" data="{http://127.0.0.1/robocall/call}"/>
         <action application="bridge" data="sofia/external/123456789@internalip:5060"/>
     </condition>
   </extension>
```

Recordings come from Amazon Polly -> Long Form -> English, US -> Ruth, Female.

You can run ``php artisan serve`` to run a testing instance of the webserver.  This could be run directly on your FreeSWITCH instance and then the dialplan could be pointed to localhost:8000.

Some configuration is found in the ``.env`` file.  Details on the rules per DID can be found in ``storage/app/dids.json``