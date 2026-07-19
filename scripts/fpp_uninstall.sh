#!/bin/bash
set -e
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

systemctl stop mosquitto.service
systemctl disable mosquitto.service
systemctl stop node-red.service
systemctl disable node-red.service
rm /etc/systemd/system/node-red.service
systemctl daemon-reload
apt-get -y remove npm
apt-get -u autoremove


