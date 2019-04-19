<?php

/*
|--------------------------------------------------------------------------
| Task Scheduler
|--------------------------------------------------------------------------
|
| Here you can automate your task on your machine. But you should turn on
| Scarlets Scheduler by using 'php scarlets schedule start'. It would
| create new process for running your task, and it would check this file
| for new update every minute.
| But if you're on shared hosting, you can use EasyCron on the internet
| or register command console and create new cron from webhost's
| control panelto execute that command
|
| You need to turn on this feature by install task scheduler (minimal 5 minutes)
| $ scarlets install 5
|
*/
use \Scarlets\Library\Schedule;

// https://crontab.guru/
Schedule::cron('* * * * *', function(){});

// Every hour
Schedule::hourly(function(){
	
});

// Every 2 week
Schedule::weekly(2, function(){
	
});