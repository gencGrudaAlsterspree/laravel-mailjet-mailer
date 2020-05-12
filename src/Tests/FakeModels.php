<?php

namespace WizeWiz\MailjetMailer\Tests;

use WizeWiz\MailjetMailer\Collections\MailjetRequestCollection;
use WizeWiz\MailjetMailer\Models\MailjetRequest;

class FakeModels {

   static $UserModel;

    /**
     * Create a collection of fake users.
     * @param array $emails
     * @param bool $save
     * @return \Illuminate\Support\Collection
     */
    public function createFakeUsers(array $emails, $save = true, $unique_save = false) {
        $users = collect([]);
        foreach($emails as $email) {
            $User = $this->createFakeUser($email, $save, $unique_save);
            $users->add($User);
        }
        return $users;
    }

    /**
     * Create a fake user.
     * @param string $email
     */
    public function createFakeUser(string $email, $save = true, $unique_save = false) {
        $user_class = static::$UserModel !== null ? static::$UserModel : config('auth.providers.users.model');
        $User = factory($user_class)->make([
            'email' => $email
        ]);
        if($save) {
            if($unique_save) {
                $DuplicateUser = $user_class::withTrashed()->where(['email' => $email])->first();
                if($DuplicateUser) {
                    $DuplicateUser->forceDelete();
                }
            }
            $User->save();
        }
        return $User;
    }

    /**
     * Create a fake MailjetRequest.
     * @param array $attributes
     * @return
     */
    public function createFakeRequest(array $attributes = []) {
        return factory(MailjetRequest::class)->make($attributes);
    }

    /**
     * Create a fake MailjetRequest.
     * @param array $data
     * @return
     */
    public function createCollection(array $data = []) {
        return factory(MailjetRequestCollection::class)->make($data);
    }

}
