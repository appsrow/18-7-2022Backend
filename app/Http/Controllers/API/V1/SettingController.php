<?php

namespace App\Http\Controllers\API\V1;

use Illuminate\Http\Request;
use App\User;
use App\Company;
use App\Country;
use App\State;
use Illuminate\Support\Facades\Auth;
use App\Helpers\GeneralHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Lang;
use App\Log;
use Exception;

class SettingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Setting Controller
    |--------------------------------------------------------------------------
    |
    | This controller will use for update brand & user profile with image
    |
    */
    /**
     * @UpdateBrandProfile - This API is used for update logged in brand(company) profile.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function UpdateBrandProfile(Request $request)
    {
        try{
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "UpdateBrandProfile";
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
                    'company_name' => 'required',
                    'phone' => 'required',
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                $User = User::where('id', $user_id)->where('user_type', 1)->first();
                if (empty($User)) {
                    return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), null, 201);
                }
                $company = Company::where('user_id', $User->id)->first();
                $company_name = $requestData['company_name'];
                $phone = $requestData['phone'];
                $company->company_name = $company_name;
                $company->phone = $phone;

                $user_photo = $requestData['user_photo'];
                if (!empty($user_photo)) {
                    $this->ProfileImageUpload($user_photo, $User);
                }
                $User->save();
                $company->save();
                if ($User) {
                    $data = [];
                    $user_updated = User::find($User->id);
                    if (!empty($user_updated->user_photo)) {
                        $Original = URL::to('/') . '/' . 'uploads/user_files/' . $user_updated->user_photo;
                        $data['user_photo'] = $Original;
                    } else {
                        $data['user_photo'] = NULL;
                    }
                    $data['company_info'] = $company;
                    return $this->sendResponse($data, Lang::get("auth.profile_updated_success", array(), $this->selected_language));
                } else {
                    return $this->sendError(Lang::get("common.failed", array(), $this->selected_language), json_decode("{}"), 201);
                }
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), null, 401);
        }
    }

    /**
     * @ProfileImageUpload - update profile image
     *
     * @param \Illuminate\Http\Request $user_photo
     * @param {User Object} $User
     * @return \Illuminate\Http\JsonResponse
     */
    public function ProfileImageUpload($user_photo, $User)
    {
        try{
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "ProfileImageUpload";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        
        }
        if ($user_photo) {
            $folderPath = 'uploads' . DIRECTORY_SEPARATOR . 'user_files' . DIRECTORY_SEPARATOR;
            $destinationPath = GeneralHelper::public_path($folderPath);
            $image_parts = explode(";base64,", $user_photo);
            $image_partss = explode("data:image/", $user_photo);
            if (!empty($image_partss[1])) {
                $image_type_aux = explode("image/", $image_parts[0]);
                $image_type = $image_type_aux[1];
                if (($image_type == "png") || ($image_type == "jpeg") || ($image_type == "jpg")) {
                    $size_in_bytes = (int) (strlen(rtrim($user_photo, '=')) * 3 / 4);
                    $size_in_kb    = $size_in_bytes / 1024;
                    $size_in_mb    = $size_in_kb / 1024;
                    $newfaltvalue = floor($size_in_mb);
                    if ($newfaltvalue > 2) {
                        return $this->sendError(Lang::get("auth.profile_photo_size_limit", array(), $this->selected_language), json_decode("{}"), 400);
                    }
                    $image_base64 = base64_decode($image_parts[1]);
                    $uniqid = uniqid();
                    $file =  $destinationPath . $uniqid . '.' . $image_type;
                    file_put_contents($file, $image_base64);
                    $User->user_photo = $uniqid . '.' . $image_type;
                } else {
                    return $this->sendError(Lang::get("auth.profile_photo_file_type", array(), $this->selected_language), json_decode("{}"), 400);
                }
            }
        } else {
            $User->user_photo = NULL;
        }
    }

    /**
     * @UpdateUserProfile - This API is used for update logged in user profile.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function UpdateUserProfile(Request $request)
    {
        try{
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "UpdateUserProfile";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        
        }
        $user_id = Auth::id();
        $ids = User::where('user_type', 2)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $requestData = $request->json()->all();
            if (count($requestData) > 0) {
                $validator =  Validator::make($requestData, [
                    'first_name' => 'required',
                    'last_name' => 'required',
                    'city' => 'required',
                    'country' => 'required',
                    'is_phone_confirmed' => 'required',
                    'country_dialing_code' => 'required_if:phone,!=,null',
                ]);

                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                $user_id = Auth::id();
                $User = User::where('id', $user_id)->where('user_type', 2)->first();
                if (empty($User)) {
                    return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), null, 201);
                }
                $first_name = $requestData['first_name'];
                $last_name = $requestData['last_name'];
                if (!empty($requestData['gender'])) {
                    $gender = $requestData['gender'];
                }
                $city = $requestData['city'];
                if (!empty($requestData['state'])) {
                    $state = $requestData['state'];
                }
                $country = $requestData['country'];
                if (!empty($requestData['dob'])) {
                    $dob = $requestData['dob'];
                }
                if (!empty($requestData['phone'])) {
                    $phone = $requestData['phone'];
                }
                $User->first_name = $first_name;
                $User->last_name = $last_name;
                if (!empty($gender)) {
                    $User->gender = $gender;
                } else {
                    $User->gender = NULL;
                }
                $User->city = $city;
                if (!empty($state)) {
                    $User->state = $state;
                }
                $User->country = $country;
                if (!empty($dob)) {
                    $User->dob = date("Y-m-d", strtotime($dob));
                }
                $is_phone_confirmed = $requestData['is_phone_confirmed'];
                if (!empty($phone) && $is_phone_confirmed == 0) {
                    $User->phone = $phone;
                }
                if (!empty($requestData['country_dialing_code'])) {
                    $country_dialing_code = $requestData['country_dialing_code'];
                }

                //calling ProfileImageUpload function
                $user_photo = $requestData['user_photo'];
                if (!empty($user_photo)) {
                    $this->ProfileImageUpload($user_photo, $User);
                }

                if (!empty($User->country)) {
                    $country = Country::where('id', $User->country)->first();
                    $country_name = $country->country_name;
                }
                if (!empty($User->state)) {
                    $state = State::where('id', $User->state)->first();
                    $state_name = $state->state_name;
                }
                if (!empty($country_dialing_code)) {
                    $User->country_dialing_code = $country_dialing_code;
                }
                $User->is_phone_confirmed = $is_phone_confirmed;
                $User->save();
                if ($User) {
                    $user_updated = User::find($User->id);
                    if (!empty($user_updated->user_photo)) {
                        $Original = URL::to('/') . '/' . 'uploads/user_files/' . $user_updated->user_photo;
                        $User->user_photo = $Original;
                    } else {
                        $User->user_photo = NULL;
                    }
                    if (!empty($state_name)) {
                        $User->state = $state_name;
                    }
                    if (!empty($country_name)) {
                        $User->country = $country_name;
                    }
                    return $this->sendResponse($User, Lang::get("auth.profile_updated_success", array(), $this->selected_language));
                } else {
                    return $this->sendError(Lang::get("common.failed", array(), $this->selected_language), json_decode("{}"), 201);
                }
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), null, 401);
        }
    }
}
