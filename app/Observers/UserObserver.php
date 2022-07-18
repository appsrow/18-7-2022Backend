<?php 

namespace App\Observers;
use App\User;
use Illuminate\Support\Facades\Auth;

class UserObserver {

    public $userID;

    public function __construct($id = 0){
        $this->userID = Auth::id();

        if(!$this->userID){
            $this->userID = $id;
        }
    }

    public function saving($model)
    {
        $model->updated_by = $this->userID;
    }

    public function saved($model)
    {
        $model->updated_by = $this->userID;
    }


    public function updating($model)
    {
        $model->updated_by = $this->userID;
    }

    public function updated($model)
    {
        $model->updated_by = $this->userID;
    }


    public function creating($model)
    {
        $model->created_by = $this->userID;
    }

    public function created($model)
    {
        $model->created_by = $this->userID;
    }


    public function removing($model)
    {
        $model->purged_by = $this->userID;
    }

    public function removed($model)
    {
        $model->purged_by = $this->userID;
    }
}