<?php

namespace App\Classes;

use App\Models\Alexandrine;

/**
*  Some methods to generate poems (with tweets ?)
*/
class PoemMaker
{
    const THANK_MSG = 'Merci pour ton tweet, c\'est un bel alexandrin, je vais m\'en servir pour mon poème. Plus qu\' à trouver des rimes !';

    // Poem language
    private $language;

    public function __construct($language)
    {
        // Set language
        $this->language = $language;

        // Configure the bot
        $botConfig = $this->getBotConfig();
        \Twitter::reconfig($botConfig);

        // Set cache folder
        \Syllable::setCacheDir(storage_path().'/framework/cache');
    }

    /*
     * Search tweets from a place to find inspiration
     */
    public function getInspiration()
    {
        $params = [
            'q'           => 'place:09f6a7707f18e0b1', // Hardcoding Paris, FR for now
            'lang'        => $this->language,
            'result_type' => 'recent',
            'count'       => 100,
            'format'      => 'array'
        ];

        try {
            \Log::info('// Poem Maker : getting inspiration');
            $inspiration = \Twitter::getSearch($params);

            // Looking for an alexandrine !
            $found = [];
            foreach ($inspiration['statuses'] as $key => $tweet) {

                // Not taking tweets with mentions or links or with numbers inside
                if (false === strpos($tweet['text'], '@') &&
                    false === strpos($tweet['text'], 'http') &&
                    false === preg_match('~[0-9]+~', $tweet['text'])) {
                    if ($this->isAlexandrine($tweet['text'])) {

                        // Getting last phoneme for rhyme matching (i remember you we are here to build a poem)
                        $lastPhoneme = $this->getLastPhoneme($tweet['text']);

                        // If we can't find the phoneme, skip it
                        if (empty($lastPhoneme)) {
                            continue;
                        }

                        $data = [
                            'tweet_id'          => $tweet['id'],
                            'user_id'           => $tweet['user']['id'],
                            'text'              => $tweet['text'],
                            'lang'              => $tweet['lang'],
                            'screen_name'       => $tweet['user']['screen_name'],
                            'profile_image_url' => $tweet['user']['profile_image_url'],
                            'phoneme'           => $lastPhoneme
                        ];

                        // Store in DB
                        $alexandrine = Alexandrine::updateOrCreate(['tweet_id' => $tweet['id']], $data);

                        // Let's thank the author of this ! Or not, i'm spamming
                        //$this->thankSource($tweet);

                        $found[] = $data;
                    }
                }
            }

            \Log::info('// Found ' . count($found) . ' alexandrine(s)');

            return $found;
        } catch (\Exception $e) {
            \Log::error('// Can\'t get inspiration : ' . $e->getMessage());
        }
    }

    /*
     * Retrieve the last phoneme of the alexandrine (for rhymes matching)
     * @param text text to analyse
     * @return text the phoneme
     */
    public function getLastPhoneme($text)
    {
        $lastPhoneme = '';

        // Getting last word
        $words = mb_split('[^\'[:alpha:]]+', $this->removeEmoji($text));
        $words = array_reverse($words);
        foreach ($words as $w) {
            // Don't get the word if it's empty (sometimes it happens) or an emoji
            if (mb_strlen($w)) {
                $lastWord = $w;
                break;
            }
        }

        // Getting last syllable
        $syllable = new \Syllable('fr');
        $syllables = $syllable->splitWord($lastWord);
        $lastSyllable = end($syllables);

        // Finally, getting the phonem
        $lastPhoneme = SoundexFr::phonetique($lastSyllable);

        return $lastPhoneme;
    }

    /*
     * Send a tweet to the writer of the alexandrin and like the tweet
     * $param array the original tweet
     * @return boolean
     */
    public function thankSource($tweet)
    {
        try {
            // Like the tweet
            \Twitter::postFavorite(['id' => $tweet['id_str']]);

            // Send the message
            $params = [
                'status'                => html_entity_decode('@' . $tweet['user']['screen_name'] . ' ' . self::THANK_MSG),
                'in_reply_to_status_id' => $tweet['id_str'],
                'format'                => 'array'
            ];
            \Twitter::postTweet($params);
        } catch (\Exception $e) {
            \Log::error('// Can\'t thank the source : ' . $e->getMessage());
        }
    }

    /*
     * Check if a string is an alexandrine
     * @param string $string
     * @php return boolean
     */
    private function isAlexandrine($text)
    {
        $syllable = new \Syllable($this->language);

        $histogram = $syllable->histogramText($text);
        $syllabesCount = $this->sumSyllabes($histogram);
        return (12 == $syllabesCount) ? true : false;
    }

    /*
     * Calculate the total number of syllabes from a text histogram
     * @param array $histogram array from \Syllable->histogramText
     * @return int
     */
    private function sumSyllabes($histogram)
    {
        $sum = 0;
        foreach ($histogram as $syllable_count => $number) {
            $sum += $syllable_count * $number;
        }

        return $sum;
    }

    /*
     * Twitter app configuration
     */
    private function getBotConfig()
    {
        $botConfig = [
            'consumer_key'    => env('TWITTER_CONSUMER_KEY', ''),
            'consumer_secret' => env('TWITTER_CONSUMER_SECRET', ''),
            'token'           => env('TWITTER_ACCESS_TOKEN', ''),
            'secret'          => env('TWITTER_ACCESS_TOKEN_SECRET', '')
        ];

        return $botConfig;
    }

    /*
     * Remove emoji
     */
    private function removeEmoji($text)
    {
        return preg_replace('/([0-9#][\x{20E3}])|[\x{00ae}\x{00a9}\x{203C}\x{2047}\x{2048}\x{2049}\x{3030}\x{303D}\x{2139}\x{2122}\x{3297}\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $text);
    }
}
