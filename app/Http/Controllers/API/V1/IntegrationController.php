<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\User;
use App\Campaign;
use App\Action;
use Illuminate\Support\Facades\Auth;
use App\Helpers\GeneralHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class IntegrationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Integration Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles all external integrations related functionalites
    */

    /**
     * @postAction - This API is used to create a new action.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postAction(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $validator =  Validator::make($requestData, [
                'user_id' => 'required',
                'campaign_id' => 'required'
                //'company_id' => 'required',
                //'action_type_id' => 'required'
                //user_id
                //campaign_id
                //action_type_name
                //campaign_name
                //source
                //medium
            ]);

            $campaign_id = app(\App\Http\Controllers\API\V1\CampaignsController::class)->encrypt_decrypt($requestData['campaign_id'], 'decrypt');
            $campaign = Campaign::where('id', $campaign_id)->first();
            if (empty($campaign)) {
                $campaign_id = -2;
            } else {
                $campaign_name = $campaign->campaign_name;
            }

            $user_id = app(\App\Http\Controllers\API\V1\CampaignsController::class)->encrypt_decrypt($requestData['user_id'], 'decrypt');
            #$user_id = $requestData['user_id'];

            $user = User::where('user_type', 2)->where('id', $user_id)->first();
            if (empty($user)) {
                $user_id = -2;
            }

            $action = new Action;
            $action->campaign_id = $campaign_id;
            $action->user_id = $user_id;
            
            if (!empty($requestData['company_id'])) {
                $company_id = $requestData['company_id'];
                $action->company_id = $company_id;
            }
            if (!empty($requestData['action_type_id'])) {
                $action_type_id = $requestData['action_type_id'];
                $action->action_type_id = $action_type_id;
            }
            if (!empty($requestData['action_type_name'])) {
                $action_type_name = $requestData['action_type_name'];
                $action->action_type_name = $action_type_name;
            }
            if (empty($campaign_name) and !empty($requestData['campaign_name'])) {
                $campaign_name = $requestData['campaign_name'];
                $action->campaign_name = $campaign_name;
            }
            if (!empty($requestData['source'])) {
                $source = $requestData['source'];
                $action->source = $source;
            }
            if (!empty($requestData['medium'])) {
                $medium = $requestData['medium'];
                $action->medium = $medium;
            }

            $action->save();
            if ($action) {
                return $this->sendResponse($action, "Success");
            } else {
                return $this->sendError("Failed", json_decode("{}"), 500);
            }
                   
        }else {
            return $this->sendResponse(json_decode("{}"), "Request body not found", 200);
        }
    }

    /**
     * @postAction - This API is used to check for external user actions.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAction(Request $request)
    {
        $user_id = Auth::id();
        $ids = User::where('user_type', 2)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $requestData = $request->json()->all();
            if (count($requestData) > 0) {
                $validator =  Validator::make($requestData, [
                    'campaign_id' => 'required'
                ]);
                $campaign_id = $requestData['campaign_id'];
                $action_type_id = $requestData['action_type_id'];
                $action = Action::where('campaign_id',$campaign_id)
                    ->where('user_id', $user_id)->first();
                if(!empty($action)){
                    return $this->sendResponse($action, "Success");
                } else {
                    return $this->sendError("Action not found.", null, 404);
                }
                
            }else {
                return $this->sendResponse(json_decode("{}"), "Request body not found", 400);
            }
        } else {
            return $this->sendError("User not found", json_decode("{}"));
        }
    }
}
