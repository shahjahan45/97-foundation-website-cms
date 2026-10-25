<?php
namespace App\Payments;
use App\Payments\Gateways\PayHereGateway;
final class PaymentGatewayFactory{public static function make(string $provider='payhere'):GatewayInterface{return match(strtolower($provider)){default=>new PayHereGateway()};}}
