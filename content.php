<?php
require_once '/opt/fpp/www/common.php';

// Function to execute system commands with sudo
function executeSystemCommand($command) {
    $output = [];
    $return_var = 0;
    exec("sudo $command 2>&1", $output, $return_var);
    return ['output' => $output, 'status' => $return_var];
}

// Function to get the service status
function getServiceStatus($service) {
    $result = executeSystemCommand("systemctl is-active $service");
    $status = trim(implode("\n", $result['output']));
    return ($status === 'active') ? 'active' : 'inactive';
}

// Function to get the number of connected clients to Mosquitto using ss
function getMosquittoClients() {
    $result = executeSystemCommand("ss -tan state established '( sport = :1883 )' | grep -c '1883'");

    if ($result['status'] === 0 && !empty($result['output'])) {
        return intval(trim($result['output'][0]));
    } else {
        return 0;
    }
}

// Handle AJAX requests for service control and status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service']) && isset($_POST['action'])) {
    $service = $_POST['service'];
    $action = $_POST['action'];

    if ($action === 'wipe-node-red') {
        executeSystemCommand("cp /home/fpp/media/plugins/fpp-node-red/assets/flows.json /home/fpp/media/plugindata/fpp-node-red/flows.json");
        executeSystemCommand("systemctl restart node-red");
    } elseif ($action === 'configure-mqtt') {
        executeSystemCommand("/home/fpp/media/plugins/fpp-node-red/scripts/configure-mqtt.sh");
    } elseif ($action === 'enable') {
        executeSystemCommand("systemctl enable $service");
        executeSystemCommand("systemctl start $service");
    } elseif ($action === 'disable') {
        executeSystemCommand("systemctl stop $service");
        executeSystemCommand("systemctl disable $service");
    } elseif ($action === 'restart') {
        executeSystemCommand("systemctl restart $service");
    }

    echo getServiceStatus($service);
    exit;
}

// Fetch the statuses of services directly in PHP
$mosquittoStatus = getServiceStatus('mosquitto');
$nodeRedStatus = getServiceStatus('node-red');

