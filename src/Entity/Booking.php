<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'bookings')]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'user_id', type: 'integer')]
    private int $userId = 0;

    #[ORM\Column(name: 'booking_ref', type: 'string', length: 20, unique: true)]
    private string $bookingRef = '';

    #[ORM\Column(name: 'booking_date', type: 'datetime')]
    private ?\DateTimeInterface $bookingDate = null;

    #[ORM\Column(name: 'traveler_name', type: 'string', length: 120)]
    private string $travelerName = '';

    #[ORM\Column(name: 'traveler_email', type: 'string', length: 180)]
    private string $travelerEmail = '';

    #[ORM\Column(name: 'traveler_phone', type: 'string', length: 30, nullable: true)]
    private ?string $travelerPhone = null;

    #[ORM\Column(type: 'string', length: 120)]
    private string $city = '';

    #[ORM\Column(type: 'string', length: 30)]
    private string $mode = 'flights';

    #[ORM\Column(type: 'smallint')]
    private int $adults = 1;

    #[ORM\Column(type: 'string', length: 40)]
    private string $cabin = 'Economy';

    #[ORM\Column(name: 'fare_name', type: 'string', length: 80, nullable: true)]
    private ?string $fareName = null;

    #[ORM\Column(name: 'fare_desc', type: 'string', length: 200, nullable: true)]
    private ?string $fareDesc = null;

    #[ORM\Column(name: 'flight_schedule', type: 'string', length: 200, nullable: true)]
    private ?string $flightSchedule = null;

    #[ORM\Column(name: 'hotel_name', type: 'string', length: 120, nullable: true)]
    private ?string $hotelName = null;

    #[ORM\Column(name: 'hotel_price', type: 'decimal', precision: 10, scale: 2)]
    private float|string $hotelPrice = 0.0;

    #[ORM\Column(type: 'smallint')]
    private int $nights = 0;

    #[ORM\Column(name: 'car_name', type: 'string', length: 80, nullable: true)]
    private ?string $carName = null;

    #[ORM\Column(name: 'car_price', type: 'decimal', precision: 10, scale: 2)]
    private float|string $carPrice = 0.0;

    #[ORM\Column(type: 'smallint')]
    private int $days = 0;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float|string $subtotal = 0.0;

    #[ORM\Column(name: 'discount_num', type: 'decimal', precision: 10, scale: 2)]
    private float|string $discountNum = 0.0;

    #[ORM\Column(name: 'discount_label', type: 'string', length: 80, nullable: true)]
    private ?string $discountLabel = null;

    #[ORM\Column(name: 'grand_total', type: 'decimal', precision: 10, scale: 2)]
    private float|string $grandTotal = 0.0;

    #[ORM\Column(name: 'cancellation_fee', type: 'decimal', precision: 10, scale: 2)]
    private float|string $cancellationFee = 0.0;

    #[ORM\Column(name: 'refund_amount', type: 'decimal', precision: 10, scale: 2)]
    private float|string $refundAmount = 0.0;

    #[ORM\Column(name: 'cancelled_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $cancelledAt = null;

    #[ORM\Column(name: 'promo_code', type: 'string', length: 30, nullable: true)]
    private ?string $promoCode = null;

    #[ORM\Column(name: 'payment_method', type: 'string', length: 30, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: 'string', length: 40)]
    private string $status = 'pending';

    #[ORM\Column(name: 'cancellation_reason', type: 'string', length: 255, nullable: true)]
    private ?string $cancellationReason = null;

    #[ORM\Column(name: 'admin_notes', type: 'text', nullable: true)]
    private ?string $adminNotes = null;

    #[ORM\Column(name: 'cancellation_requested_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $cancellationRequestedAt = null;

    private ?string $username = null;
    private ?string $userEmail = null;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = isset($data['id']) ? (int)$data['id'] : null;
            $this->userId = (int)($data['user_id'] ?? 0);
            $this->bookingRef = $data['booking_ref'] ?? '';
            if (!empty($data['booking_date'])) {
                $this->bookingDate = is_string($data['booking_date']) ? new \DateTime($data['booking_date']) : $data['booking_date'];
            }
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
            if (!empty($data['cancelled_at'])) {
                $this->cancelledAt = is_string($data['cancelled_at']) ? new \DateTime($data['cancelled_at']) : $data['cancelled_at'];
            }
            $this->promoCode = $data['promo_code'] ?? null;
            $this->paymentMethod = $data['payment_method'] ?? null;
            $this->status = $data['status'] ?? 'pending';
            $this->cancellationReason = $data['cancellation_reason'] ?? null;
            $this->adminNotes = $data['admin_notes'] ?? null;
            if (!empty($data['cancellation_requested_at'])) {
                $this->cancellationRequestedAt = is_string($data['cancellation_requested_at']) ? new \DateTime($data['cancellation_requested_at']) : $data['cancellation_requested_at'];
            }
            $this->username = $data['username'] ?? null;
            $this->userEmail = $data['user_email'] ?? null;
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getBookingRef(): string { return $this->bookingRef; }
    public function getBookingDate(): ?string { return $this->bookingDate ? $this->bookingDate->format('Y-m-d H:i:s') : null; }
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
    public function getHotelPrice(): float { return (float)$this->hotelPrice; }
    public function getNights(): int { return $this->nights; }
    public function getCarName(): ?string { return $this->carName; }
    public function getCarPrice(): float { return (float)$this->carPrice; }
    public function getDays(): int { return $this->days; }
    public function getSubtotal(): float { return (float)$this->subtotal; }
    public function getDiscountNum(): float { return (float)$this->discountNum; }
    public function getDiscountLabel(): ?string { return $this->discountLabel; }
    public function getGrandTotal(): float { return (float)$this->grandTotal; }
    public function getCancellationFee(): float { return (float)$this->cancellationFee; }
    public function getRefundAmount(): float { return (float)$this->refundAmount; }
    public function getCancelledAt(): ?string { return $this->cancelledAt ? $this->cancelledAt->format('Y-m-d H:i:s') : null; }
    public function getPromoCode(): ?string { return $this->promoCode; }
    public function getPaymentMethod(): ?string { return $this->paymentMethod; }
    public function getStatus(): string { return $this->status; }
    public function getCancellationReason(): ?string { return $this->cancellationReason; }
    public function getAdminNotes(): ?string { return $this->adminNotes; }
    public function getCancellationRequestedAt(): ?string { return $this->cancellationRequestedAt ? $this->cancellationRequestedAt->format('Y-m-d H:i:s') : null; }
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
            'booking_date' => $this->getBookingDate(),
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
            'cancelled_at' => $this->getCancelledAt(),
            'promo_code' => $this->promoCode,
            'payment_method' => $this->paymentMethod,
            'status' => $this->status,
            'cancellation_reason' => $this->cancellationReason,
            'admin_notes' => $this->adminNotes,
            'cancellation_requested_at' => $this->getCancellationRequestedAt(),
            'username' => $this->username,
            'user_email' => $this->userEmail,
        ];
    }
}
