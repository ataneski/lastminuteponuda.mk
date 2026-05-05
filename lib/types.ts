export type BoardType =
  | "noBoard"
  | "breakfast"
  | "halfBoard"
  | "fullBoard"
  | "allInclusive"
  | "ultraAllInclusive";

export type Transport = "bus" | "plane" | "ownTransport" | "ferry";

export const BOARD_LABELS: Record<BoardType, string> = {
  noBoard: "Без оброци",
  breakfast: "Појадок",
  halfBoard: "Полупансион",
  fullBoard: "Полн пансион",
  allInclusive: "All inclusive",
  ultraAllInclusive: "Ultra all inclusive",
};

export const TRANSPORT_LABELS: Record<Transport, string> = {
  bus: "Автобус",
  plane: "Авион",
  ownTransport: "Сопствен превоз",
  ferry: "Траект",
};

export interface Listing {
  id: string;
  agencyName: string;
  agencyContact: string;
  title: string;
  destination: string;
  country: string;
  hotelName: string;
  hotelStars: 1 | 2 | 3 | 4 | 5;
  boardType: BoardType;
  transport: Transport;
  departureDate: string;
  returnDate: string;
  nights: number;
  pricePerPerson: number;
  currency: "EUR" | "MKD" | "USD";
  availableSeats: number;
  description: string;
  features: string[];
  imageUrl?: string;
  createdAt: string;
}

export type NewListingInput = Omit<Listing, "id" | "createdAt">;
