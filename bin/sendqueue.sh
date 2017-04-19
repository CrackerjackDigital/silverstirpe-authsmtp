#!/bin/bash
# This script will call the task which sends emails from the AuthSMTP queue via sake.
#
# It wants to change to ~/htdocs first which should be or link to the web root for the website. If not found
# it will complain and fail.
#
# It should also be run as the login user for the web-site admin, not root.
#
# You can call this task from a cron entry e.g to run every 5 minutes as user 'manz':
#
# 5 * * * * sudo su - manz -c "~/htdocs/authsmtp/bin/sendqueue.sh"
#
set -e
if [ "$(id -u)" == "0" ]; then
   echo "This script should be run as the web site user, not root" 1>&2
   exit 1
fi

if [ ! -e ~/htdocs ]; then
	echo "Please link the web root to ~/htdocs"  1>&2
	echo "e.g. ln -s ~/<webroot> ~/htdocs"  1>&2
	exit 2;
fi
cd ~/htdocs
framework/sake "dev/tasks/AuthSMTPQueueTask"