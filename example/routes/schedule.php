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
*/
use \Scarlets\Library\Schedule;

Schedule::cron('* * * * * *', function(){
	
});

// Every hour
Schedule::hourly(function(){
	
});

// Every hour at 30m and 15s
Schedule::hourly('30:15', function(){
	
});