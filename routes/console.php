<?php

use Illuminate\Support\Facades\Schedule;

// Tarefas agendadas. No cPanel basta um cron por minuto:
//   * * * * * cd /home/UTILIZADOR/crevelhos && php artisan schedule:run >> /dev/null 2>&1

Schedule::command('crevelhos:copia-seguranca')->dailyAt('03:30');
