<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use App\Company;
use App\State;
use App\Country;
use App\Campaign;
use App\Invoice;
use App\UserRewards;
use App\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use PDF;
use App\PaymentHistory;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Lang;

class AdminController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Admin Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles admin functionalities for the application.
    */

    /**
     * @GetAllUsers - This API is used for get list of all users.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetAllUsers()
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetAllUsers";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $admin_id = Auth::id();
        $user_id = User::where('user_type', 3)->where('id', $admin_id)->first();
        if (!empty($user_id)) {
            $user = User::where('user_type', 2)->orderBy('id', 'DESC')->get();

            if (!empty($user)) {
                return $this->sendResponse($user, Lang::get('common.success', array(), $this->selected_language));
            } else {
                return $this->sendError(Lang::get('admin.no_users', array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendError(Lang::get('auth.unauthorized_user', array(), $this->selected_language), null, 401);
        }
    }

    /**
     * @GetUserByid - This API is used for get single user details by user ID.
     * 
     * @param {Number} $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetUserByid($id)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetUserByid";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $admin_id = Auth::id();
        $user_id = User::where('user_type', 3)->where('id', $admin_id)->first();
        if (!empty($user_id)) {
            $userDetails = User::where('id', $id)->where('user_type', 2)->first();
            if (!empty($userDetails)) {
                if (!empty($userDetails->country)) {
                    $country = Country::where('id', $userDetails->country)->first();
                    $country_name = $country->country_name;
                    $userDetails['country_name'] = $country_name;
                } else {
                    $userDetails['country_name'] = NULL;
                }
                if (!empty($userDetails->state)) {
                    $state = State::where('id', $userDetails->state)->first();
                    $state_name = $state->state_name;
                    $userDetails['state_name'] = $state_name;
                } else {
                    $userDetails['state_name'] = NULL;
                }
                if (!empty($userDetails->user_photo)) {
                    $Original = URL::to('/') . '/' . 'uploads/user_files/' . $userDetails->user_photo;
                    $userDetails->user_photo = $Original;
                } else {
                    $userDetails->user_photo = NULL;
                }
                return $this->sendResponse($userDetails, Lang::get("common.success", array(), $this->selected_language));
            } else {
                return $this->sendError(Lang::get("admin.user_not_found", array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendError(Lang::get('auth.unauthorized_user', array(), $this->selected_language), null, 401);
        }
    }


    /**
     * @GetAllBrands - This API is used for get list of all brands.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetAllBrands()
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetAllBrands";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $admin_id = Auth::id();
        $user_id = User::where('user_type', 3)->where('id', $admin_id)->first();
        if (!empty($user_id)) {
            $user = User::select('id', 'email', 'active', 'created_at', 'confirmed')->where('user_type', 1)->orderBy('id', 'DESC')->get();
            if (!empty($user)) {
                foreach ($user as $users) {
                    if (!empty($users->id)) {
                        $Company = Company::select('id', 'user_id', 'company_name', 'phone', 'created_at')->where('user_id', $users->id)->get();
                        $new = [];
                        $new = $Company;
                        $users->company_info = $new;
                    }
                }
                return $this->sendResponse($user, Lang::get('common.success', array(), $this->selected_language));
            } else {
                return $this->sendError(Lang::get('admin.no_brands', array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendError(Lang::get('auth.unauthorized_user', array(), $this->selected_language), null, 401);
        }
    }

    /**
     * @GetBrandByid - This API is used for get single brand details by brand ID.
     * 
     * @param {Number} $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetBrandByid($id)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetBrandByid";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $admin_id = Auth::id();
        $user_id = User::where('user_type', 3)->where('id', $admin_id)->first();
        if (!empty($user_id)) {
            $userDetails = User::select('id', 'email', 'active', 'user_photo', 'created_at')->where('id', $id)->where('user_type', 1)->first();
            if (!empty($userDetails)) {
                $campaign_status = "APPROVED";
                $company = Company::select('id', 'user_id', 'company_name', 'phone')->where('user_id', $userDetails->id)->first();
                $campaigns = Campaign::select('id', 'campaign_name', 'campaign_type', 'campaign_type_name', 'start_date', 'end_date', 'sub_total', 'is_approved', 'user_target', 'created_at')->where('company_id', $company->id)->where('campaign_status', $campaign_status)->get();
                foreach ($campaigns as $campaign) {
                    if (!empty($campaign->campaign_image)) {
                        $Original = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->campaign_image;
                        $campaign->campaign_image = $Original;
                    }
                    if (!empty($campaign->uploaded_video_url)) {
                        $Original_video = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->uploaded_video_url;
                        $campaign->uploaded_video_url = $Original_video;
                    }
                    if (!empty($campaign->company_id)) {
                        $Company = Company::select('id', 'user_id', 'company_name', 'phone', 'created_at')->where('id', $campaign->company_id)->get();
                        $new = [];
                        $new = $Company;
                        $campaign->company_info = $new;
                    }
                    $payment_data = PaymentHistory::select('transaction_type')->where('campaign_id', $campaign->id)->latest('id')->first();
                    $campaign->transaction_type = ($payment_data) ? $payment_data->transaction_type : "";
                }
                $userDetails['company_info'] = $company;
                $userDetails['campaign_details'] = $campaigns;
                if (!empty($userDetails->user_photo)) {
                    $Original = URL::to('/') . '/' . 'uploads/user_files/' . $userDetails->user_photo;
                    $userDetails->user_photo = $Original;
                } else {
                    $userDetails->user_photo = NULL;
                }
                return $this->sendResponse($userDetails, Lang::get('common.success', array(), $this->selected_language));
            } else {
                return $this->sendError(Lang::get('admin.no_brand', array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendError(Lang::get('auth.unauthorized_user', array(), $this->selected_language), null, 401);
        }
    }
    /**
     * @UserStatus - This API is used for update status of user Active or Inactive.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function UserStatus(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "UserStatus";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $admin_id = Auth::id();
        $user_id = User::where('user_type', 3)->where('id', $admin_id)->first();
        if (!empty($user_id)) {
            $requestData = $request->json()->all();
            if (count($requestData) > 0) {
                $validator =  Validator::make($requestData, [
                    'user_id' => 'required',
                    'active' => 'required|numeric'
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                $users_id = $requestData['user_id'];
                if (!empty($users_id)) {
                    $user_details = User::where('id', $users_id)->first();
                    if (empty($user_details)) {
                        return $this->sendError(Lang::get("admin.user_not_found", array(), $this->selected_language), null, 201);
                    }
                    $active = $requestData['active'];
                    $user_details->active = $active;
                    $user_details->save();
                    if (!empty($user_details)) {
                        $data = [];
                        $data['active'] = $user_details->active;
                        return $this->sendResponse($data, Lang::get("admin.user_status_changed", array(), $this->selected_language));
                    } else {
                        return $this->sendError(Lang::get("common.failed", array(), $this->selected_language), json_decode("{}"), 201);
                    }
                }
            } else {
                return $this->sendError(Lang::get("common.request_empty", array(), $this->selected_language), null, 400);
            }
        } else {
            return $this->sendError(Lang::get('auth.unauthorized_user', array(), $this->selected_language), null, 400);
        }
    }

    /**
     * @GetCampaignByid - This API is used for get single campaign details by campaign ID.
     * 
     * @param {Number} $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetCampaignByid($id)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetCampaignByid";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $admin_id = Auth::id();
        $user_id = User::where('user_type', 3)->where('id', $admin_id)->first();
        if (!empty($user_id)) {
            $campaign = Campaign::where('id', $id)->first();
            if (!empty($campaign)) {
                if (!empty($campaign->campaign_image)) {
                    $Original = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->campaign_image;
                    $campaign->campaign_image = $Original;
                }
                if (!empty($campaign->uploaded_video_url)) {
                    $Original_video = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->uploaded_video_url;
                    $campaign->uploaded_video_url = $Original_video;
                }
                $campaign_youtube_url = $campaign->youtube_video_url;
                if (!empty($campaign_youtube_url)) {
                    $video_id_array = parse_str(parse_url($campaign_youtube_url, PHP_URL_QUERY), $my_array_of_vars);
                    $video_id = $my_array_of_vars['v'];
                    $campaign->video_id = $video_id;
                } else {
                    $campaign->video_id = '';
                }
                if (!empty($campaign->company_id)) {
                    $Company = Company::select('id', 'company_name')->where('id', $campaign->company_id)->get();
                    $new = [];
                    $new = $Company;
                    $campaign->company_info = $new;
                }
                $payment_data = PaymentHistory::select('transaction_type')->where('campaign_id', $campaign->id)->latest('id')->first();
                $campaign->transaction_type = ($payment_data) ? $payment_data->transaction_type : "";

                return $this->sendResponse($campaign, Lang::get("common.success", array(), $this->selected_language));
            } else {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), null, 201);
            }
        } else {
            return $this->sendError(Lang::get('auth.unauthorized_user', array(), $this->selected_language), null, 401);
        }
    }

    /**
     * @GetAllCampaign - This API is used for get list of all campaigns.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetAllCampaign()
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetAllCampaign";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $admin_id = Auth::id();
        $user_id = User::where('user_type', 3)->where('id', $admin_id)->first();
        if (!empty($user_id)) {
            $approval = "APPROVED";
            $campaigns = Campaign::select('id', 'company_id', 'campaign_name', 'campaign_type_name', 'campaign_type', 'start_date', 'end_date', 'sub_total', 'is_approved', 'note')->where('campaign_status', $approval)->orderBy('id', 'DESC')->get();
            if (!empty($campaigns)) {
                foreach ($campaigns as $campaign) {
                    if (!empty($campaign->campaign_image)) {
                        $Original = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->campaign_image;
                        $campaign->campaign_image = $Original;
                    }
                    if (!empty($campaign->uploaded_video_url)) {
                        $Original_video = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->uploaded_video_url;
                        $campaign->uploaded_video_url = $Original_video;
                    }
                    if (!empty($campaign->company_id)) {
                        $Company = Company::select('id', 'company_name')->where('id', $campaign->company_id)->get();
                        $new = [];
                        $new = $Company;
                        $campaign->company_info = $new;
                    }
                    $payment_data = PaymentHistory::select('transaction_type')->where('campaign_id', $campaign->id)->latest('id')->first();
                    $campaign->transaction_type = ($payment_data) ? $payment_data->transaction_type : "";
                }
                return $this->sendResponse($campaigns, Lang::get("common.success", array(), $this->selected_language));
            } else {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), null, 201);
            }
        } else {
            return $this->sendError(Lang::get('auth.unauthorized_user', array(), $this->selected_language), null, 401);
        }
    }

    /**
     * @CampaignApprovalByAdmin - This API is used for APPROVED the campaign which added by brand(company).
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function CampaignApprovalByAdmin(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "CampaignApprovalByAdmin";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $admin_id = Auth::id();
        $user_id = User::where('user_type', 3)->where('id', $admin_id)->first();
        if (!empty($user_id)) {
            $requestData = $request->json()->all();
            if (count($requestData) > 0) {
                $validator =  Validator::make($requestData, [
                    'company_id' => 'required',
                    'campaign_id' => 'required',
                    'is_approved' => 'required'
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                $campaign_id = $requestData['campaign_id'];
                if (!empty($campaign_id)) {
                    $campaigns = Campaign::where('id', $requestData['campaign_id'])
                        ->where('company_id', $requestData['company_id'])->first();
                    if (empty($campaigns)) {
                        return $this->sendError(Lang::get("admin.campaign_not_found", array(), $this->selected_language), null, 400);
                    }
                    $campaign_name = $campaigns->campaign_name;
                    $is_approved = $requestData['is_approved'];
                    $note = $requestData['note'];
                    $campaigns->is_approved = $is_approved;
                    $campaigns->note = $note;
                    $campaigns->save();

                    $userDetails = Company::where('id', $requestData['company_id'])->first();
                    $company_name = $userDetails->company_name;
                    $Users = User::where('id', $userDetails->user_id)->first();
                    $email = $Users->email;

                    if ($campaigns) {
                        $data = [];
                        $data['is_approved'] = $campaigns->is_approved;
                        $data['note'] = $campaigns->note;
                        if (!empty($is_approved) and $is_approved == "APPROVED") {
                            // $subject_var = ($templates_lang === "es") ? "Campaña aprobada- " : "Campaign Approved- ";
                            $subject_var = Lang::get("admin.campaign_approved_subject", array(), $this->selected_language);
                        } else {
                            // $subject_var = ($templates_lang === "es") ? "Campaña rechazada- " : "Campaign Rejected- ";
                            $subject_var = Lang::get("admin.campaign_rejected_subject", array(), $this->selected_language);
                        }
                        if (!empty($campaigns) and !empty($email) and !empty($company_name) and !empty($subject_var)) {
                            $datas = [
                                'company_name' => $company_name,
                                'campaign_name' => $campaign_name,
                                'note' => $note,
                                'is_approved' => $is_approved
                            ];
                            $to = $email;
                            $emails =  env("MAIL_FROM_ADDRESS", null);
                            $from_name =  env("MAIL_FROM_NAME", null);
                            $from = $emails;

                            $subject =  $subject_var . ' ' . $campaign_name;
                            Mail::send($this->selected_language . '.auth.emails.campaign_approval', $datas, function ($msg) use ($to, $from, $from_name, $subject) {
                                $msg->to($to)->from($from, $from_name)->subject($subject);
                            });
                            return $this->sendResponse($data, Lang::get("admin.campaign_status_changed", array(), $this->selected_language));
                        } else {
                            return $this->sendError(Lang::get("admin.failed_sending_mail", array(), $this->selected_language), null, 201);
                        }
                    } else {
                        return $this->sendError(Lang::get("admin.failed_status", array(), $this->selected_language), json_decode("{}"), 201);
                    }
                } else {
                    return $this->sendError(Lang::get("admin.campaign_not_found", array(), $this->selected_language), null, 201);
                }
            } else {
                return $this->sendError(Lang::get("common.request_empty", array(), $this->selected_language), null, 400);
            }
        } else {
            return $this->sendError(Lang::get('auth.unauthorized_user', array(), $this->selected_language), null, 401);
        }
    }

    /**
     * @GetAllInvoice - This API is used for get list of all invoices.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetAllInvoice()
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetAllInvoice";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $admin_id = Auth::id();
        $user_id = User::where('user_type', 3)->where('id', $admin_id)->first();
        if (!empty($user_id)) {
            $Invoice =  Invoice::select(
                'invoices.id',
                'invoices.invoice_id',
                'invoices.invoice_date',
                'invoices.grand_total',
                'campaigns.campaign_name',
                'campaigns.campaign_type',
                'campaigns.campaign_type_name',
                'companies.company_name'
            )
                ->join('campaigns', 'invoices.campaign_id', '=', 'campaigns.id')
                ->join('companies', 'companies.id', '=', 'campaigns.company_id')
                ->orderBy('invoices.id', 'DESC')
                ->get();
            $invoice_count = count($Invoice);
            if (!empty($invoice_count)) {
                return $this->sendResponse($Invoice, Lang::get("commmon.success", array(), $this->selected_language));
            } else {
                return $this->sendError(Lang::get("admin.no_invoice_found", array(), $this->selected_language), json_decode("[]"), 201);
            }
        } else {
            return $this->sendError(Lang::get('auth.unauthorized_user', array(), $this->selected_language), null, 401);
        }
    }

    /**
     * @DownloadInvoice - This API is used for to generate invoice PDF of invoice by ID & return PDF url.
     * 
     * @param \Illuminate\Http\Request $request
     * @param {Number} $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function DownloadInvoice(Request $request, $id)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "DownloadInvoice";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $admin_id = Auth::id();
        $user_id = User::where('user_type', 3)->where('id', $admin_id)->first();
        if (!empty($user_id)) {
            if (!empty($id)) {
                $show =  Invoice::select('invoices.*', 'campaigns.campaign_name', 'campaigns.campaign_type', 'campaigns.user_target', 'companies.company_name', 'companies.phone', 'users.city')
                    ->join('campaigns', 'invoices.campaign_id', '=', 'campaigns.id')
                    ->join('companies', 'invoices.user_id', '=', 'companies.user_id')
                    ->join('users', 'invoices.user_id', '=', 'users.id')
                    ->where('invoices.id', $id)
                    ->first();
                if (empty($show)) {
                    return $this->sendError(Lang::get("admin.no_invoice_found", array(), $this->selected_language), null, 201);
                }
                $pdf = PDF::loadView($this->selected_language . '.pdf', compact('show'));
                $base64 = $pdf->download('invoice.pdf');
                $decoded = base64_encode($base64);
                return $this->sendResponse($decoded, Lang::get("common.success", array(), $this->selected_language));
            } else {
                return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), null, 400);
            }
        } else {
            return $this->sendError(Lang::get('auth.unauthorized_user', array(), $this->selected_language), null, 401);
        }
    }

    /**
     * @GetPaymentHistoryByBrandId - This API is used for get all payments by brand(company) ID.
     * 
     * @return {Number} $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetPaymentHistoryByBrandId($id)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetPaymentHistoryByBrandId";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $admin_id = Auth::id();
        $user_id = User::where('user_type', 3)->where('id', $admin_id)->first();
        if (!empty($user_id)) {
            if (!empty($id)) {
                $PaymentDetails = PaymentHistory::select('id', 'user_id', 'campaign_id', 'rewards_id', 'invoice_id', 'transaction_id', 'transaction_date', 'transaction_type', 'transaction_status', 'grand_total')->where('user_id', $id)->get();
                if (!empty($PaymentDetails)) {
                    $data = [];
                    foreach ($PaymentDetails as $PaymentDetailss) {
                        // $paypal_response = json_decode($PaymentDetailss->paypal_response);
                        if (!empty($PaymentDetailss->campaign_id)) {
                            $Campaign = Campaign::select('id', 'campaign_name', 'campaign_type', 'campaign_type_name')->where('id', $PaymentDetailss->campaign_id)->first();
                            $PaymentDetailss->campaign_info = $Campaign;
                        }
                        if (!empty($PaymentDetailss->campaign_id)) {
                            $invoice = Invoice::select('id', 'invoice_id', 'campaign_id', 'payment_id')->where('campaign_id', $PaymentDetailss->campaign_id)->first();
                            $PaymentDetailss->invoice_details = $invoice;
                        }
                        // $new = [];
                        // $new = $paypal_response;
                        // $PaymentDetailss->paypal_response = $new;                        
                        $new_again[] = $PaymentDetailss;
                    }
                    if (!empty($new_again)) {
                        $data = $new_again;
                    }
                    if (!empty($data)) {
                        return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language));
                    } else {
                        return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
                    }
                } else {
                    return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), null, 201);
                }
            } else {
                return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), null, 400);
            }
        } else {
            return $this->sendError(Lang::get('auth.unauthorized_user', array(), $this->selected_language), null, 401);
        }
    }

    public function getTwitchSubscriptions(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "getTwitchSubscriptions";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }
        try {

            $requestData = $request->json()->all();

            // check authenticate admin user exist
            $user_id = Auth::id();
            $user_data = User::where('user_type', 3)->where('id', $user_id)->first();

            if (empty($user_data)) {
                return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 403);
            }

            if (!empty($requestData['last_reward_id'])) {
                $last_reward_id = $requestData['last_reward_id'];
            } else {
                $last_reward_id = -1;
            }

            $twitch_rewards = UserRewards::select('user_rewards.*', 'rewards.name')
                ->join('rewards', 'user_rewards.reward_id', '=', 'rewards.id')
                ->where('rewards.id', 1)
                ->where('user_rewards.id', '>', $last_reward_id)
                ->orderBy('created_at', 'DESC')
                ->get();

            return $this->sendResponse($twitch_rewards, Lang::get("common.success", array(), $this->selected_language), 200);
        } catch (Exception $e) {
            return $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 500);
        }
    }

    public function updateTwitchSubscriptions(Request $request)
    {
        $requestData = $request->json()->all();

        if (count($requestData) > 0) {
            $validator =  Validator::make($requestData, [
                'id' => 'required',
                'reward_status' => 'required'
            ]);
            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }
            $reward = UserRewards::where('id', $requestData['id'])->first();
            if (!empty($reward)) {
                $reward->reward_status = $requestData['reward_status'];
                $reward = $reward->save();
                if (!empty($reward)) {
                    return $this->sendResponse($reward, "Success");
                } else {
                    return $this->sendError("Failed", json_decode("{}"), 500);
                }
            } else {
                return $this->sendError("Data not found.", json_decode("{}"));
            }
        } else {
            return $this->sendResponse(json_decode("{}"), "Request body not found", 200);
        }
    }
}
