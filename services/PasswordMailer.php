<?php
use PHPMailer\PHPMailer\PHPMailer;
require_once __DIR__.'/../lib/phpmailer/src/Exception.php';
require_once __DIR__.'/../lib/phpmailer/src/PHPMailer.php';
require_once __DIR__.'/../lib/phpmailer/src/SMTP.php';
class PasswordMailer {
    public static function settings() {
        global $appConfig;
        $private=is_file(__DIR__.'/../config/mail.local.php') ? require __DIR__.'/../config/mail.local.php' : [];
        $mail=array_merge($appConfig['mail'] ?? [],$private);
        foreach(['host'=>'SMTP_HOST','port'=>'SMTP_PORT','username'=>'SMTP_USERNAME','password'=>'SMTP_PASSWORD','from_email'=>'SMTP_FROM_EMAIL','from_name'=>'SMTP_FROM_NAME','encryption'=>'SMTP_ENCRYPTION'] as $key=>$env) if(getenv($env)!==false)$mail[$key]=getenv($env);
        return $mail;
    }
    public static function reset_url($token) {
        // Recovery links must point to the public site, even when sent locally.
        $base=getenv('PASSWORD_RESET_PUBLIC_URL') ?: 'https://bookings.ct.ws/';
        if(!filter_var($base,FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($base,PHP_URL_SCHEME) ?? ''),APP_ENV==='local'?['http','https']:['https'],true) || parse_url($base,PHP_URL_QUERY) || parse_url($base,PHP_URL_FRAGMENT) || parse_url($base,PHP_URL_USER)) throw new RuntimeException('Set the trusted public_url before enabling recovery.');
        return rtrim($base,'/').'/?route=reset_password&token='.rawurlencode($token);
    }
    public static function reset_body($link,$brand='Wanwan Flowers',$minutes=30) {
        $safe=htmlspecialchars($link,ENT_QUOTES,'UTF-8');
        $name=htmlspecialchars($brand,ENT_QUOTES,'UTF-8');
        $minutes=max(1,(int)$minutes);
        ob_start();require __DIR__.'/templates/password_reset.php';return ob_get_clean();
    }
    public static function send_reset($email,$token,$brand) {
        $settings=self::settings();
        if(empty($settings['host']) || !filter_var($settings['from_email'] ?? '',FILTER_VALIDATE_EMAIL))throw new RuntimeException('SMTP is not configured.');
        $encryption=$settings['encryption'] ?? 'tls';
        if(!in_array($encryption,['tls','ssl','none'],true) || ($encryption==='none' && APP_ENV!=='local'))throw new RuntimeException('Use encrypted SMTP in production.');
        $link=self::reset_url($token);$mail=new PHPMailer(true);$mail->isSMTP();$mail->Host=$settings['host'];$mail->Port=(int)($settings['port'] ?? 587);$mail->SMTPAuth=!empty($settings['username']);$mail->Username=$settings['username'] ?? '';$mail->Password=$settings['password'] ?? '';
        $mail->SMTPSecure=$encryption==='none'?'':$encryption;$mail->SMTPAutoTLS=$encryption!=='none';$mail->Timeout=10;$mail->SMTPDebug=0;$mail->CharSet='UTF-8';$mail->setFrom($settings['from_email'],$settings['from_name'] ?? $brand);$mail->addAddress($email);$mail->Subject='Reset your password - '.$brand;
        require_once __DIR__.'/../models/AuthSecurity.php';
        $minutes = (int)ceil(AuthSecurity::config()['reset_lifetime']/60);
        $mail->isHTML(true);
        $mail->Body=self::reset_body($link,$brand,$minutes);
        $mail->AltBody=$brand."\n\nReset your password: ".$link."\nThis link expires in ".$minutes." minutes and can be used once. If you did not request this, you can safely ignore this email. Your password will stay the same.";
        $mail->send();
    }
}
