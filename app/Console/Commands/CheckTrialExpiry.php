<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Restaurant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckTrialExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-trial-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check restaurant free trial expirations and trigger email reminders.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting trial expiration check...');

        $now = Carbon::now();
        $restaurants = Restaurant::whereIn('account_status', ['trial', 'active'])->get();

        $expiredCount = 0;
        $reminderCount = 0;

        foreach ($restaurants as $restaurant) {
            if (!$restaurant->trial_ends_at) {
                continue;
            }

            // Expiry Check
            if ($now->greaterThanOrEqualTo($restaurant->trial_ends_at)) {
                if ($restaurant->account_status !== 'expired') {
                    $restaurant->update([
                        'account_status' => 'expired',
                        'plan_status' => 'inactive',
                        'trial_expired_sent' => true,
                    ]);
                    $expiredCount++;

                    Log::info("TRIAL EXPIRED EMAIL: Sent to {$restaurant->email} - Subject: 'Your Free Trial Has Expired'");
                    $this->info("Expired trial for restaurant ID {$restaurant->id} ({$restaurant->name})");
                }
                continue;
            }

            // Reminder Checks
            $daysRemaining = $restaurant->days_remaining;

            if ($daysRemaining <= 1 && !$restaurant->trial_reminder_sent_1) {
                $restaurant->update(['trial_reminder_sent_1' => true]);
                $reminderCount++;
                Log::info("TRIAL REMINDER EMAIL: Sent to {$restaurant->email} - Subject: 'Your Free Trial Ends Tomorrow'");
                $this->info("Sent 1-day reminder to restaurant ID {$restaurant->id}");
            } elseif ($daysRemaining <= 3 && !$restaurant->trial_reminder_sent_3) {
                $restaurant->update(['trial_reminder_sent_3' => true]);
                $reminderCount++;
                Log::info("TRIAL REMINDER EMAIL: Sent to {$restaurant->email} - Subject: 'Your Free Trial Ends in 3 Days'");
                $this->info("Sent 3-day reminder to restaurant ID {$restaurant->id}");
            } elseif ($daysRemaining <= 7 && !$restaurant->trial_reminder_sent_7) {
                $restaurant->update(['trial_reminder_sent_7' => true]);
                $reminderCount++;
                Log::info("TRIAL REMINDER EMAIL: Sent to {$restaurant->email} - Subject: 'Your Free Trial Ends in 7 Days'");
                $this->info("Sent 7-day reminder to restaurant ID {$restaurant->id}");
            }
        }

        $this->info("Completed. Expired: {$expiredCount}, Reminders Logged: {$reminderCount}");

        return Command::SUCCESS;
    }
}
