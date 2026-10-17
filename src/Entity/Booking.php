<?php

namespace App\Entity;

class Booking
{
    private ?int $id = null;
    private int $userId = 0;
    private string $bookingRef = '';
    private string $bookingDate = '';
    private string $travelerName = '';
    private string $travelerEmail = '';
    private ?string $travelerPhone = null;
    private string $city = '';
    private string $mode = 'flights';
    private int $adults = 1;
    private string $cabin = 'Economy';
    private ?string $fareName = null;
    private ?string $fareDesc = null;
    private ?string $flightSchedule = null;
    private ?string $hotelName = null;
    private float $hotelPrice = 0.0;
    private int $nights = 0;
    private ?string $carName = null;
    private float $carPrice = 0.0;
    private int $days = 0;
    private float $subtotal = 0.0;
    private float $discountNum = 0.0;
    private ?string $discountLabel = null;
    private float $grandTotal = 0.0;
    private float $cancellationFee = 0.0;
    private float $refundAmount = 0.0;
    private ?string $cancelledAt = null;
    private ?string $promoCode = null;
    private ?string $paymentMethod = null;
    private string $status = 'confirmed';
    private ?string $cancellationReason = null;
    private ?string $adminNotes = null;
    private ?string $cancellationRequestedAt = null;
    private ?string $username = null;
    private ?string $userEmail = null;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = isset($data['id']) ? (int)$data['id'] : null;
            $this->userId = (int)($data['user_id'] ?? 0);
            $this->bookingRef = $data['booking_ref'] ?? '';
            $this->bookingDate = $data['booking_date'] ?? '';
            $this->travelerName = $data['traveler_name'] ?? '';
            $this->travelerEmail = $data['traveler_email'] ?? '';
            $this->travelerPhone = $data['traveler_phone'] ?? null;
            $this->city = $data['city'] ?? '';
            $this->mode = $data['mode'] ?? 'flights';
            $this->adults = (int)($data['adults'] ?? 1);
            $this->cabin = $data['cabin'] ?? 'Economy';
            $this->fareName = $data['fare_name'] ?? null;
            $this->fareDesc = $data['fare_desc'] ?? null;
            $this->flightSchedule = $data['flight_schedule'] ?? null;
            $this->hotelName = $data['hotel_name'] ?? null;
            $this->hotelPrice = (float)($data['hotel_price'] ?? 0);
            $this->nights = (int)($data['nights'] ?? 0);
            $this->carName = $data['car_name'] ?? null;
            $this->carPrice = (float)($data['car_price'] ?? 0);
            $this->days = (int)($data['days'] ?? 0);
            $this->subtotal = (float)($data['subtotal'] ?? 0);
            $this->discountNum = (float)($data['discount_num'] ?? 0);
            $this->discountLabel = $data['discount_label'] ?? null;
            $this->grandTotal = (float)($data['grand_total'] ?? 0);
            $this->cancellationFee = (float)($data['cancellation_fee'] ?? 0);
            $this->refundAmount = (float)($data['refund_amount'] ?? 0);
            $this->cancelledAt = $data['cancelled_at'] ?? null;
            $this->promoCode = $data['promo_code'] ?? null;
            $this->paymentMethod = $data['payment_method'] ?? null;
            $this->status = $data['status'] ?? 'confirmed';
            $this->cancellationReason = $data['cancellation_reason'] ?? null;
            $this->adminNotes = $data['admin_notes'] ?? null;
            $this->cancellationRequestedAt = $data['cancellation_requested_at'] ?? null;
            $this->username = $data['username'] ?? null;
            $this->userEmail = $data['user_email'] ?? null;
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getBookingRef(): string { return $this->bookingRef; }
    public function getBookingDate(): string { return $this->bookingDate; }
    public function getTravelerName(): string { return $this->travelerName; }
    public function getTravelerEmail(): string { return $this->travelerEmail; }
    public function getTravelerPhone(): ?string { return $this->travelerPhone; }
    public function getCity(): string { return $this->city; }
    public function getMode(): string { return $this->mode; }
    public function getAdults(): int { return $this->adults; }
    public function getCabin(): string { return $this->cabin; }
    public function getFareName(): ?string { return $this->fareName; }
    public function getFareDesc(): ?string { return $this->fareDesc; }
    public function getFlightSchedule(): ?string { return $this->flightSchedule; }
    public function getHotelName(): ?string { return $this->hotelName; }
    public function getHotelPrice(): float { return $this->hotelPrice; }
    public function getNights(): int { return $this->nights; }
    public function getCarName(): ?string { return $this->carName; }
    public function getCarPrice(): float { return $this->carPrice; }
    public function getDays(): int { return $this->days; }
    public function getSubtotal(): float { return $this->subtotal; }
    public function getDiscountNum(): float { return $this->discountNum; }
    public function getDiscountLabel(): ?string { return $this->discountLabel; }
    public function getGrandTotal(): float { return $this->grandTotal; }
    public function getCancellationFee(): float { return $this->cancellationFee; }
    public function getRefundAmount(): float { return $this->refundAmount; }
    public function getCancelledAt(): ?string { return $this->cancelledAt; }
    public function getPromoCode(): ?string { return $this->promoCode; }
    public function getPaymentMethod(): ?string { return $this->paymentMethod; }
    public function getStatus(): string { return $this->status; }
    public function getCancellationReason(): ?string { return $this->cancellationReason; }
    public function getAdminNotes(): ?string { return $this->adminNotes; }
    public function getCancellationRequestedAt(): ?string { return $this->cancellationRequestedAt; }
    public function getUsername(): ?string { return $this->username; }
    public function getUserEmail(): ?string { return $this->userEmail; }

    public function isConfirmed(): bool { return $this->status === 'confirmed'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }
    public function isPendingCancellation(): bool { return $this->status === 'pending_cancellation'; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'booking_ref' => $this->bookingRef,
            'booking_date' => $this->bookingDate,
            'traveler_name' => $this->travelerName,
            'traveler_email' => $this->travelerEmail,
            'traveler_phone' => $this->travelerPhone,
            'city' => $this->city,
            'mode' => $this->mode,
            'adults' => $this->adults,
            'cabin' => $this->cabin,
            'fare_name' => $this->fareName,
            'fare_desc' => $this->fareDesc,
            'flight_schedule' => $this->flightSchedule,
            'hotel_name' => $this->hotelName,
            'hotel_price' => $this->hotelPrice,
            'nights' => $this->nights,
            'car_name' => $this->carName,
            'car_price' => $this->carPrice,
            'days' => $this->days,
            'subtotal' => $this->subtotal,
            'discount_num' => $this->discountNum,
            'discount_label' => $this->discountLabel,
            'grand_total' => $this->grandTotal,
            'cancellation_fee' => $this->cancellationFee,
            'refund_amount' => $this->refundAmount,
            'cancelled_at' => $this->cancelledAt,
            'promo_code' => $this->promoCode,
            'payment_method' => $this->paymentMethod,
            'status' => $this->status,
            'cancellation_reason' => $this->cancellationReason,
            'admin_notes' => $this->adminNotes,
            'cancellation_requested_at' => $this->cancellationRequestedAt,
            'username' => $this->username,
            'user_email' => $this->userEmail,
        ];
    }
}
