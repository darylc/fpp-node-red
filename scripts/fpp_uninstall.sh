#!/bin/bash
. /opt/fpp/scripts/common
# Fetch all the settings
MQTTHost=$(getSetting MQTTHost)
MQTTStatusFrequency=$(getSetting MQTTStatusFrequency)
MQTTPrefix=$(getSetting MQTTPrefix)

if [[ "$MQTTHost" == "127.0.0.1" ]] && \
   [[ "$MQTTStatusFrequency" == "1" ]] && \
   [[ "$MQTTPrefix" == "node-red" ]]; then
   setSetting MQTTHost ""
   setSetting MQTTStatusFrequency 0
   setSetting MQTTPrefix ""
   setSetting restartFlag 1
else
   echo "MQTT config has been changed, not touching during uninstall"
fi

sudo systemctl stop mosquitto.service
sudo systemctl disable mosquitto.service
sudo systemctl stop node-red.service
sudo systemctl disable node-red.service
sudo rm /etc/systemd/system/node-red.service
sudo systemctl daemon-reload
sudo apt-get -y remove npm

