<?php
namespace App\Payments;

interface GatewayInterface
{
    public function key(): string;
    public function displayName(): string;
    public function checkoutUrl(): string;
    public function buildCheckoutPayload(array $transaction): array;
    public function verifyCallback(array $payload, array $transaction): array;
    public function testConnection(): array;
    public function retrieve(string $orderId): array;
    public function refund(string $paymentId, ?float $amount, string $description): array;
}
