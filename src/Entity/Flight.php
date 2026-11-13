<?php

namespace App\Entity;

use App\Repository\FlightRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FlightRepository::class)]
#[ORM\Table(name: 'flights')]
class Flight
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'flight_number', type: 'string', length: 30)]
    private string $flightNumber = '';

    #[ORM\Column(type: 'string', length: 80)]
    private string $airline = '';

    #[ORM\Column(name: 'airline_code', type: 'string', length: 10)]
    private string $airlineCode = '';

    #[ORM\Column(type: 'string', length: 60)]
    private string $aircraft = '';

    #[ORM\Column(name: 'origin_code', type: 'string', length: 10)]
    private string $originCode = '';

    #[ORM\Column(name: 'origin_name', type: 'string', length: 100)]
    private string $originName = '';

    #[ORM\Column(name: 'dest_code', type: 'string', length: 10)]
    private string $destCode = '';

    #[ORM\Column(name: 'dest_name', type: 'string', length: 120)]
    private string $destName = '';

    #[ORM\Column(name: 'departure_time', type: 'string', length: 15)]
    private string $departureTime = '';

    #[ORM\Column(name: 'arrival_time', type: 'string', length: 15)]
    private string $arrivalTime = '';

    #[ORM\Column(type: 'string', length: 30)]
    private string $duration = '';

    #[ORM\Column(name: 'flight_date', type: 'string', length: 15)]
    private string $flightDate = '';

    #[ORM\Column(name: 'base_price', type: 'decimal', precision: 10, scale: 2)]
    private float|string $basePrice = 0.0;

    #[ORM\Column(type: 'string', length: 30)]
    private string $stops = 'Nonstop';

    #[ORM\Column(name: 'seats_available', type: 'integer')]
    private int $seatsAvailable = 45;

    #[ORM\Column(type: 'string', length: 40)]
    private string $cabin = 'Economy';

    #[ORM\Column(type: 'string', length: 30)]
    private string $status = 'available';

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = isset($data['id']) ? (int)$data['id'] : null;
            $this->flightNumber = $data['flight_number'] ?? '';
            $this->airline = $data['airline'] ?? '';
            $this->airlineCode = $data['airline_code'] ?? '';
            $this->aircraft = $data['aircraft'] ?? '';
            $this->originCode = $data['origin_code'] ?? '';
            $this->originName = $data['origin_name'] ?? '';
            $this->destCode = $data['dest_code'] ?? '';
            $this->destName = $data['dest_name'] ?? '';
            $this->departureTime = $data['departure_time'] ?? '';
            $this->arrivalTime = $data['arrival_time'] ?? '';
            $this->duration = $data['duration'] ?? '';
            $this->flightDate = $data['flight_date'] ?? '';
            $this->basePrice = (float)($data['base_price'] ?? 0);
            $this->stops = $data['stops'] ?? 'Nonstop';
            $this->seatsAvailable = (int)($data['seats_available'] ?? 45);
            $this->cabin = $data['cabin'] ?? 'Economy';
            $this->status = $data['status'] ?? 'available';
            if (!empty($data['created_at'])) {
                $this->createdAt = is_string($data['created_at']) ? new \DateTime($data['created_at']) : $data['created_at'];
            }
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getFlightNumber(): string { return $this->flightNumber; }
    public function getAirline(): string { return $this->airline; }
    public function getAirlineCode(): string { return $this->airlineCode; }
    public function getAircraft(): string { return $this->aircraft; }
    public function getOriginCode(): string { return $this->originCode; }
    public function getOriginName(): string { return $this->originName; }
    public function getDestCode(): string { return $this->destCode; }
    public function getDestName(): string { return $this->destName; }
    public function getDepartureTime(): string { return $this->departureTime; }
    public function getArrivalTime(): string { return $this->arrivalTime; }
    public function getDuration(): string { return $this->duration; }
    public function getFlightDate(): string { return $this->flightDate; }
    public function getBasePrice(): float { return (float)$this->basePrice; }
    public function getStops(): string { return $this->stops; }
    public function getSeatsAvailable(): int { return $this->seatsAvailable; }
    public function getCabin(): string { return $this->cabin; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): ?string { return $this->createdAt ? $this->createdAt->format('Y-m-d H:i:s') : null; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'flight_number' => $this->flightNumber,
            'airline' => $this->airline,
            'airline_code' => $this->airlineCode,
            'aircraft' => $this->aircraft,
            'origin_code' => $this->originCode,
            'origin_name' => $this->originName,
            'dest_code' => $this->destCode,
            'dest_name' => $this->destName,
            'departure_time' => $this->departureTime,
            'arrival_time' => $this->arrivalTime,
            'duration' => $this->duration,
            'flight_date' => $this->flightDate,
            'base_price' => $this->basePrice,
            'stops' => $this->stops,
            'seats_available' => $this->seatsAvailable,
            'cabin' => $this->cabin,
            'status' => $this->status,
            'created_at' => $this->getCreatedAt(),
        ];
    }
}
