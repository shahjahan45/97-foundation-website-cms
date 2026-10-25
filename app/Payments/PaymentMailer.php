<?php
namespace App\Payments;

final class PaymentMailer
{
    public static function receipt(array $tx): bool
    {
        if(!PaymentConfig::public()['receipt_email_enabled']) return false;
        $email=trim((string)($tx['donor_email']??''));if($email===''||!filter_var($email,FILTER_VALIDATE_EMAIL))return false;
        $from=trim(setting('foundation','email',''));if($from===''||!filter_var($from,FILTER_VALIDATE_EMAIL))return false;
        $subject='97 Foundation donation confirmation - '.($tx['receipt_number']??$tx['public_id']);
        $body="Thank you for supporting 97 Foundation.\n\nDonation amount: LKR ".number_format((float)$tx['amount'],2)."\nTransaction reference: ".$tx['public_id']."\nReceipt number: ".($tx['receipt_number']??'Pending')."\nStatus: Successful\n\nReceipt: ".absolute_url('donate/payment/receipt/'.rawurlencode($tx['public_id']))."\n\n97 Foundation";
        $headers=['From: 97 Foundation <'.$from.'>','Reply-To: '.$from,'Content-Type: text/plain; charset=UTF-8'];
        try{return @mail($email,$subject,$body,implode("\r\n",$headers));}catch(\Throwable){return false;}
    }
}
