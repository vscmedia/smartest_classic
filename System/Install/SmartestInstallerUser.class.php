<?php

class SmartestInstallerUser{

    protected $database;
    protected $user_columns;

    public function __construct($database=null){
        $this->database = $database ? $database : SmartestDatabase::getInstance('SMARTEST');
    }

    public function createInitialAccounts($username, $password, $firstname, $lastname, $email){

        $username = SmartestStringHelper::toVarName($username);
        $salt = SmartestStringHelper::random(40);
        $hashed_password = $this->hasUserColumn('user_password_salt') ? md5($password.$salt) : md5($password);
        $now = time();

        $this->assertUsersTableIsEmpty();

        if(!$this->insertUser($this->getFirstUserData($username, $hashed_password, $salt, $firstname, $lastname, $email, $now))){
            throw new SmartestException('The installer could not create the first user account.');
        }

        if(!$this->insertUser($this->getSystemUserData($now))){
            throw new SmartestException('The installer could not create the internal Smartest user account.');
        }

        $this->database->preparedQuery('UPDATE Users SET user_id=:user_id WHERE username=:username LIMIT 1', array(
            'user_id' => 0,
            'username' => 'smartest',
        ));

        SmartestLog::getInstance('installer')->log('Created system user \'Smartest\' with a uid of 0', SM_LOG_DEBUG);
        SmartestLog::getInstance('installer')->log('Created user '.$username.' with a uid of 1', SM_LOG_DEBUG);

        return true;

    }

    protected function getFirstUserData($username, $password, $salt, $firstname, $lastname, $email, $now){

        return array(
            'user_id' => 1,
            'username' => $username,
            'password' => $password,
            'user_password_salt' => $salt,
            'user_password_last_changed' => $now,
            'user_password_change_required' => 0,
            'user_firstname' => SmartestStringHelper::sanitize($firstname),
            'user_lastname' => SmartestStringHelper::sanitize($lastname),
            'user_email' => SmartestStringHelper::isEmailAddress($email) ? $email : '',
            'user_register_date' => $now,
            'user_activated' => 1,
            'user_is_smartest_account' => 1,
            'user_type' => 'SM_USERTYPE_SYSTEM_USER',
        );

    }

    protected function getSystemUserData($now){

        return array(
            'user_id' => 0,
            'username' => 'smartest',
            'password' => 'x',
            'user_firstname' => 'Smartest',
            'user_register_date' => $now,
            'user_activated' => 1,
            'user_is_smartest_account' => 1,
            'user_type' => 'SM_USERTYPE_SYSTEM_USER',
        );

    }

    protected function insertUser($data){

        $insert_data = array();

        foreach($data as $column => $value){
            if($this->hasUserColumn($column)){
                $insert_data[$column] = $value;
            }
        }

        if(!count($insert_data)){
            return false;
        }

        $columns = array_keys($insert_data);
        $placeholders = array();

        foreach($columns as $column){
            $placeholders[] = ':'.$column;
        }

        $sql = 'INSERT INTO Users (`'.implode('`, `', $columns).'`) VALUES ('.implode(', ', $placeholders).')';
        return $this->database->preparedQuery($sql, $insert_data);

    }

    protected function assertUsersTableIsEmpty(){

        $existing_users = $this->database->preparedQuery('SELECT user_id FROM Users LIMIT 1');

        if(is_array($existing_users) && count($existing_users)){
            throw new SmartestException('The installer will not create bootstrap user accounts because the Users table already contains data.');
        }

        return true;

    }

    protected function hasUserColumn($column){

        if(!is_array($this->user_columns)){
            $this->user_columns = $this->database->getColumnNames('Users');
        }

        return in_array($column, $this->user_columns, true);

    }

}
