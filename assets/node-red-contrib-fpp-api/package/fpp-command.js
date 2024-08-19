const request = require('request');
const fs = require('fs');
const path = require('path');

module.exports = function(RED) {
    function FppCommandNode(config) {
        RED.nodes.createNode(this, config);
        var node = this;

        // Load the JSON configuration file
        let commandsConfig;
        try {
            const jsonFilePath = path.join(__dirname, 'fpp-command.json');
            commandsConfig = JSON.parse(fs.readFileSync(jsonFilePath, 'utf8'));
            node.log("Loaded commands configuration successfully.");
        } catch (err) {
            node.error("Failed to load commands configuration file: " + err.message);
            return;
        }

        node.on('input', function(msg) {
            node.log("Input event received. Processing the node...");
            node.log(`Node configuration: ${JSON.stringify(config)}`);
            node.log(`Dynamic fields: ${JSON.stringify(config.dynamicFields)}`);

            // Build the API request payload
            let payload = {};

            const commandConfig = commandsConfig.commands[config.command];
            if (commandConfig) {
                node.log(`Processing command: ${config.command}`);
                commandConfig.fields.forEach(field => {
                    // Retrieve value from dynamicFields and parse it as a number if necessary
                    let fieldValue = config.dynamicFields ? config.dynamicFields[field.name] : config[field.name];
                    
                    node.log(`Original value from dynamicFields: ${config.dynamicFields[field.name]}, Retrieved fieldValue: ${fieldValue}`);
                    
                    // Parse the value as a number if it's meant to be numeric
                    if (field.type === 'int' && fieldValue !== undefined && fieldValue !== null) {
                        fieldValue = parseInt(fieldValue, 10);
                    }

                    // Ensure the dynamic field value is used, and only use default if the value is null or undefined
                    if (fieldValue === undefined || fieldValue === null) {
                        fieldValue = field.defaultValue;
                    }

                    payload[field.name] = fieldValue;
                    node.log(`Field: ${field.name}, Value: ${fieldValue}`);
                });
            } else {
                node.error("Unknown command: " + config.command);
                return;
            }

            // Construct URL
            const command = encodeURIComponent(config.command);
            const args = [];
            for (const [key, value] of Object.entries(payload)) {
                if (key !== 'command' && value !== undefined && value !== null) {
                    args.push(encodeURIComponent(value));
                }
            }
            const apiBaseUrl = 'http://127.0.0.1';
            const apiUrl = `${apiBaseUrl}/api/command/${command}/${args.join('/')}`;

            // Log the API request details
            node.log(`Making API request to: ${apiUrl}`);

            // Make the HTTP request
            request(apiUrl, function (error, response, body) {
                if (error) {
                    node.error(`API request failed: ${error.message}`, msg);
                } else {
                    node.log(`API response status: ${response.statusCode}`);
                    node.log(`API response body: ${body}`);
                    // Attach the response to the message payload if needed
                    msg.payload.response = body;
                }
                // Send the message along
                node.send(msg);
            });
        });
    }

    RED.nodes.registerType("fpp-command", FppCommandNode);
}

