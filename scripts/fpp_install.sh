#!/bin/bash

echo "******"
echo "******"
echo "The install will take some time, please stay on this page until you see Done"
echo "******"
echo "******"

if ! grep -q "^listener 1883" /etc/mosquitto/mosquitto.conf; then
    sudo sh -c 'echo "listener 1883" >> /etc/mosquitto/mosquitto.conf'
fi

if ! grep -q "^allow_anonymous true" /etc/mosquitto/mosquitto.conf; then
    sudo sh -c 'echo "allow_anonymous true" >> /etc/mosquitto/mosquitto.conf'
fi
sudo systemctl enable mosquitto.service
sudo systemctl start mosquitto.service

sudo apt-get -y install npm
sudo npm install -g node-red

PLUGINDIR="/home/fpp/media/plugins/fpp-node-red"
DATADIR="/home/fpp/media/plugindata/fpp-node-red"
if [ ! -d "$DATADIR" ]; then
  mkdir -p "$DATADIR"
fi
cd $DATADIR
echo ""
echo "Please wait"
echo ""
npm install node-red-dashboard

cd $PLUGINDIR/assets/node-red-contrib-fpp-api
tar zcvf node-red-contrib-fpp-api-1.0.1.tgz package
sleep 1
cd $DATADIR
npm install $PLUGINDIR/assets/node-red-contrib-fpp-api/node-red-contrib-fpp-api-1.0.1.tgz

if [ ! -e "$DATADIR/flows.json" ]; then
  echo "copying flows.json"
  cp $PLUGINDIR/assets/flows.json $DATADIR/flows.json
fi

mv settings.js settings.js.orig
echo "module.exports = {" > settings.js
echo "    httpStatic: '/home/fpp/media/plugindata/fpp-node-red/node_modules/node-red-contrib-fpp-api'" >>settings.js
echo "}">>settings.js
echo >>settings.js

ln -s $PLUGINDIR/assets/flows.json $DATADIR/node_modules/node-red-contrib-fpp-api/flows-default.json
ln -s $DATADIR/flows.json $DATADIR/node_modules/node-red-contrib-fpp-api/flows.json

sudo chown -R fpp $DATADIR

sudo cp $PLUGINDIR/assets/node-red.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable node-red.service
sudo systemctl start node-red.service
sudo systemctl status node-red.service

. /opt/fpp/scripts/common

# Fetch all the settings
MQTTHost=$(getSetting MQTTHost)
MQTTStatusFrequency=$(getSetting MQTTStatusFrequency)
MQTTClientId=$(getSetting MQTTClientId)
MQTTFrequency=$(getSetting MQTTFrequency)
MQTTPortStatusFrequency=$(getSetting MQTTPortStatusFrequency)
MQTTSubscribe=$(getSetting MQTTSubscribe)
MQTTPrefix=$(getSetting MQTTPrefix)
MQTTUsername=$(getSetting MQTTUsername)
MQTTCaFile=$(getSetting MQTTCaFile)
MQTTPassword=$(getSetting MQTTPassword)

if { [ -z "$MQTTHost" ] || [ "$MQTTHost" == "0" ]; } && \
   { [ -z "$MQTTStatusFrequency" ] || [ "$MQTTStatusFrequency" == "0" ]; } && \
   { [ -z "$MQTTClientId" ] || [ "$MQTTClientId" == "0" ]; } && \
   { [ -z "$MQTTFrequency" ] || [ "$MQTTFrequency" == "0" ]; } && \
   { [ -z "$MQTTPortStatusFrequency" ] || [ "$MQTTPortStatusFrequency" == "0" ]; } && \
   [ -z "$MQTTSubscribe" ] && \
   [ -z "$MQTTPrefix" ] && \
   [ -z "$MQTTUsername" ] && \
   [ -z "$MQTTCaFile" ] && \
   [ -z "$MQTTPassword" ]; then
   
    echo "Configuring MQTT"
    /home/fpp/media/plugins/fpp-node-red/scripts/configure-mqtt.sh
    setSetting restartFlag 1
else
    echo "MQTT is already configured for some other purpose"
fi




