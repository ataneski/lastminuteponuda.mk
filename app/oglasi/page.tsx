import Link from "next/link";
import ListingCard from "@/components/ListingCard";
import { getListings } from "@/lib/storage";

export const metadata = {
  title: "Сите огласи — lastminuteponuda.mk",
};

export default async function ListingsPage() {
  const listings = await getListings();

  return (
    <div className="mx-auto max-w-6xl px-4 py-8">
      <header className="mb-6 flex items-end justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Сите огласи</h1>
          <p className="text-slate-600">
            {listings.length} активн{listings.length === 1 ? "а понуда" : "и понуди"}
          </p>
        </div>
        <Link href="/agencija/nov-oglas" className="btn-primary">
          + Нов оглас
        </Link>
      </header>

      {listings.length === 0 ? (
        <div className="rounded-lg border-2 border-dashed border-slate-300 bg-white p-10 text-center">
          <p className="text-slate-600">Сè уште нема огласи.</p>
        </div>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {listings.map((l) => (
            <ListingCard key={l.id} listing={l} />
          ))}
        </div>
      )}
    </div>
  );
}
