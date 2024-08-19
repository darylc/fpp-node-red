<h2>FPP Node-RED Help</h2>
A handy command for checking if the MQTT broker is seeing data from FPP:
mosquitto_sub -v -t node-red/#
<br><br>
If you don't see anything it's likley that your MQTT configuration inside FPP is
 incorrect.  This plugin requires it to be set as:
<br>
   <ul>
        <li><strong>Broker Host:</strong> 127.0.0.1</li>
        <li><strong>Broker Port:</strong> 1883</li>
        <li><strong>Client ID:</strong> Leave Blank</li>
        <li><strong>Topic Prefix:</strong> node-red</li>
        <li><strong>Username:</strong> Leave Blank</li>
        <li><strong>Password:</strong> Leave Blank</li>
        <li><strong>CA File:</strong> Leave Blank</li>
        <li><strong>Publish FPPD Status Frequency:</strong> 1 or more</li>
    </ul>
