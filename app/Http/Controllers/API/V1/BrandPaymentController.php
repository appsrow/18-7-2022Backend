<?php

namespace App\Http\Controllers\API\V1;

use Illuminate\Http\Request;
use App\User;
use App\Company;
use App\Campaign;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\BrandWallet;
use App\BrandWalletBalance;
use App\Http\Controllers\Controller;
use App\PaymentHistory;
use App\Invoice;
use PDF;
use Illuminate\Support\Facades\Lang;
use App\Log;

class BrandPaymentController extends Controller
{
    /**
     * @CampaignPaymentFromBrand - This API is used for campaign payment by brand(company).
     * 
     * @return \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function CampaignPaymentFromBrand(Request $request)
    {
        try{
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "CampaignPaymentFromBrand";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 1)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $requestData = $request->json()->all();
            if (count($requestData) > 0) {
                $validator =  Validator::make($requestData, [
                    "campaign_id" => 'required',
                    "campaing_status" => 'required',
                    // "transaction_id" => 'required',
                    // "transaction_date" => 'required',
                    "transaction_type" => 'required',
                    "transaction_status" =>  'required',
                    // "paypal_id" => 'required',
                    "grand_total" => 'required',
                    "cac" => 'required',
                    "tax_percentage" => 'required',
                    //"status" => 'required'
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                $user_id = Auth::id();
                if (!empty($user_id)) {
                    $Users = User::where('id', $user_id)->first();
                    $email = $Users->email;
                    $userDetails = Company::where('user_id', $user_id)->first();
                    $company_name = $userDetails->company_name;
                    if (!empty($userDetails)) {
                        $company_id = $userDetails->id;
                        $campaigns = Campaign::where('id', $requestData['campaign_id'])
                            ->where('company_id', $company_id)
                            ->first();
                        if (!empty($campaigns)) {
                            $PaymentHistory = new PaymentHistory;
                            $PaymentHistory->user_id = $user_id;
                            $PaymentHistory->campaign_id = $requestData['campaign_id'];
                            $PaymentHistory->transaction_id = ($requestData['transaction_id']) ? $requestData['transaction_id'] : null;
                            $PaymentHistory->transaction_date = ($requestData['transaction_date']) ? $requestData['transaction_date'] : date('Y-m-d');
                            $PaymentHistory->transaction_type = $requestData['transaction_type'];
                            $PaymentHistory->transaction_status = $requestData['transaction_status'];
                            $PaymentHistory->paypal_id = ($requestData['paypal_id']) ? $requestData['paypal_id'] : null;
                            $campaign_discount =  env("INVOICE_DISCOUNT", null);
                            $final_total = $requestData['grand_total'] - $campaign_discount;
                            $tax_percentage = $requestData['tax_percentage'];
                            $tax_value = ($final_total * $tax_percentage) / 100;
                            $payment = $final_total + $tax_value;
                            $PaymentHistory->grand_total = $payment;
                            $PaymentHistory->paypal_response = json_encode($requestData);
                            $PaymentHistory->save();
                            //payment success
                            if ($PaymentHistory) {
                                $campaigns->campaign_status = $requestData['campaing_status'];
                                $campaign_name = $campaigns->campaign_name;
                                $closing_amount = BrandWallet::where('user_id', $user_id)->latest('id')->first();

                                $BrandWallet = new BrandWallet;
                                $BrandWallet->user_id = $user_id;
                                $BrandWallet->campaign_id = $campaigns->id;
                                $BrandWallet->opening_balance = 0.00;
                                $BrandWallet->transaction_date = $requestData['transaction_date'];
                                $BrandWallet->credit = $requestData['grand_total'];
                                $BrandWallet->closing_balance = 0.00 + $requestData['grand_total'];
                                $BrandWallet->cac = $requestData['cac'];
                                $BrandWallet->save();

                                if ($BrandWallet) {
                                    $campaigns->save();
                                    if ($campaigns) {
                                        //update brand wallet balance
                                        BrandWalletBalance::updateBrandBalance($user_id);

                                        $Invoice_indb = Invoice::latest('id')->first();
                                        $Invoice = new Invoice;
                                        if (!empty($Invoice_indb)) {
                                            $final_invoice = $Invoice_indb->invoice_id + 1;
                                            $Invoice->invoice_id = str_pad($final_invoice, 5, "0", STR_PAD_LEFT);
                                        } else {
                                            $Invoice->invoice_id = env("INVOICE_START_FROM", null);
                                        }
                                        $Invoice->user_id = $user_id;
                                        $invoice_discount =  env("INVOICE_DISCOUNT", null);
                                        $final_total = $requestData['grand_total'] - $invoice_discount;
                                        $tax_percentage = $requestData['tax_percentage'];
                                        $Invoice->campaign_id = $campaigns->id;
                                        $Invoice->cac = $requestData['cac'];
                                        $Invoice->sub_total = $requestData['grand_total'];
                                        $Invoice->discount = $invoice_discount;
                                        $Invoice->final_total = $final_total;
                                        $Invoice->tax_percentage = $tax_percentage;
                                        $tax_value = ($final_total * $tax_percentage) / 100;
                                        $Invoice->tax_value = $tax_value;
                                        $Invoice->grand_total = $final_total + $tax_value;
                                        $Invoice->payment_id = ($requestData['transaction_id']) ? $requestData['transaction_id'] : $requestData['transaction_type'];
                                        $Invoice->payment_history_id = $PaymentHistory->id;
                                        $Invoice->save();
                                        if (!empty($Invoice)) {
                                            $show =  Invoice::select(
                                                'invoices.*',
                                                'campaigns.campaign_name',
                                                'campaigns.campaign_type',
                                                'campaigns.user_target',
                                                'companies.company_name',
                                                'companies.phone',
                                                'users.city'
                                            )
                                                ->join('campaigns', 'invoices.campaign_id', '=', 'campaigns.id')
                                                ->join('companies', 'invoices.user_id', '=', 'companies.user_id')
                                                ->join('users', 'invoices.user_id', '=', 'users.id')
                                                ->where('invoices.user_id', $user_id)
                                                ->where('invoices.id', $Invoice->id)
                                                ->first();

                                            $pdf = PDF::loadView($this->selected_language . '.pdf', compact('show'));
                                            $grand_total = $payment;
                                            $data = [
                                                'company_name' => $company_name,
                                                'campaign_name' => $campaign_name,
                                                'grand_total' => $grand_total
                                            ];
                                            $to = $email;
                                            $emails =  env("MAIL_FROM_ADDRESS", null);
                                            $from_name =  env("MAIL_FROM_NAME", null);
                                            $from = $emails;
                                            // $subject = ($templates_lang === "es") ? "Campaña creada con éxito- " : "Campaign Created Successfully- ";
                                            $subject = Lang::get("brand.campaign_created_success_subject", array(), $this->selected_language);
                                            $subject .= $campaign_name;

                                            Mail::send($this->selected_language . '.auth.emails.paymentconfirmation', $data, function ($msg)
                                            use ($to, $from, $from_name, $subject, $pdf) {
                                                $msg->to($to)->from($from, $from_name)->subject($subject);
                                                $msg->attachData($pdf->output(), Lang::get("campaign.campaign_invoice_filename", array(), $this->selected_language) . '.pdf');
                                            });

                                            //Send Campaign Approval Email To Admin
                                            $admin_data = [
                                                'brand_name' => $company_name,
                                                'campaign_name' => $campaign_name,
                                                'campaign_type' => $campaigns->campaign_type,
                                                'start_date' => $campaigns->start_date,
                                                'end_date' => $campaigns->end_date,
                                                'user_target' => $campaigns->user_target,
                                                'cac' => $campaigns->cac,
                                                'sub_total' => $campaigns->sub_total,
                                                'grand_total' => $grand_total
                                            ];

                                            $to = env("ADMIN_EMAIL", null);
                                            $emails =  env("MAIL_FROM_ADDRESS", null);
                                            $from_name =  env("MAIL_FROM_NAME", null);
                                            $from = $emails;
                                            // $subject = ($templates_lang === "es") ? "Se requiere aprobación de campaña- " : "Campaign approval required- ";
                                            $subject = Lang::get("brand.campaign_approval_required_subject", array(), $this->selected_language);
                                            $subject .=  $campaign_name;

                                            Mail::send($this->selected_language . '.auth.emails.admin_campaign_created', $admin_data, function ($msg)
                                            use ($to, $from, $from_name, $subject) {
                                                $msg->to($to)->from($from, $from_name)->subject($subject);
                                            });
                                        } else {
                                            return $this->sendError(Lang::get("brand.invoice_creation_failed", array(), $this->selected_language), null, 201);
                                        }
                                    }
                                    return $this->sendResponse($BrandWallet, Lang::get("brand.payment_success", array(), $this->selected_language));
                                }
                            } else {
                                $campaigns->campaign_status = 'DRAFT';
                                $campaigns->save();
                                return $this->sendError(Lang::get("brand.payment_failed", array(), $this->selected_language), json_decode("{}"), 201);
                            }
                        } else {
                            return $this->sendError(Lang::get("brand.campaign_not_found", array(), $this->selected_language), null, 201);
                        }
                    } else {
                        return $this->sendError(Lang::get("brand.brand_not_found", array(), $this->selected_language), null, 201);
                    }
                } else {
                    return $this->sendError(Lang::get("common.unauthorized", array(), $this->selected_language), null, 401);
                }
            } else {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendError(Lang::get("common.unauthorized", array(), $this->selected_language), null, 401);
        }
    }

    /**
     * @InvoiceByUser - This API is used get list of invoices by brand (logged in).
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function InvoiceByUser()
    {
        try{
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "InvoiceByUser";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 1)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $Invoice =  Invoice::select(
                'invoices.id',
                'invoices.invoice_id',
                'invoices.invoice_date',
                'invoices.grand_total',
                'campaigns.campaign_name',
                'campaigns.campaign_type'
            )
                ->join('campaigns', 'invoices.campaign_id', '=', 'campaigns.id')
                ->where('user_id', $user_id)
                ->orderBy('invoices.id', 'DESC')
                ->get();
            $invoice_count = count($Invoice);
            if (!empty($invoice_count)) {
                return $this->sendResponse($Invoice, Lang::get("common.success", array(), $this->selected_language), 200);
            } else {
                return $this->sendError(Lang::get("brand.invoice_not_found", array(), $this->selected_language), json_decode("[]"), 201);
            }
        } else {
            return $this->sendError(Lang::get("common.unauthorized", array(), $this->selected_language), null, 401);
        }
    }

    /**
     * @downloadPDF - This API is used for to generate invoice PDF of invoice by ID & return PDF url.
     * 
     * @return \Illuminate\Http\Request $request
     * @return {Number} $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function downloadPDF(Request $request, $id)
    {
        try{
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "downloadPDF";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        
        }

        $user_id = Auth::id();
        if (!empty($user_id)) {
            if (!empty($id)) {
                $show =  Invoice::select(
                    'invoices.*',
                    'campaigns.campaign_name',
                    'campaigns.campaign_type',
                    'campaigns.user_target',
                    'companies.company_name',
                    'companies.phone',
                    'users.city'
                )
                    ->join('campaigns', 'invoices.campaign_id', '=', 'campaigns.id')
                    ->join('companies', 'invoices.user_id', '=', 'companies.user_id')
                    ->join('users', 'invoices.user_id', '=', 'users.id')
                    ->where('invoices.user_id', $user_id)
                    ->where('invoices.id', $id)
                    ->first();
                if ($show) {
                    $pdf = PDF::loadView($this->selected_language . '.pdf', compact('show'));
                    $base64 = $pdf->download('invoice.pdf');
                    $decoded = base64_encode($base64);
                    return $this->sendResponse($decoded, Lang::get("common.success", array(), $this->selected_language), 200);
                } else {
                    return $this->sendError(Lang::get("brand.invoice_not_found", array(), $this->selected_language), null, 201);
                }
            } else {
                return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), null, 400);
            }
        } else {
            return $this->sendError(Lang::get("common.unauthorized", array(), $this->selected_language), null, 401);
        }
    }
}
