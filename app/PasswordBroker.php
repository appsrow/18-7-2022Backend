<?php

namespace App;

use Illuminate\Auth\Passwords\PasswordBroker as BasePasswordBroker;

class PasswordBroker extends BasePasswordBroker
{
 /**
     * Send a password reset link to a user.
     *
     * @param  array  $credentials
     * @return string
     */
    public function sendResetLink(array $credentials)
    {
        // First we will check to see if we found a user at the given credentials and
        // if we did not we will redirect back to this current URI with a piece of
        // "flash" data in the session to indicate to the developers the errors.
        $user = $this->getUser($credentials);

       if (is_null($user)) {
           return static::INVALID_USER;
       }

        // Once we have the reset token, we are ready to send the message out to this
        // user with a link to reset their password. We will then redirect back to
        // the current URI having nothing set in the session to indicate errors.
        if(!is_null($user)) {
            $user->sendPasswordResetNotification(
                $this->tokens->create($user).urlencode('($'.encrypt($user->email))
            );
        }

        return static::RESET_LINK_SENT;
    }

    public function sendResetLink_to_brand(array $credentials)
    {
        // First we will check to see if we found a user at the given credentials and
        // if we did not we will redirect back to this current URI with a piece of
        // "flash" data in the session to indicate to the developers the errors.
        $user = $this->getUser($credentials);

       if (is_null($user)) {
           return static::INVALID_USER;
       }
      
        // Once we have the reset token, we are ready to send the message out to this
        // user with a link to reset their password. We will then redirect back to
        // the current URI having nothing set in the session to indicate errors.
        if(!is_null($user)) {
            $user->sendPasswordResetNotification_to_brand(
                $this->tokens->create($user).urlencode('($'.encrypt($user->email))
            );
        }

        return static::RESET_LINK_SENT;
    }
}