<?php
require_once __DIR__.'/User.php';
require_once __DIR__.'/AuthSecurity.php';
class CustomerContact
{
    public static function validate(array $input,array $customer,string $savedPhone): array
    {
        $email=is_string($input['email']??null)?strtolower(trim($input['email'])):strtolower($customer['email']);
        $phone=is_string($input['phone']??null)?trim($input['phone']):$savedPhone;
        $phone=preg_replace('/[\s()-]/','',$phone);
        if (strlen($email)>254 || !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new DomainException('Enter a valid email address.');
        if ($phone!=='' && !preg_match('/^(?:\+63|0)9\d{9}$/D',$phone)) throw new DomainException('Enter a Philippine mobile number: 09XXXXXXXXX or +639XXXXXXXXX.');
        if ($email!==strtolower($customer['email']) || $phone!==preg_replace('/[\s()-]/','',$savedPhone)) {
            if (AuthSecurity::consume('customer-contact',(string)$customer['id'],5,900)) throw new DomainException('Please wait before trying to change your contact details again.');
            $password=is_string($input['contact_password']??null)?$input['contact_password']:'';
            if (!password_verify($password,$customer['password'])) throw new DomainException('Enter your current password to change your email or phone number.');
        }
        $existing=User::find_by_username_or_email($email);
        if ($existing && (int)$existing['id']!==(int)$customer['id']) throw new DomainException('This email address is already in use.');
        return [$email,$phone];
    }
}
