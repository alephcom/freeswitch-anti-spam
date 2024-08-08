api = freeswitch.API();
caller=session:getVariable('caller');
callee=session:getVariable('callee');
uuid = session:getVariable("UUID");
freeswitch.consoleLog("info","Caller is : "..caller);
freeswitch.consoleLog("info","Callee is : "..callee);
local Timestamp = os.time();
local weekday = os.date("%A");
freeswitch.consoleLog("info","week date   is: "..weekday);
if weekday == "Sunday" then
weekday = 1;
mainmenu='/home/spamvoice/press1.mp3';
elseif weekday == "Monday" then
weekday = 2;
mainmenu='/home/spamvoice/press2.mp3';
elseif weekday == "Tuesday" then
weekday =3;
mainmenu='/home/spamvoice/press3.mp3';
elseif weekday == "Wednesday" then
weekday = 4;
mainmenu='/home/spamvoice/press4.mp3';
elseif weekday == "Thursday" then
weekday = 5;
mainmenu='/home/spamvoice/press5.mp3';
elseif weekday == "Friday" then
weekday = 6;
mainmenu='/home/spamvoice/press6.mp3';
elseif weekday == "Saturday" then
weekday = 7;
mainmenu='/home/spamvoice/press7.mp3';
end

--command = "/usr/bin/php   /usr/local/freeswitch/scripts/spamchek.php     "..tostring(caller).."    "..tostring(uuid);
command="php /usr/local/freeswitch/scripts/spamcheck.php      " ..tostring(caller).."    "..tostring(uuid);
local handle = io.popen("php /usr/local/freeswitch/scripts/spamcheck.php      " ..tostring(caller).."    "..tostring(uuid));
freeswitch.consoleLog("info","Command is: "..tostring(command));
local result = handle:read("*a")
handle:close()
--session:execute("system",command );

--query="select SYear,SMonth,SDate from wcallerid where callerid  like   '%"..caller.."' order by id desc";
--freeswitch.consoleLog("info","File query is: "..query);
--session:setVariable("numrow","0");
--local result = dbh:query(query,set_session_variables);

local numrow = tonumber(session:getVariable("Exits"));


if numrow ~= nil then freeswitch.consoleLog("info","Numrow is: "..tostring(numrow));end

if numrow == 1 then
SYear = session:getVariable("Syear");
SMonth = session:getVariable("SMonth");
SDate = session:getVariable("SDate");

freeswitch.consoleLog("info","Year is: "..tostring(SYear));
freeswitch.consoleLog("info","Month is: "..tostring(SMonth));
freeswitch.consoleLog("info","Date is: "..tostring(SDate));
local time = os.time or _G.time
Year=os.date("%Y");
Month=os.date("%m");
Date=os.date("%d");

local date1 = time{ year = SYear, month = SMonth, day = SDate , hour = 0, min = 0, sec = 0 }
local date2 = time{ year = Year, month = Month, day = Date, hour = 0, min = 0, sec = 0 }
daydifference=tonumber(math.floor(math.abs(date2 - date1) / (3600 * 24)));
freeswitch.consoleLog("info","Day Difference is: "..tostring(daydifference));


if daydifference > 10 then
--query="delete from wcallerid where callerid='"..tostring(caller).."'";
--freeswitch.consoleLog("info","File query is: "..query);
--dbh:query(query);
session:execute("lua","revaliadte.lua");
end
else
local min = 1;
local max = 1;
local timeoutsec=2000;
local hashterminator="#";
local ivrmenupromptfilepath=mainmenu;
local ivrmenuinvalidfilepath='/home/spamvoice/noinput.mp3';
local terminatordigit=weekday;
digittimeout =6000;
local tries = 3;
local command = min..' , '..max..' , '..tries..' , '..timeoutsec..' , '..hashterminator..' , '..ivrmenupromptfilepath..' , '..ivrmenuinvalidfilepath..' , '.."'"..terminatordigit.."',"..digittimeout;
freeswitch.consoleLog("notice", "IVR Time Out Destination ID : " ..command.. "\n");
dtmf = session:playAndGetDigits(min, max, tries, timeoutsec, hashterminator, ivrmenupromptfilepath , ivrmenuinvalidfilepath,terminatordigit , digittimeout )
if dtmf ~= nil then freeswitch.consoleLog("notice", "DTMF : " ..dtmf.. "\n"); end
if dtmf ==  '1' or dtmf == '2' or dtmf == '3' or dtmf == '4' or dtmf == '5' or dtmf == '6' or dtmf == '7'  then
Year=os.date("%Y");
Month=os.date("%m");
Date=os.date("%d");
command="php /usr/local/freeswitch/scripts/calleridin.php      " ..tostring(caller).."    "..tostring(Year).."   "..tostring(Month).."   "..tostring(Date);
local handle = io.popen("php /usr/local/freeswitch/scripts/calleridin.php      " ..tostring(caller).."    "..tostring(Year).."   "..tostring(Month).."   "..tostring(Date));
freeswitch.consoleLog("info","Command is: "..tostring(command));
local result = handle:read("*a")
handle:close()

--local query ="insert into wcallerid(callerid,SYear,SMonth,SDate) values(".."'"..caller.."'"..','.."'"..Year.."'"..','.."'"..Month.."','"..Date.."'"..")"
--freeswitch.consoleLog("notice", "Query : " ..query.. "\n");
--dbh:query(query);
freeswitch.consoleLog("info","OS date  is: "..Timestamp);
end
end
dbh:release();
