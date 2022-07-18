<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\GiftCard;
use App\GiftCardsType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use App\Imports\GiftCardImport;
use Excel;
use App\Helpers\GeneralHelper;
use Exception;
use App\Log;

class GiftCardsController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Gift cards Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles admin functionalities for the gift cards.
    */
    /**
     * @GetAllGiftCardsList - This API is used for get list of all gift cards.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetAllGiftCardsList()
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetAllGiftCardsList";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }
        $admin_id = Auth::id();
        $user_id = User::where('user_type', 3)->where('id', $admin_id)->first();
        if (!empty($user_id)) {
            $gift_cards_list = GiftCard::select('*')->orderBy('id', 'DESC')->get();

            if (!empty($gift_cards_list)) {
                return $this->sendResponse($gift_cards_list, Lang::get('common.success', array(), $this->selected_language));
            } else {
                return $this->sendError(Lang::get('admin.no_gift_cards', array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendError(Lang::get('auth.unauthorized_user', array(), $this->selected_language), null, 401);
        }
    }

    public function addGiftCard(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "addGiftCard";
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
                    'gift_card_code' => 'required',
                    'gift_card_type' => 'required',
                    'coins' => 'required',
                    'expiry_date' => 'required'
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                $gift_card = new GiftCard;
                $gift_card->card_code = Crypt::encryptString($requestData['gift_card_code']);
                $gift_card->type = $requestData['gift_card_type'];
                $gift_card->amount = $requestData['gift_card_amount'];
                $gift_card->price = $requestData['coins'];
                $gift_card->currency_code = $requestData['currency_code'];
                $gift_card->user_photo = '';
                $gift_card->status = 'AVAILABLE';
                $gift_card->redeemed_at = Null;
                $gift_card->expiry_date = $requestData['expiry_date'];
                // $gift_card->created_at = NULL;
                // $gift_card->updated_at = NULL;
                $gift_card->save();
                if (!empty($gift_card)) {
                    return $this->sendResponse(json_decode("{}"), Lang::get("admin.gift_card_inserted", array(), $this->selected_language));
                } else {
                    return $this->sendError(Lang::get("common.failed", array(), $this->selected_language), json_decode("{}"), 201);
                }
            } else {
                return $this->sendError(Lang::get('common.request_empty', array(), $this->selected_language), null, 400);
            }
        } else {
            return $this->sendError(Lang::get('auth.unautorized_user', array(), $this->selected_language), null, 401);
        }
    }

    public function change_gift_card_status(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "change_gift_card_status";
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
                    'gift_card_id' => 'required',
                    'status' => 'required|string'
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                $gift_card_id = $requestData['gift_card_id'];
                if (!empty($gift_card_id)) {
                    $gift_card_details = GiftCard::where('id', $gift_card_id)->first();
                    if (empty($gift_card_details)) {
                        return $this->sendError(Lang::get("admin.gift_card_not_found", array(), $this->selected_language), null, 201);
                    }
                    $status = $requestData['status'];
                    $gift_card_details->status = $status;
                    $gift_card_details->save();
                    if (!empty($gift_card_details)) {
                        return $this->sendResponse(json_decode("{}"), Lang::get("admin.gift_card_status_changed", array(), $this->selected_language));
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

    public function import(Request $request)
    {
        $requestFile = $request->file;

        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "import_csv_gift_cards_file";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }
        try {
            if (!empty($requestFile)) {
                $explodedFileData = explode(",", $request->file);
                $base64File = base64_decode($explodedFileData[1]);
                $folderPath = 'uploads' . DIRECTORY_SEPARATOR . 'user_files' . DIRECTORY_SEPARATOR;
                $destinationPath = GeneralHelper::public_path($folderPath);
                $uniqid = uniqid();
                $file =  $destinationPath . $uniqid . '.csv';
                file_put_contents($file, $base64File);
                $import = new GiftCardImport;
                try {
                    Excel::import($import, $file);
                } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                    $failures = $e->failures();
                    $rowErors = [];
                    foreach ($failures as $failure) {
                        $row = $failure->row(); // row that went wrong
                        // $failure->attribute(); // either heading key (if using heading row concern) or column index
                        // $failure->errors(); // Actual error messages from Laravel validator
                        // $failure->values(); // The values of the row that has failed.
                        $rowErors[] = ['row_no' => $row, 'errors' => $failure->errors()];
                    }
                    return $this->sendError(Lang::get("admin.excel_data_empty", array(), $this->selected_language), $rowErors, 400);
                }
                if ($import->getRowCount() > 0) {
                    $importedRows = $import->getRowCount();
                    return $this->sendResponse(json_decode("{}"), "($importedRows) " . Lang::get("admin.gift_card_inserted", array(), $this->selected_language));
                } else {
                    return $this->sendError(Lang::get("admin.excel_data_empty", array(), $this->selected_language), json_decode("{}"), 201);
                }
            } else {
                return $this->sendError(Lang::get("admin.please_insert_valid_file", array(), $this->selected_language), null, 201);
            }
        } catch (Exception $e) {
            return $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 500);
        }
    }


    /**
     * @getGiftCardTypes - This API is used for get list of all gift cards type used in dropdown.
     */
    public function getGiftCardTypes()
    {
        $gift_card_types = GiftCardsType::all();
        $gift_card_type_data = [];

        foreach ($gift_card_types as $gift_card_type) {
            $gift_card_type_data[] = array(
                'id' => $gift_card_type['id'],
                'itemName' =>  $gift_card_type['gift_card_type']
            );
        }

        if (!empty($gift_card_types)) {
            return $this->sendResponse($gift_card_type_data, Lang::get("common.success", array(), $this->selected_language), 200);
        } else {
            return $this->sendError(Lang::get("common.gift_card_type_not_found", array(), $this->selected_language), null, 201);
        }
    }
}
