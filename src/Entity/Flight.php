<?php

namespace App\Entity;

class Flight
{
    private ?int $id = null;
    private string $flightNumber = '';
    private string $airline = '';
    private string $airlineCode = '';
    private string $aircraft = '';
    private string $originCode = '';
    private string $originName = '';
    private string $destCode = '';
    private string $destName = '';
    private string $departureTime = '';
    private string $arrivalTime = '';
    private string $duration = '';
    private string $flightDate = '';
    private float $basePrice = 0.0;
    private string $stops = 'Nonstop';
    private int $seatsAvailable = 45;
    private string $cabin = 'Economy';
    private string $status = 'available';
    private ?string $createdAt = null;

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
            $this->createdAt = $data['created_at'] ?? null;
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
    public function getBasePrice(): float { return $this->basePrice; }
    public function getStops(): string { return $this->stops; }
    public function getSeatsAvailable(): int { return $this->seatsAvailable; }
    public function getCabin(): string { return $this->cabin; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): ?string { return $this->createdAt; }

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
            'created_at' => $this->createdAt,
        ];
    }
}
