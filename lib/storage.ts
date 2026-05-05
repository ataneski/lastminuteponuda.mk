import { promises as fs } from "node:fs";
import path from "node:path";
import { randomUUID } from "node:crypto";
import type { Listing, NewListingInput } from "./types";

const DATA_DIR = path.join(process.cwd(), "data");
const DATA_FILE = path.join(DATA_DIR, "listings.json");

async function ensureFile(): Promise<void> {
  await fs.mkdir(DATA_DIR, { recursive: true });
  try {
    await fs.access(DATA_FILE);
  } catch {
    await fs.writeFile(DATA_FILE, "[]", "utf8");
  }
}

export async function getListings(): Promise<Listing[]> {
  await ensureFile();
  const raw = await fs.readFile(DATA_FILE, "utf8");
  const parsed = JSON.parse(raw) as Listing[];
  return parsed.sort(
    (a, b) =>
      new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
  );
}

export async function getListing(id: string): Promise<Listing | null> {
  const all = await getListings();
  return all.find((l) => l.id === id) ?? null;
}

export async function createListing(input: NewListingInput): Promise<Listing> {
  await ensureFile();
  const all = await getListings();
  const listing: Listing = {
    ...input,
    id: randomUUID(),
    createdAt: new Date().toISOString(),
  };
  await fs.writeFile(
    DATA_FILE,
    JSON.stringify([listing, ...all], null, 2),
    "utf8"
  );
  return listing;
}