// Fetch the number of clients connected to Mosquitto
$mosquittoClients = ($mosquittoStatus === 'active') ? getMosquittoClients() : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Manager and Syslog Viewer</title>
    <style>
        .log-container {
            width: 100%;
            height: 400px; /* Adjust height as needed */
            overflow-y: scroll;
            background-color: #f4f4f4;
            padding: 10px;
            border: 1px solid #ccc;
            font-family: monospace;
            white-space: pre-wrap; /* Preserves whitespace formatting */
            font-size: 12px; /* Smaller font size */
        }
        .service-container {
            margin: 20px 0;
        }
        .service-buttons {
            display: flex;
            gap: 10px;
        }
        .service-status {
            margin-bottom: 10px;
            padding: 5px;
            font-family: monospace;
        }
        .status-active {
            color: green;
            font-weight: bold;
        }
        .status-inactive {
            color: red;
            font-weight: bold;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Configuration</h1>
    Out of the box, the FPP Node-RED plugin requires FPP MQTT settings as follows:<br>
    <i>If your FPP MQTT configuration was unconfigured, the plugin as set these for you.</i>
    <br>
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
    If you already had MQTT configured, you can either:
    <ul>
        <li>Press the 'Configure FPP MQTT to Node-RED Defaults' button below to replace your existing configuration.</li>
        <li>Modify the default FPP Node-RED flows to talk to your existing MQTT broker, etc.</li>
    </ul>

    <a id="dynamic-link" href="#" target="_blank"><h1>FPP Node-RED UI</h1></a>
    <a id="dynamic-link2" href="#" target="_blank"><h1>FPP Node-RED FPP Variable Dashboard</h1></a>

    <script>
        // Get the current hostname
        const hostname = window.location.hostname;
        // Construct the base URL with port 1880
        const baseUrl = `http://${hostname}:1880`;
        document.getElementById('dynamic-link').href = baseUrl;
        document.getElementById('dynamic-link2').href = baseUrl + "/ui";
        document.getElementById('dynamic-link3').href = baseUrl + "/flows-default.json";
        document.getElementById('dynamic-link4').href = baseUrl + "/flows.json";
    </script>

    <div class="service-container">
        <h2>Configuration</h2>
        <a id="dynamic-link4" href="#" target="_blank" class="button">Download your Node-RED flows configuration</a>
        <a id="dynamic-link3" href="#" target="_blank" class="button">Download default Node-RED flows configuration</a>
        <br>
        <br>
        <div class="service-buttons">
            <button onclick="controlService('configuration', 'configure-mqtt')">Configure FPP MQTT to Node-Red defaults</button>
            <button onclick="controlService('configuration', 'wipe-node-red')">Wipe Node-Red configuration</button>
        </div>
        <br>

        <h2>Mosquitto</h2>
        <div id="mosquitto-status" class="service-status">
            Mosquitto MQTT Broker Status: <span class="status-<?php echo $mosquittoStatus; ?>"><?php echo ucfirst($mosquittoStatus); ?></span>
            <?php if ($mosquittoStatus === 'active'): ?>
                , Clients Connected: <?php echo $mosquittoClients; ?>
            <?php endif; ?>
        </div>
        <div class="service-buttons">
            <button onclick="controlService('mosquitto', 'restart')">Restart Mosquitto</button>
            <button onclick="controlService('mosquitto', 'enable')">Enable Mosquitto</button>
            <button onclick="controlService('mosquitto', 'disable')">Disable Mosquitto</button>
        </div>
    </div>

    <div class="service-container">
        <h2>Node-RED</h2>
        <div id="node-red-status" class="service-status">
            Status: <span class="status-<?php echo $nodeRedStatus; ?>"><?php echo ucfirst($nodeRedStatus); ?></span>
        </div>
        <div class="service-buttons">
            <button onclick="controlService('node-red', 'restart')">Restart Node-RED</button>
            <button onclick="controlService('node-red', 'enable')">Enable Node-RED</button>
            <button onclick="controlService('node-red', 'disable')">Disable Node-RED</button>
        </div>
    </div>

    <h1>Node-RED Log Viewer</h1>
    Shows last 100 lines, for more see /var/log/syslog
    <div class="log-container">
        <?php
        $lines = [];
        $pattern = '/env\[\d+\]/';
        $stripPattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+\+\d{2}:\d{2} fpp env\[\d+\]:\s*/';

        // Run the sudo command to read the syslog file
        $output = [];
        $command = 'sudo cat /var/log/syslog';
        exec($command, $output, $return_var);

        if ($return_var === 0) {
            // Filter and strip the lines based on the pattern
            foreach (array_reverse($output) as $line) {
                if (preg_match($pattern, $line)) {
                    // Strip the timestamp and prefix
                    $line = preg_replace($stripPattern, '', $line);
                    $lines[] = $line;
                    if (count($lines) >= 100) {
                        break;
                    }
                }
            }

            // Output the filtered and stripped lines
            echo htmlspecialchars(implode("\n", $lines));
        } else {
            echo "Failed to read syslog. Please check the sudo configuration.";
        }
        ?>
    </div>

    <script>
    function controlService(service, action) {
        if (action === 'wipe-node-red') {
            // Trigger the modal dialog
            DoModalDialog({
                id: "wipeNodeRedConfirm",
                title: "Wipe Node-RED Configuration",
                body: $("#dialog-confirm"),
                class: 'modal-lg',
                backdrop: true,
                keyboard: true,
                buttons: {
                    "Wipe Node-Red Config": {
                        class: 'btn-danger',
                        click: function () {
                            CloseModalDialog("wipeNodeRedConfirm");
                            // Perform the AJAX call to wipe Node-RED configuration
                            $.post("", { action: action, service: service }, function() {
                                location.reload();
                            });
                        }
                    },
                    "Cancel": {
                        click: function () {
                            CloseModalDialog("wipeNodeRedConfirm");
                        }
                    }
                }
            });
        } else {
            // Handle other actions without confirmation
            $.post("", { action: action, service: service }, function() {
                location.reload();
            });
        }
    }
    </script>
                    <div id="dialog-confirm" class="hidden">
                    <p><span class="ui-icon ui-icon-alert" style="flat:left; margin: 0 7px 20px 0;"></span>Wiping Node-RED configuration is an extreme action<br> It should only be done if you are willing to lose all of your flows.  This action will revert your Node-RED flows to the ones that come with this plugin before any changes you may have made.
                        Do you wish to proceed?</p>
                </div>

</body>
</html>

