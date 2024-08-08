

	DBUSER = 'root'
	DBPASSWORD = '48dgEfYTMxD9ZpawYFWkKMM'
	DBHOST = 'localhost'
	DSN="freecall"
       dbh = freeswitch.Dbh("odbc://"..DSN..":"..DBUSER..":"..DBPASSWORD);


if dbh:connected() == false then
freeswitch.consoleLog("notice", "cannot connect to database" .. dsn .. "\n")
return
end








function set_session_variables(row)
	-- Sets session variables with the same names as the columns from the database
	session:setVariable("numrow", "1");

	for key, val in pairs(row) do
	if session then
session:setVariable(key, val)

	end
	freeswitch.consoleLog("DEBUG", string.format("set(%s=%s)\n", key, val))
	end
	end





