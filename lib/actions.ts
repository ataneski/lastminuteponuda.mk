"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { createListing } from "./storage";
import type { BoardType, NewListingInput, Transport } from "./types";

function requiredString(value: FormDataEntryValue | null, field: string): string {
  if (typeof value !== "string" || value.trim() === "") {
    throw new Error(`Полето "${field}" е задолжително.`);
  }
  return value.trim();
}

function requiredNumber(
  value: FormDataEntryValue | null,
  field: string,
  { min = 0 }: { min?: number } = {}
): number {
  const str = requiredString(value, field);
  const n = Number(str);
  if (!Number.isFinite(n) || n < min) {
    throw new Error(`Полето "${field}" мора да биде број ≥ ${min}.`);
  }
  return n;
}

export type CreateListingState =
  | { status: "idle" }
  | { status: "error"; message: string };

export async function createListingAction(
  _prev: CreateListingState,
  formData: FormData
): Promise<CreateListingState> {
  let listingId: string;
  try {
    const featuresRaw = formData.get("features");
    const features =
      typeof featuresRaw === "string"
        ? featuresRaw
            .split(",")
            .map((f) => f.trim())
            .filter(Boolean)
        : [];

    const stars = requiredNumber(formData.get("hotelStars"), "Хотел ѕвезди", {
      min: 1,
    });
    if (stars < 1 || stars > 5) {
      throw new Error("Хотел ѕвезди мора да биде од 1 до 5.");
    }

    const input: NewListingInput = {
      agencyName: requiredString(formData.get("agencyName"), "Агенција"),
      agencyContact: requiredString(
        formData.get("agencyContact"),
        "Контакт на агенција"
      ),
      title: requiredString(formData.get("title"), "Наслов"),
      destination: requiredString(formData.get("destination"), "Дестинација"),
      country: requiredString(formData.get("country"), "Држава"),
      hotelName: requiredString(formData.get("hotelName"), "Хотел"),
      hotelStars: stars as 1 | 2 | 3 | 4 | 5,
      boardType: requiredString(
        formData.get("boardType"),
        "Тип на пансион"
      ) as BoardType,
      transport: requiredString(formData.get("transport"), "Превоз") as Transport,
      departureDate: requiredString(
        formData.get("departureDate"),
        "Датум на поаѓање"
      ),
      returnDate: requiredString(formData.get("returnDate"), "Датум на враќање"),
      nights: requiredNumber(formData.get("nights"), "Ноќевања", { min: 1 }),
      pricePerPerson: requiredNumber(
        formData.get("pricePerPerson"),
        "Цена по лице",
        { min: 1 }
      ),
      currency: (formData.get("currency") as "EUR" | "MKD" | "USD") ?? "EUR",
      availableSeats: requiredNumber(
        formData.get("availableSeats"),
        "Слободни места",
        { min: 1 }
      ),
      description: requiredString(formData.get("description"), "Опис"),
      features,
      imageUrl:
        typeof formData.get("imageUrl") === "string" &&
        (formData.get("imageUrl") as string).trim() !== ""
          ? (formData.get("imageUrl") as string).trim()
          : undefined,
    };

    const created = await createListing(input);
    listingId = created.id;
  } catch (err) {
    return {
      status: "error",
      message: err instanceof Error ? err.message : "Неуспешно зачувување.",
    };
  }

  revalidatePath("/");
  revalidatePath("/oglasi");
  redirect(`/oglasi/${listingId}`);
}
