#!/bin/bash
set -e
#set -x

binDir=$(dirname "$0")
cd $binDir

if [ $# -ne 1 ]
then
  echo "usage: $0 start|stop|wpStart|wpStop|synapseStart|synapseStop"
  exit 1
fi

if [ ! -f dirs.env ]
then
  echo "dirs.env not found"
  exit 2
fi

source dirs.env
wpPluginDir=$wpDir/wp-content/plugins
basedir=$(dirname $(pwd))
pid=wp.pid
pluginName=wp2matrix

wpStart() {
  cd $wpPluginDir
  ln -s $basedir $pluginName

  cd $wpDir
  php -S localhost:8080 &
  if [ $? -ne 0 ]
  then
    echo "$0: WordPress not started"
    exit $?
  fi
  echo $! > $pid
}

wpStop() {
  cd $wpDir
  if [ -f $pid ]
  then
    pkill --pidfile $pid
    rm $pid
  else
    echo "$pid not found. Can not stop wordpress"
  fi

  cd $wpPluginDir
  if [ -h $pluginName ]
  then
    rm $pluginName
  fi
}

synapseStart() {
  cd $synapseDir
  source env/bin/activate
  synctl start
}

synapseStop() {
  cd $synapseDir
  source env/bin/activate
  synctl stop
}

start() {
  wpStart
  synapseStart
}

stop() {
  wpStop
  synapseStop
}

$1
