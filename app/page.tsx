import Link from "next/link";
import ListingCard from "@/components/ListingCard";
import { getListings } from "@/lib/storage";

export default async function HomePage() {
  const listings = await getListings();
  const latest = listings.slice(0, 6);

  return (
    <div>
      <section className="bg-gradient-to-br from-brand-700 to-brand-500 text-white">
        <div className="mx-auto max-w-6xl px-4 py-14">
          <h1 className="text-3xl md:text-4xl font-bold leading-tight">
            Last minute понуди од македонските туристички агенции
          </h1>
          <p className="mt-3 text-brand-50 max-w-2xl">
            Сите актуелни попусти на едно место. Агенциите објавуваат брзо и
            лесно — за помалку од минута.
          </p>
          <div className="mt-6 flex flex-wrap gap-3">
            <Link
              href="/agencija/nov-oglas"
              className="inline-flex items-center rounded-md bg-white text-brand-700 font-semibold px-4 py-2 hover:bg-brand-50"
            >
              + Поставете оглас
            </Link>
            <Link
              href="/oglasi"
              className="inline-flex items-center rounded-md border border-white/40 px-4 py-2 hover:bg-white/10"
            >
              Сите огласи
            </Link>
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-6xl px-4 py-10">
        <div className="flex items-end justify-between mb-5">
          <h2 className="text-xl font-semibold text-slate-900">
            Најнови понуди
          </h2>
          <Link
            href="/oglasi"
            className="text-sm font-medium text-brand-700 hover:underline"
          >
            Види ги сите →
          </Link>
        </div>
        {latest.length === 0 ? (
          <EmptyState />
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {latest.map((l) => (
              <ListingCard key={l.id} listing={l} />
            ))}
          </div>
        )}
      </section>
    </div>
  );
}

function EmptyState() {
  return (
    <div className="rounded-lg border-2 border-dashed border-slate-300 bg-white p-10 text-center">
      <h3 className="text-lg font-semibold text-slate-800">
        Сè уште нема објавени огласи
      </h3>
      <p className="mt-1 text-slate-600">
        Бидете првата агенција која ќе постави last minute понуда.
      </p>
      <Link href="/agencija/nov-oglas" className="btn-primary mt-4">
        Поставете оглас
      </Link>
    </div>
  );
}
