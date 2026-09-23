<?php

use App\Services\PushNotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('wowsapi:send-vaccine-reminders', function (PushNotificationService $push) {
    $count = $push->sendVaccineReminders();
    $this->info("Pengingat vaksin terkirim: {$count}");
})->purpose('Kirim pengingat vaksin H-7, H-1, dan Hari H');

Schedule::command('wowsapi:send-vaccine-reminders')->dailyAt('07:00');
