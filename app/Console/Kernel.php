<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\Inspire::class,
        Commands\RetweetTrending::class,
        Commands\TweetInspire::class,
        Commands\StatusUpdate::class,
        Commands\FollowUsers::class,
        Commands\UnfollowUsers::class,
        Commands\PurgeUsers::class,
        Commands\PurgeUsers::class,
        Commands\GetSuggested::class,
        Commands\SavePopularTweets::class,
        Commands\UpdateBotInformation::class,
        Commands\GetQOTD::class,
        Commands\GetInspiration::class,
        Commands\SearchPhoneme::class,
        Commands\SearchLastWord::class,
        Commands\GeneratePoem::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /*
         * Poem Maker tasks
         */

        $schedule->command('poem:get-inspiration')
                 ->everyMinute();

        $schedule->command('poem:generate')
                 ->twiceDaily(10, 17);

         /*
          * Twitter Bot tasks
          */

        $schedule->command('twitter:follow-users')
                 ->everyTenMinutes();

        $schedule->command('twitter:status-update')
                 ->everyTenMinutes();

        $schedule->command('twitter:save-popular-tweets')
                 ->twiceDaily(1, 13);

        $schedule->command('twitter:unfollow-users')
                 ->hourly();

        $schedule->command('twitter:retweet-trending')
                 ->weekdays()->at('14:00');

        $schedule->command('twitter:tweet-inspire')
                 ->weekdays()->at('10:00');

        $schedule->command('twitter:purge-users')
                 ->daily();

        $schedule->command('twitter:get-suggested')
                 ->daily();

        $schedule->command('twitter:update-bot-information')
                 ->daily();

        $schedule->command('quote:get-qotd')
                 ->daily();
    }
}
