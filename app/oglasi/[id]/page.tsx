import Link from "next/link";
import { notFound } from "next/navigation";
import { getListing } from "@/lib/storage";
import { BOARD_LABELS, TRANSPORT_LABELS } from "@/lib/types";

const dateFmt = new Intl.DateTimeFormat("mk-MK", {
  day: "2-digit",
  month: "long",
  year: "numeric",
});

function formatPrice(amount: number, currency: string) {
  try {
    return new Intl.NumberFormat("mk-MK", {
      style: "currency",
      currency,
      maximumFractionDigits: 0,
    }).format(amount);
  } catch {
    return `${amount} ${currency}`;
  }
}

export default async function ListingDetail({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const listing = await getListing(id);
  if (!listing) notFound();

  return (
    <article className="mx-auto max-w-4xl px-4 py-8">
      <Link
        href="/oglasi"
        className="text-sm text-brand-700 hover:underline"
      >
        ← Назад на огласи
      </Link>

      <div className="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        {listing.imageUrl ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={listing.imageUrl}
            alt={listing.title}
            className="h-72 w-full object-cover"
          />
        ) : (
          <div className="h-56 bg-gradient-to-br from-brand-100 to-brand-50" />
        )}

        <div className="p-6">
          <div className="flex items-start justify-between gap-4 flex-wrap">
            <div>
              <h1 className="text-2xl font-bold text-slate-900">
                {listing.title}
              </h1>
              <p className="mt-1 text-slate-600">
                {listing.destination}, {listing.country} ·{" "}
                <span className="font-medium">{listing.hotelName}</span>{" "}
                <span className="text-amber-500">
                  {"★".repeat(listing.hotelStars)}
                </span>
              </p>
            </div>
            <div className="text-right">
              <div className="text-3xl font-bold text-brand-700">
                {formatPrice(listing.pricePerPerson, listing.currency)}
              </div>
              <div className="text-sm text-slate-500">по лице</div>
            </div>
          </div>

          <dl className="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <Info label="Поаѓање" value={dateFmt.format(new Date(listing.departureDate))} />
            <Info label="Враќање" value={dateFmt.format(new Date(listing.returnDate))} />
            <Info label="Ноќевања" value={`${listing.nights}`} />
            <Info label="Слободни места" value={`${listing.availableSeats}`} />
            <Info label="Пансион" value={BOARD_LABELS[listing.boardType]} />
            <Info label="Превоз" value={TRANSPORT_LABELS[listing.transport]} />
          </dl>

          {listing.features.length > 0 && (
            <section className="mt-6">
              <h2 className="text-sm font-semibold text-slate-700 mb-2">
                Карактеристики
              </h2>
              <div className="flex flex-wrap gap-1.5">
                {listing.features.map((f) => (
                  <span key={f} className="badge">
                    {f}
                  </span>
                ))}
              </div>
            </section>
          )}

          <section className="mt-6">
            <h2 className="text-sm font-semibold text-slate-700 mb-2">Опис</h2>
            <p className="whitespace-pre-line text-slate-700 leading-relaxed">
              {listing.description}
            </p>
          </section>

          <section className="mt-6 rounded-md bg-slate-50 p-4 border border-slate-200">
            <h2 className="text-sm font-semibold text-slate-700">
              Контакт со агенцијата
            </h2>
            <p className="text-slate-900 font-medium">{listing.agencyName}</p>
            <p className="text-slate-700">{listing.agencyContact}</p>
          </section>
        </div>
      </div>
    </article>
  );
}

function Info({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <dt className="text-slate-500">{label}</dt>
      <dd className="mt-0.5 font-medium text-slate-900">{value}</dd>
    </div>
  );
}
