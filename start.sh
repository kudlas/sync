#!/bin/bash

printenv | sed 's/^\(.*\)\=\(.*\)$/export \1\="\2"/g' > /root/project_env.env
cron
