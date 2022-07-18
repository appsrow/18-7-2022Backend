<?php

namespace App\Console\Commands;

use App\TwitterData;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use GuzzleHttp\Client;

class TwitterCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitter:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Log::info('test', ['message' => 'test message']);
        // Log::info('test', ['message' => $twitter_record]);

        //model 
        $get_twitter_records =  DB::select(DB::raw('SELECT * FROM twitter_data 
        Where status = 0 LIMIT 10'));


        foreach ($get_twitter_records as $twitter_record) {
            // change status into processing(1)
            DB::select(DB::raw('UPDATE twitter_data 
            SET status = 1 Where id= ' . $twitter_record->id . ''));
            // Log::info('test', ['message' => $twitter_record]);

            // if (!empty($target_user_id)) {
            $forth_uri = 'https://api.twitter.com/2/users/' . $twitter_record->user_id . '/following';
            $stack = HandlerStack::create();
            $middleware = new Oauth1([
                'consumer_key'    => env("TWITTER_CLIENT_ID", null),
                'consumer_secret' => env("TWITTER_CLIENT_SECRET", null),
                'token'           => $twitter_record->oauth_token,
                'token_secret'    => $twitter_record->oauth_token_secret
            ]);
            $stack->push($middleware);
            $client = new Client([
                'handler' => $stack
            ]);
            $rowDatas = '{ "target_user_id": "' . $twitter_record->target_user_id . '" }';
            try {
                $response = $client->request(
                    'POST',
                    $forth_uri,
                    [
                        'headers' =>
                        [
                            'Content-Type' => 'application/json'
                        ],
                        'body' => $rowDatas,
                        'auth' => 'oauth',
                    ]
                );
                $params = (string)$response->getBody();

                Log::info('test', ['message' => $params]);

                TwitterData::whereId($twitter_record->id)->update(['response' => $params]);
                // $params_new_resp = json_decode($params);

                //         $camp_ids = $requestData['campaign_id'];
                //         $campaignid = $this->encrypt_decrypt($camp_ids, 'decrypt');
                //         // store twitter data
                //         $twitterFollow = new TwitterFollows;
                //         $twitterFollow->campaign_id = $campaignid;
                //         $twitterFollow->user_id = $user_id;
                //         $twitterFollow->brand_twitter_account = $requestData['target_screen_name'];
                //         $twitterFollow->user_twitter_id = $out_puts['user_id'];
                //         $twitterFollow->user_twitter_account = $out_puts['screen_name'];
                //         $twitterFollow->save();
                //         return $this->sendResponse($params_new_resp, Lang::get("common.success", array(), $this->selected_language), 200);
            } catch (ClientException $e) {
                Log::info('test', ['message' => $e]);
                // if ($e->getResponse()->getStatusCode() == 429) {
                //     return $this->sendError(Lang::get("common.please_try_again_later", array(), $this->selected_language), json_decode("{}"), 201);
                //     // $this->markTestIncomplete(Lang::get("common.something_went_wrong", array(), $this->selected_language));
                // } else {
                //     // throw $e;
                //     Log::info('test', ['message' => $e]);
                // }
                //         return $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 500);
            }
            // } else {
            //     return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 201);
            // }
        }
    }
}
